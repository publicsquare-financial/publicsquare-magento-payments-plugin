<?php

namespace PublicSquare\Payments\Api\Webhooks;

use PublicSquare\Payments\Api\Webhooks\WebhookInterface;
use PublicSquare\Payments\Api\Webhooks\WebhookEventType;
use PublicSquare\Payments\Api\Webhooks\PaymentStatus;
use PublicSquare\Payments\Api\Webhooks\RefundStatus;
use Magento\Sales\Api\OrderRepositoryInterface;
use PublicSquare\Payments\Api\Authenticated\PaymentGetFactory;

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
   * @var OrderRepositoryInterface
   */
  private $orderRepository;

  /**
   * @var PaymentGetFactory
   */
  private $paymentGetFactory;

  public function __construct(
    \PublicSquare\Payments\Logger\Logger $logger,
    \PublicSquare\Payments\Helper\Config $config,
    OrderRepositoryInterface $orderRepository,
    PaymentGetFactory $paymentGetFactory
  ) {
    $this->logger = $logger;
    $this->config = $config;
    $this->orderRepository = $orderRepository;
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

    $order = $this->orderRepository->loadByIncrementId($entity['external_id']);
    if (!$order) {
      $this->logger->error("Order not found for payment " . $entity['id']);
      throw new \Exception("Order not found for payment " . $entity['id']);
    }

    $payment = $order->getPayment();
    if (!$payment) {
      $this->logger->error("Payment not found for order " . $order->getIncrementId());
      throw new \Exception("Payment not found for order " . $order->getIncrementId());
    }

    $payment->setAdditionalInformation(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $entity);
    $payment->save();

    return $payment;
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
