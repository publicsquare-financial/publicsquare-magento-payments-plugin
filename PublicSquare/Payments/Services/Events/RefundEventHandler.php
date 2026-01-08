<?php

namespace PublicSquare\Payments\Services\Events;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use PublicSquare\Payments\Logger\Logger;

class RefundEventHandler implements PSQEventHandler
{
    private Logger|\Monolog\Logger $logger;
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
        $this->logger = $logger->withName('PSQ:RefundEventHandler');
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
        $refund = $event['refund'] ?? null;
        $refundId = $refund['id'] ?? '';
        $paymentId = $refund['payment_id'] ?? '';
        $this->logger->info(
            'Processing refund:',
            [
                'id' => $refundId,
                'payment_id' => $paymentId,
                'status' => $refund['status'] ?? '',
                'reason' => $refund['reason'] ?? '',
                'decline_reason' => $refund['decline_reason'] ?? '',
            ],
        );

        if (!$refundId || !$paymentId) {
            $this->logger->error(
                'PSQ Webhook: Missing refund or payment ID',
                [
                    'id' => $refundId,
                    'payment_id' => $paymentId,
                ],
            );
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
                $this->logger->error('Transaction not found for payment ID', ['payment_id' => $paymentId]);
                // TODO should this throw?
                return;
            }

            $transaction = reset($transactions);
            $orderId = $transaction->getOrderId();
            $order = $this->orderRepository->get($orderId);
            $payment = $order->getPayment();

            // Update additional information
            $additionalInfo = $payment?->getAdditionalInformation() ?? [];
            $additionalInfo[REFUND_ID_KEY] = $refundId;
            $payment?->setAdditionalInformation($additionalInfo);

            // Save the order
            $this->orderRepository->save($order);

            $this->logger->info('Refund ID saved', ['refund_id' => $refundId, 'order_id' => $orderId]);
        } catch (\Exception $e) {
            $this->logger->error('Error handling refund update', ['exception' => $e, 'event_id' => $event['id']]);
            throw $e;
        }
    }
}