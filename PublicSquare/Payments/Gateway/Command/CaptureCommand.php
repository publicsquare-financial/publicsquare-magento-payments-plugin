<?php

namespace PublicSquare\Payments\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use PublicSquare\Payments\Logger\Logger;
use PublicSquare\Payments\Gateway\PaymentExecutor;

class CaptureCommand implements CommandInterface
{
    /**
     * @var \PublicSquare\Payments\Logger\Logger
     */
    private $logger;

    /**
     * @var \PublicSquare\Payments\Gateway\PaymentExecutor
     */
    private $paymentExecutor;

    public function __construct(
        Logger $logger,
        PaymentExecutor $paymentExecutor
    ) {
        $this->logger = $logger;
        $this->paymentExecutor = $paymentExecutor;
    }

    public function execute(array $commandSubject)
    {
        $payment = $commandSubject["payment"]->getPayment();
        $amount = ceil($commandSubject["amount"] * 100);
        // throw new LocalizedException(__('CaptureCommand => '.json_encode(get_object_vars($payment)).' '.$payment->getLastTransId().' '.json_encode($payment->getOrder()->getId())));
        $tid = $payment->getLastTransId();
        $this->logger->info('transaction', [
            "tid" => $tid,
        ]);
        if ($commandSubject["amount"] > 0) {
            $payment = $commandSubject["payment"]->getPayment();
            $tid = $payment->getLastTransId();
            $this->logger->info('getLastTransId', [
                'tid' => $tid,
            ]);
            $this->paymentExecutor->setCommandSubject($commandSubject);
            $transaction = $this->paymentExecutor->getTransaction();
            $this->logger->info('getTransaction', [
                'payment_id' => $transaction,
            ]);
    
            if ($transaction) {
                // Capture the payment
                $this->paymentExecutor->executeCapture($commandSubject);
            } else {
                // Authorize and capture the payment
                $this->paymentExecutor->executeAuthorizeCapture($commandSubject);
            }
        }
    }
}
