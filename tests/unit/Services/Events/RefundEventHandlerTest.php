<?php

namespace PublicSquare\Payments\Test\Unit\Services\Events;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\Data\TransactionSearchResultInterface;
use Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use PHPUnit\Framework\TestCase;
use PublicSquare\Payments\Api\Constants;
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
            'event_type' => 'refund:updated',
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

        $searchResultsFactory = $this->createMock(TransactionSearchResultInterfaceFactory::class);

        $transactionSearchResults = $this->createMock(TransactionSearchResultInterface::class);
        // $searchResultsFactory->method('create')->willReturn($transactionSearchResults);
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


        $this->handler->handleEvent($event);

        // $this->assertEquals('rfd_1', $additionalData[Constants::REFUND_ID_KEY]);
        $this->orderRepository->expects($this->once())
            ->method('save')
           ->with($order);
    }
}