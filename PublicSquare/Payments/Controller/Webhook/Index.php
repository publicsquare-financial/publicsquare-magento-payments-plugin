<?php

namespace PublicSquare\Payments\Controller\Webhook;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Phrase;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use Psr\Log\LoggerInterface;
use PublicSquare\Payments\Helper\Config;
use PublicSquare\Payments\Logger\Logger;
use PublicSquare\Payments\Services\Events\RefundEventHandler;
use PublicSquare\Payments\Services\Events\SettlementUpdateEventHandler;

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
    private RequestInterface $request;
    private JsonFactory $jsonResultFactory;
    private Encryptor $encryptor;
    private SettlementUpdateEventHandler $settlementUpdateEventHandler;
    private RefundEventHandler $refundEventHandler;

    public function __construct(
        Config                       $config,
        Logger                       $logger,
        RequestInterface             $request,
        JsonFactory                  $jsonResultFactory,
        Encryptor                    $encryptor,
        SettlementUpdateEventHandler $settlementUpdateEventHandler,
        RefundEventHandler           $refundEventHandler,
    )
    {
        $this->config = $config;
        $this->logger = $logger->withName('PSQ:Webhook');
        $this->request = $request;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->encryptor = $encryptor;
        $this->settlementUpdateEventHandler = $settlementUpdateEventHandler;
        $this->refundEventHandler = $refundEventHandler;

    }

    public function execute(): \Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\App\ResponseInterface
    {
        $result = $this->jsonResultFactory->create();
        try {

            $body = $this->request->getContent();
            $this->logger->debug('Webhook invoked');
            $signature = $this->request->getHeader('X-Signature') ?: '';


            if (!$this->verifySignature($body, $signature)) {
                $this->logger->warning('PSQ Webhook: Invalid signature');
                $result->setStatusHeader(400);
                $result->setData(['error' => 'Invalid signature']);
                return $result;
            }

            $event = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            $eventType = $event['event_type'] ?? '';
            $this->logger->info('Processing event type: ', [
                'id' => $event['id'],
                'event_type' => $eventType,
                'entity_id' => $event['entity_id'],
                'entity_type' => $event['entity_type'],
            ]);
            switch ($eventType) {
                case 'settlement:update':
                    $this->settlementUpdateEventHandler->handleEvent($event);
                    break;
                case 'refund:update':
                    $this->refundEventHandler->handleEvent($event);
                    break;
                default:
                    $this->logger->info('PSQ Webhook: Unhandled event type', ['event_type' => $eventType]);
            }
            $result->setStatusHeader(200);
            $result->setData(['success' => true]);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $result->setStatusHeader(500);
            $result->setData(['error' => $e->getMessage()]);
        }

        return $result;
    }

    private function verifySignature(string $body, string $signature): bool
    {
        $encryptedWebhookKey = $this->config->getWebhookKey();
        if (!$encryptedWebhookKey) {
            $this->logger->error('Webhook secret not configured');
            return false;
        }
        $webhookKey = $this->encryptor->decrypt($encryptedWebhookKey);

        $decodedSignature = base64_decode($signature);
        $decodedKey = base64_decode($webhookKey);

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


    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return new InvalidRequestException($this->jsonResultFactory->create(), [new Phrase('Failed request verification')]);
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        // not a csrfMagento seems to require implementing the CSRF interface
        $signature = $this->request->getHeader('X-Signature') ?: '';
        $body = $request->getContent();
        return $this->verifySignature($body, $signature);
    }
}