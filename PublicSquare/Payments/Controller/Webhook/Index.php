<?php

namespace PublicSquare\Payments\Controller\Webhook;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use Psr\Log\LoggerInterface;
use PublicSquare\Payments\Helper\Config;
use PublicSquare\Payments\Logger\Logger;
use Magento\Framework\Controller\Result\JsonFactory;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class Index implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var LoggerInterface
     */
    private Logger $logger;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var TransactionRepositoryInterface
     */
    private TransactionRepositoryInterface $transactionRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private FilterBuilder $filterBuilder;

    private RequestInterface $request;
    private JsonFactory $jsonResultFactory;

    public function __construct(
        Config                         $config,
        Logger                         $logger,
        OrderRepositoryInterface       $orderRepository,
        TransactionRepositoryInterface $transactionRepository,
        SearchCriteriaBuilder          $searchCriteriaBuilder,
        FilterBuilder                  $filterBuilder,
        RequestInterface               $request,
        JsonFactory                    $jsonResultFactory,
    )
    {
        $this->config = $config;
        $this->logger = $logger->withName('PSQ:Webhook');
        $this->orderRepository = $orderRepository;
        $this->transactionRepository = $transactionRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->request = $request;
        $this->jsonResultFactory = $jsonResultFactory;

    }

    public function execute()
    {
        $result = $this->jsonResultFactory->create();

        $body = $this->request->getContent();
        $this->logger->debug('Webhook invoked');
        $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';

        if (!$this->verifySignature($body, $signature)) {
            $this->logger->warning('PSQ Webhook: Invalid signature');
            $result->setStatusHeader(400);
            $result->setData(['error' => 'Invalid signature']);
            return $result;
        }

        $event = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('PSQ Webhook: Invalid JSON');
            $result->setStatusHeader(400);
            $result->setData(['error' => 'Invalid JSON']);
            return $result;
        }

        $eventType = $event['event_type'] ?? '';
        $this->logger->info('Processing event type: ', ['event_type' => $eventType]);
        if ($eventType === 'settlement:update') {
            $this->handleSettlementUpdate($event['entity']);
        } else {
            $this->logger->info('PSQ Webhook: Unhandled event type', ['event_type' => $eventType]);
        }

        $result->setStatusHeader(200);
        $result->setJsonData(['success' => true]);
        return $result;
    }

    private function verifySignature(string $body, string $signature): bool
    {
        $secret = $this->config->getWebhookSecret();
        if (!$secret) {
            $this->logger->error('PSQ Webhook: Webhook secret not configured');
            return false;
        }

        $decodedSignature = base64_decode($signature);
        $decodedKey = base64_decode($secret);

        $publicKeyPem = "-----BEGIN PUBLIC KEY-----\n" .
            chunk_split(base64_encode($decodedKey), 64, "\n") .
            "-----END PUBLIC KEY-----\n";

        try {
            $rsa = PublicKeyLoader::load($publicKeyPem);
            $rsa = $rsa->withPadding(RSA::SIGNATURE_PKCS1)->withHash('sha256');
            $verified = $rsa->verify($body, $decodedSignature);
        } catch (\Exception $e) {
            $this->logger->error('PSQ Webhook: Invalid public key or verification error', ['exception' => $e->getMessage()]);
            return false;
        }

        return $verified;
    }

    private function handleSettlementUpdate(array $settlement): void
    {
        $settlementId = $settlement['id'] ?? '';
        $paymentId = $settlement['payment']['id'] ?? '';
        $this->logger->info('Processing settlement id: ' . $settlementId . 'for Payment ID: ' . $paymentId);

        if (!$settlementId || !$paymentId) {
            $this->logger->error('PSQ Webhook: Missing settlement or payment ID', ['settlement' => $settlement]);
            return;
        }

        try {
            // Find transaction by payment ID
            $filters = [
                $this->filterBuilder->setField('txn_id')->setValue($paymentId)->create(),
            ];
            $criteria = $this->searchCriteriaBuilder->addFilters($filters)->create();
            $transactions = $this->transactionRepository->getList($criteria)->getItems();

            if (empty($transactions)) {
                $this->logger->error('PSQ Webhook: Transaction not found for payment ID', ['payment_id' => $paymentId]);
                return;
            }

            $transaction = reset($transactions);
            $orderId = $transaction->getOrderId();
            $order = $this->orderRepository->get($orderId);
            $payment = $order->getPayment();

            // Update additional information
            $additionalInfo = $payment->getAdditionalInformation() ?? [];
            $additionalInfo['psq_settlement_id'] = $settlementId;
            $payment->setAdditionalInformation($additionalInfo);

            // Save the order
            $this->orderRepository->save($order);

            $this->logger->info('PSQ Webhook: Settlement ID saved', ['settlement_id' => $settlementId, 'order_id' => $orderId]);
        } catch (\Exception $e) {
            $this->logger->error('PSQ Webhook: Error handling settlement update', ['exception' => $e->getMessage(), 'settlement' => $settlement]);
        }
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return new InvalidRequestException($this->jsonResultFactory->create(), 'Failed request verification');
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
        $body = $request->getContent();
        return $this->verifySignature($body, $signature);
    }
}