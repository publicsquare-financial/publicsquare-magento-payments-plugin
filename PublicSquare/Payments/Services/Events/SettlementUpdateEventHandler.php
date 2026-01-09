<?php

namespace PublicSquare\Payments\Services\Events;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use PublicSquare\Payments\Api\Constants;
use PublicSquare\Payments\Logger\Logger;

class SettlementUpdateEventHandler implements PSQEventHandler
{
    private Logger $logger;
    private OrderRepositoryInterface $orderRepository;
    private TransactionRepositoryInterface $transactionRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private FilterBuilder $filterBuilder;

    public function __construct(
        Logger                         $logger,
        OrderRepositoryInterface       $orderRepository,
        TransactionRepositoryInterface $transactionRepository,
        SearchCriteriaBuilder          $searchCriteriaBuilder,
        FilterBuilder                  $filterBuilder,
    )
    {
        $this->logger = $logger->withName('PSQ:SettlementUpdateEventHandler');
        $this->orderRepository = $orderRepository;
        $this->transactionRepository = $transactionRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
    }

    /**
     * @throws \Exception
     */
    public function handleEvent(array $event): void
    {
        $eventId = $event['id'];
        $settlement = $event['entity'] ?? null;
        $settlementId = $settlement['id'] ?? '';
        $paymentId = $settlement['transaction']['payment']['id'] ?? '';
        $this->logger->info(
            'Processing settlement',
            [
                'id' => $settlementId,
                'payment_id' => $paymentId,
                'transaction_id' => $settlement['transaction_id'] ?? '',
                'status' => $settlement['status'] ?? '',
                'settled_at' => $settlement['settled_at'] ?? '',
                'tx_external_id' => $settlement['transaction']['external_id'] ?? '',
                'tx_type' => $settlement['transaction']['type'] ?? '',
                'event_id' => $eventId,
            ],
        );

        if (!$settlementId || !$paymentId) {
            $this->logger->error('Missing settlement or payment ID', ['settlement' => $settlement,
                'event_id' => $eventId,
            ]);
            // TODO: Should this throw?
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
                $this->logger->error('Transaction not found for payment ID', ['payment_id' => $paymentId,
                    'event_id' => $eventId,
                ]);
                return;
            }

            $transaction = reset($transactions);
            $orderId = $transaction->getOrderId();
            $order = $this->orderRepository->get($orderId);
            $payment = $order->getPayment();

            // Update additional information
            $additionalInfo = $payment?->getAdditionalInformation() ?? [];
            $additionalInfo[Constants::SETTLEMENT_ID_KEY] = $settlementId;
            $payment?->setAdditionalInformation($additionalInfo);

            // Save the order
            $this->orderRepository->save($order);

            $this->logger->info('Settlement ID saved', ['settlement_id' => $settlementId, 'order_id' => $orderId, 'event_id' => $eventId]);
        } catch (\Exception $e) {
            $this->logger->error('Error handling settlement update', ['exception' => $e, 'event_id' => $eventId]);
            throw $e;
        }
    }
}