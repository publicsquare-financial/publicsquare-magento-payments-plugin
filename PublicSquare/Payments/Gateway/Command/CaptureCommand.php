<?php

namespace PublicSquare\Payments\Gateway\Command;

use ErrorException;
use Magento\Payment\Gateway\CommandInterface;
use PublicSquare\Payments\Api\Authenticated\PaymentCaptureFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\TransactionRepositoryInterface;
use PublicSquare\Payments\Logger\Logger;

class CaptureCommand implements CommandInterface
{
    /**
     * @var \PublicSquare\Payments\Api\Authenticated\PaymentCaptureFactory
     */
    private $paymentCaptureRequestFactory;

    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var \PublicSquare\Payments\Logger\Logger
     */
    private $logger;

    public function __construct(
        PaymentCaptureFactory $paymentCaptureRequestFactory,
        TransactionRepositoryInterface $transactionRepository,
        Logger $logger
    ) {
        $this->paymentCaptureRequestFactory = $paymentCaptureRequestFactory;
        $this->transactionRepository = $transactionRepository;
        $this->logger = $logger;
    }

    public function execute(array $commandSubject)
    {
        $payment = $commandSubject["payment"]->getPayment();
        $amount = ceil($commandSubject["amount"] * 100);
        // throw new LocalizedException(__('CaptureCommand => '.json_encode(get_object_vars($payment)).' '.$payment->getLastTransId().' '.json_encode($payment->getOrder()->getId())));
        $tid = $payment->getLastTransId();
        $transaction = $this->transactionRepository->get($tid);
        // PSQ payment id
        $transactionId = $transaction->getTxnId();
        $order = $payment->getOrder();

        $currentStatus = $payment->getAdditionalInformation("raw_details_info")[
            "status"
        ];

        if (!$transactionId || !str_starts_with($transactionId, "pmt_")) {
            throw new LocalizedException(
                __(
                    "Sorry, it is not possible to invoice this order because the payment is still pending."
                )
            );
        } elseif ($currentStatus != "requires_capture") {
            $this->logger->warning(
                sprintf(
                    "PSQ Payments capture warning - Payment %s status is not 'requires_capture' (it's '%s')",
                    $transactionId,
                    $currentStatus
                ),
                [
                    "payment" => $payment->getAdditionalInformation(
                        "raw_details_info"
                    ),
                ]
            );
        }

        try {
            $request = $this->paymentCaptureRequestFactory->create([
                "payment" => [
                    "payment_id" => $transactionId,
                    "amount" => $amount,
                    "external_id" =>
                        $order->getIncrementId() ?? ($order->getId() ?? ""),
                ],
            ]);
            $response = $request->getResponseData();
            if (!empty($response["status"]) && $response["status"] == "succeeded") {
                $payment->setAdditionalInformation(
                    \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
                    $response
                );
                $payment->setIsTransactionClosed(0);
                $payment->save();
            }
        } catch (\Exception $e) {
            throw new LocalizedException($e);
        }
    }
}
