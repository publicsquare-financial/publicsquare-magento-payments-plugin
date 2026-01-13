<?php

namespace PublicSquare\Payments\Controller\Webhook;

use Magento\AdminNotification\Model\ResourceModel\Inbox\CollectionFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Notification\NotifierInterface;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;
use PublicSquare\Payments\Api\Constants;
use PublicSquare\Payments\Exception\BadRequestException;
use PublicSquare\Payments\Exception\ErrorNotifications;
use PublicSquare\Payments\Exception\PSQException;
use PublicSquare\Payments\Logger\Logger;
use PublicSquare\Payments\Services\Events\RefundEventHandler;
use PublicSquare\Payments\Services\Events\SettlementUpdateEventHandler;
use PublicSquare\Payments\Services\WebhookSignatureService;
use function __;

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
    private RemoteAddress $remoteAddress;
    private NotifierInterface $notifier;
    private CollectionFactory $collectionFactory;

    public function __construct(
        Logger                       $logger,
        RequestInterface             $request,
        JsonFactory                  $jsonResultFactory,
        SettlementUpdateEventHandler $settlementUpdateEventHandler,
        RefundEventHandler           $refundEventHandler,
        WebhookSignatureService      $webhookSignatureService,
        RemoteAddress                $remoteAddress,
        NotifierInterface             $notifier,
        CollectionFactory              $collectionFactory,

    )
    {
        $this->logger = $logger->withName('PSQ:Webhook');
        $this->request = $request;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->settlementUpdateEventHandler = $settlementUpdateEventHandler;
        $this->refundEventHandler = $refundEventHandler;
        $this->webhookSignatureService = $webhookSignatureService;
        $this->remoteAddress = $remoteAddress;
        $this->notifier = $notifier;
        $this->collectionFactory = $collectionFactory;
    }

    public function execute(): \Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\App\ResponseInterface
    {
        $result = $this->jsonResultFactory->create();
        try {

            $body = $this->request->getContent();
            $this->logger->debug('Webhook invoked');
            $signature = $this->request->getHeader('X-Signature') ?: '';
            if (!$signature) {
                $this->logger->warning('Received unsigned request!', [
                    'IP' => $this->remoteAddress->getRemoteAddress(),
                    'Host' => $this->remoteAddress->getRemoteHost(),
                ]);
                $result->setStatusHeader(400);
                return $result;
            }


            if (!$this->webhookSignatureService->verify($signature, $body)) {
                // Signature found and processed but did not pass verification
                $result->setStatusHeader(401);
                $result->setData(['error' => 'Invalid signature']);
                $this->logger->error('Invalid signature');

                $collection = $this->collectionFactory->create();
                $exists = $collection->addFieldToFilter('title', ErrorNotifications::WEBHOOK_MISCONFIGURED)->addRemoveFilter()->getSize() > 0;
                if (!$exists) {
                    $description = 'Received invalid signature in the PublicSquare webhook. Verify the webhook configuration in the PublicSquare portal matches the plugin configuration in Stores > Configuration > Sales > Payment Methods > PublicSquare.';
                    $this->notifier->addNotice(ErrorNotifications::WEBHOOK_MISCONFIGURED, $description);
                }
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
                    throw new BadRequestException('No handler found for event: [' . $event['id'] . '] with event_type: [' . $eventType . ']');
            }
            $result->setStatusHeader(200);
            $result->setData(['success' => true]);
        } catch (PSQException $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $result->setStatusHeader($e->getPropagateHttpResponseCode());
            $result->setData(['error' => $e->getMessage()]);
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