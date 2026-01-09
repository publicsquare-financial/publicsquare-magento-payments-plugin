<?php

namespace PublicSquare\Payments\Test\Unit\Services\Events;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\Data\TransactionSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use PHPUnit\Framework\TestCase;
use PublicSquare\Payments\Logger\Logger;
use PublicSquare\Payments\Services\Events\RefundEventHandler;

class RefundEventHandlerTest extends TestCase
{
    private Logger $logger;
    private OrderRepositoryInterface $orderRepository;
    private TransactionRepositoryInterface $transactionRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private FilterBuilder $filterBuilder;
    private RefundEventHandler $handler;

    function setup(): void
    {
        $this->logger = new Logger();

        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->transactionRepository = $this->createMock(TransactionRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->filterBuilder = $this->createMock(FilterBuilder::class);


        $this->handler = new RefundEventHandler(
            $this->logger,
            $this->orderRepository,
            $this->transactionRepository,
            $this->searchCriteriaBuilder,
            $this->filterBuilder,
        );

    }

    function testHappyPath(): void
    {
        $event = [
            'id' => 'test event',
            'event_type' => 'refund:update',
            'entity_type' => 'Refund',
            'entity_id' => 'rfd_1',
            'entity' => [
                'id' => 'rfd_1',
                'status' => 'refunded',
                'reason' => null,
                'decline_reason' => null,
                'payment_id' => 'pmt_1',
            ],
        ];
        $this->filterBuilder->method('setField')->willReturnSelf();
        $this->filterBuilder->method('setValue')->willReturnSelf();
        $this->filterBuilder->method('create')->willReturn($this->createMock(Filter::class));

        $this->searchCriteriaBuilder->method('addFilters')->willReturnSelf();
        $this->searchCriteriaBuilder->method('create')->willReturn($this->createMock(SearchCriteriaInterface::class));

        $transactionSearchResults = $this->createMock(TransactionSearchResultInterface::class);
        $this->transactionRepository->method('getList')->willReturn($transactionSearchResults);

        $transaction = $this->createMock(TransactionInterface::class);
        $transactionSearchResults->method('getItems')->willReturn([$transaction]);

        $transaction->method('getOrderId')->willReturn('odr_1');

        $order = $this->createMock(OrderInterface::class);
        $this->orderRepository->method('get')->willReturn($order);

        $payment = $this->createMock(PaymentInterface::class);
        $order->method('getPayment')->willReturn($payment);

        $additionalData = [];
        $payment->method('getAdditionalInformation')->willReturn($additionalData);
        $payment->expects($this->once())
            ->method('setAdditionalInformation')
            ->with($this->callback(function ($info) {
                return isset($info['psq_refund_id']) && $info['psq_refund_id'] === 'rfd_1';
            }));
        $this->orderRepository->expects($this->once())
            ->method('save')
            ->with($order);

        $this->handler->handleEvent($event);
    }

    function testMissingRefundId(): void
    {
        $event = [
            'id' => 'test event',
            'event_type' => 'refund:update',
            'entity_type' => 'Refund',
            'entity_id' => 'rfd_1',
            'entity' => [
                'id' => '', // missing refund id
                'status' => 'refunded',
                'reason' => null,
                'decline_reason' => null,
                'payment_id' => 'pmt_1',
            ],
        ];

        $this->handler->handleEvent($event);

        $this->orderRepository->expects($this->never())
            ->method('save');
    }

    function testMissingPaymentId(): void
    {
        $event = [
            'id' => 'test event',
            'event_type' => 'refund:update',
            'entity_type' => 'Refund',
            'entity_id' => 'rfd_1',
            'entity' => [
                'id' => 'rfd_1',
                'status' => 'refunded',
                'reason' => null,
                'decline_reason' => null,
                'payment_id' => '', // missing payment id
            ],
        ];

        $this->handler->handleEvent($event);

        $this->orderRepository->expects($this->never())
            ->method('save');
    }

    function testNoTransactionsFound(): void
    {
        $event = [
            'id' => 'test event',
            'event_type' => 'refund:update',
            'entity_type' => 'Refund',
            'entity_id' => 'rfd_1',
            'entity' => [
                'id' => 'rfd_1',
                'status' => 'refunded',
                'reason' => null,
                'decline_reason' => null,
                'payment_id' => 'pmt_1',
            ],
        ];
        $this->filterBuilder->method('setField')->willReturnSelf();
        $this->filterBuilder->method('setValue')->willReturnSelf();
        $this->filterBuilder->method('create')->willReturn($this->createMock(Filter::class));

        $this->searchCriteriaBuilder->method('addFilters')->willReturnSelf();
        $this->searchCriteriaBuilder->method('create')->willReturn($this->createMock(SearchCriteriaInterface::class));

        $transactionSearchResults = $this->createMock(TransactionSearchResultInterface::class);
        $this->transactionRepository->method('getList')->willReturn($transactionSearchResults);

        $transactionSearchResults->method('getItems')->willReturn([]); // no transactions

        $this->handler->handleEvent($event);

        $this->orderRepository->expects($this->never())
            ->method('save');
    }

    function testExceptionHandling(): void
    {
        $event = [
            'id' => 'test event',
            'event_type' => 'refund:update',
            'entity_type' => 'Refund',
            'entity_id' => 'rfd_1',
            'entity' => [
                'id' => 'rfd_1',
                'status' => 'refunded',
                'reason' => null,
                'decline_reason' => null,
                'payment_id' => 'pmt_1',
            ],
        ];
        $this->filterBuilder->method('setField')->willReturnSelf();
        $this->filterBuilder->method('setValue')->willReturnSelf();
        $this->filterBuilder->method('create')->willReturn($this->createMock(Filter::class));

        $this->searchCriteriaBuilder->method('addFilters')->willReturnSelf();
        $this->searchCriteriaBuilder->method('create')->willReturn($this->createMock(SearchCriteriaInterface::class));

        $transactionSearchResults = $this->createMock(TransactionSearchResultInterface::class);
        $this->transactionRepository->method('getList')->willReturn($transactionSearchResults);

        $transaction = $this->createMock(TransactionInterface::class);
        $transactionSearchResults->method('getItems')->willReturn([$transaction]);

        $transaction->method('getOrderId')->willReturn('odr_1');

        $order = $this->createMock(OrderInterface::class);
        $this->orderRepository->method('get')->willThrowException(new \Exception('test exception'));

        $this->expectException(\Exception::class);

        $this->handler->handleEvent($event);
    }
}