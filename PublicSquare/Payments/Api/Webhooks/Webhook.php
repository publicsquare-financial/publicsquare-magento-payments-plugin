<?php

namespace PublicSquare\Payments\Api\Webhooks;

use PublicSquare\Payments\Api\Webhooks\WebhookInterface;
use PublicSquare\Payments\Api\Webhooks\WebhookEventType;
use PublicSquare\Payments\Api\Webhooks\PaymentStatus;
use PublicSquare\Payments\Api\Webhooks\RefundStatus;
use PublicSquare\Payments\Api\Authenticated\PaymentGetFactory;
use Magento\Sales\Model\Spi\OrderResourceInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Service\CreditmemoService;

class Webhook implements WebhookInterface
{
  /**
   * @var Logger
   */
  private $logger;

  /**
   * @var Config
   */
  private $config;

  /**
   * @var OrderResourceInterface
   */
  private $orderResource;

  /**
   * @var OrderInterfaceFactory
   */
  private $orderFactory;

  /**
   * @var CreditmemoFactory
   */
  private $creditMemoFactory;

  /**
   * @var CreditmemoService
   */
  private $creditMemoService;

  /**
   * @var PaymentGetFactory
   */
  private $paymentGetFactory;

  public function __construct(
    \PublicSquare\Payments\Logger\Logger $logger,
    \PublicSquare\Payments\Helper\Config $config,
    OrderResourceInterface $orderResource,
    OrderInterfaceFactory $orderFactory,
    CreditmemoFactory $creditMemoFactory,
    CreditmemoService $creditMemoService,
    PaymentGetFactory $paymentGetFactory
  ) {
    $this->logger = $logger;
    $this->config = $config;
    $this->orderResource = $orderResource;
    $this->orderFactory = $orderFactory;
    $this->creditMemoFactory = $creditMemoFactory;
    $this->creditMemoService = $creditMemoService;
    $this->paymentGetFactory = $paymentGetFactory;
  }

  public function execute(string $id, string $event_type, string $entity_type, string $entity_id, mixed $entity)
  {
    if ($this->verifySignature()) {
      try {
        $event = WebhookEventType::from($event_type);
      } catch (\Throwable $th) {
        $this->logger->error("Unknown event type " . $event_type);
        return "";
      }
      try {
        switch ($event) {
          case WebhookEventType::PAYMENT_UPDATE:
            $this->handlePaymentUpdate($entity);
            break;
          case WebhookEventType::REFUND_UPDATE:
            $this->handleRefundUpdate($entity);
            break;
          default:
            $this->logger->error("Unknown event type " . $event_type);
            break;
        }
      } catch (\Exception $e) {
        $this->logger->error("Error handling event " . $event_type . " " . $e->getMessage());
      }
    } else {
      $this->logger->error("Signature verification failed");
    }
    return "";
  }

  private function handlePaymentUpdate(mixed $entity)
  {
    try {
      $status = PaymentStatus::from($entity['status']);

      $this->logger->info("Payment update received " . $entity['id'] . " " . $status->value);

      $this->updateOrderPaymentAdditionalInformation($entity);
    } catch (\ValueError $th) {
      $this->logger->error("Unknown payment status " . $entity['status']);
    }
  }

  private function handleRefundUpdate(mixed $entity)
  {
    try {
      $status = RefundStatus::from($entity['status']);

      $this->logger->info("Refund update received " . $entity['id'] . " " . $status->value);

      $payment = $this->getPSQPaymentFromPSQRefundEntity($entity);

      $creditMemoCreated = $this->checkIfPaymentShouldBeRefunded($entity, $payment);

      $this->updateOrderPaymentAdditionalInformation($payment);
    } catch (\ValueError $th) {
      $this->logger->error("Unknown refund status " . $entity['status']);
    }
  }

  private function getPSQPaymentFromPSQRefundEntity(mixed $refundEntity): mixed
  {
    if (!isset($refundEntity['payment_id'])) {
      $this->logger->error("Missing payment_id for refund " . $refundEntity['id']);
      throw new \Exception("Missing payment_id for refund " . $refundEntity['id']);
    }

    $payment = $this->paymentGetFactory->create([
      'paymentId' => $refundEntity['payment_id']
    ])->getResponseData();

    if (!$payment) {
      $this->logger->error("Payment not found for refund " . $refundEntity['id']);
      throw new \Exception("Payment not found for refund " . $refundEntity['id']);
    }

    return $payment;
  }

  private function updateOrderPaymentAdditionalInformation(mixed $entity): mixed
  {
    if (!isset($entity['external_id'])) {
      $this->logger->error("Missing external_id for payment " . $entity['id']);
      throw new \Exception("Missing external_id for payment " . $entity['id']);
    }

    $order = $this->getOrderForPayment($entity);

    $payment = $order->getPayment();
    if (!$payment) {
      $this->logger->error("Payment not found for order " . $order->getIncrementId());
      throw new \Exception("Payment not found for order " . $order->getIncrementId());
    }

    $payment->setAdditionalInformation(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $entity);
    $payment->save();

    return $payment;
  }

  private function checkIfPaymentShouldBeRefunded(mixed $refund, mixed $payment): bool
  {
    if ($payment['status'] === 'cancelled') {
      // Check if there is a credit memo for this payment
      $order = $this->getOrderForPayment($payment);
      $creditMemo = $order->getCreditmemosCollection()->getFirstItem();
      if ($creditMemo) {
        $this->logger->info("Credit memo found for payment, no need to create a new credit memo. " . $refund['id']);
        return false;
      } else {
        $this->logger->info("No credit memo found for payment, creating a new credit memo. " . $refund['id']);
        $invoices = $order->getInvoiceCollection();
        foreach ($invoices as $invoice) {
          $invoiceincrementid = $invoice->getIncrementId();
        }

        $invoiceobj = $invoice->loadByIncrementId($invoiceincrementid);
        $creditmemo = $this->creditMemoFactory->createByOrder($order);

        // Don't set invoice if you want to do offline refund
        $creditmemo->setInvoice($invoiceobj);

        $creditmemo->setGrandTotal($refund['amount']);

        $this->creditMemoService->refund($creditmemo);
        return true;
      }
    }

    $this->logger->info("Payment is not cancelled, no need to create a credit memo. " . $refund['id']);

    return false;
  }

  private function getOrderForPayment(mixed $entity)
  {
    $order = $this->orderFactory->create();
    $this->orderResource->load($order, $entity['external_id'], OrderInterface::INCREMENT_ID);
    if (!$order) {
      $this->logger->error("Order not found for payment " . $entity['id']);
      throw new \Exception("Order not found for payment " . $entity['id']);
    }

    return $order;
  }

  private function verifySignature(): bool
  {
    $signingSecret = $this->config->getWebhookSigningSecret();
    if (empty($signingSecret)) {
      $this->logger->warning("Missing webhook signing secret - cannot handle webhook request.");
      return false;
    }

    // Get the signature from the X-SIGNATURE header
    $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
    if (empty($signature)) {
      $this->logger->error("Missing X-SIGNATURE header");
      return false;
    }

    // Get the raw request body
    $requestBody = file_get_contents('php://input');
    if (empty($requestBody)) {
      $this->logger->error("Empty request body");
      return false;
    }

    try {
      // Format the public key in X.509 style
      $publicKeyPem = "-----BEGIN PUBLIC KEY-----\n" .
        chunk_split("MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8A" . $signingSecret, 64, "\n") .
        "-----END PUBLIC KEY-----\n";

      // Create RSA instance and import the public key
      $rsa = openssl_pkey_get_public($publicKeyPem);
      if ($rsa === false) {
        $error = openssl_error_string();
        $this->logger->error("Failed to import public key: " . $error);
        return false;
      }

      // Decode the signature from base64
      $decodedSignature = base64_decode($signature);
      if ($decodedSignature === false) {
        $this->logger->error("Invalid base64-encoded signature");
        return false;
      }

      // Verify the signature using PKCS1 padding
      $verified = openssl_verify($requestBody, $decodedSignature, $rsa, OPENSSL_ALGO_SHA256);

      if ($verified === 1) {
        return true;
      } else {
        return false;
      }
    } catch (\Exception $e) {
      $this->logger->error("Error verifying signature: " . $e->getMessage());
      return false;
    }
  }
}
