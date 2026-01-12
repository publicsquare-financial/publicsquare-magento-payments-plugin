<?php

namespace PublicSquare\Payments\Controller\Webhook;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;
use PublicSquare\Payments\Api\Constants;
use PublicSquare\Payments\Logger\Logger;
use PublicSquare\Payments\Services\Events\RefundEventHandler;
use PublicSquare\Payments\Services\Events\SettlementUpdateEventHandler;
use PublicSquare\Payments\Services\WebhookSignatureService;

class Index implements HttpPostActionInterface, CsrfAwareActionInterface
{

    /**
     * @var LoggerInterface
     */
    private Logger $logger;
    private RequestInterface $request;
    private JsonFactory $jsonResultFactory;
    private SettlementUpdateEventHandler $settlementUpdateEventHandler;
    private RefundEventHandler $refundEventHandler;

    private WebhookSignatureService $webhookSignatureService;

    public function __construct(
        Logger                       $logger,
        RequestInterface             $request,
        JsonFactory                  $jsonResultFactory,
        SettlementUpdateEventHandler $settlementUpdateEventHandler,
        RefundEventHandler           $refundEventHandler,
        WebhookSignatureService        $webhookSignatureService,
    )
    {
        $this->logger = $logger->withName('PSQ:Webhook');
        $this->request = $request;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->settlementUpdateEventHandler = $settlementUpdateEventHandler;
        $this->refundEventHandler = $refundEventHandler;
        $this->webhookSignatureService = $webhookSignatureService;

    }

    public function execute(): \Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\App\ResponseInterface
    {
        $result = $this->jsonResultFactory->create();
        try {

            $body = $this->request->getContent();
            $this->logger->debug('Webhook invoked');
            $signature = $this->request->getHeader('X-Signature') ?: '';


            if (!$this->webhookSignatureService->verify($signature, $body)) {
                $this->logger->warning('PSQ Webhook: Invalid signature');
                $result->setStatusHeader(400);
                $result->setData(['error' => 'Invalid signature']);
                $this->logger->error('Invalid signature');
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
                case Constants::WEBHOOK_EVENT_SETTLEMENT_UPDATE:
                    $this->settlementUpdateEventHandler->handleEvent($event);
                    break;
                case Constants::WEBHOOK_EVENT_REFUND_UPDATE:
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

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return new InvalidRequestException($this->jsonResultFactory->create(), [new Phrase('Failed request verification')]);
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        // not a csrfMagento seems to require implementing the CSRF interface
        // the signature does get validated...
        return true;
    }
}