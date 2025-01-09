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
        $this->paymentExecutor->setCommandSubject($commandSubject);
        $transaction = $this->paymentExecutor->getTransaction();

        if ($transaction) {
            // Capture the payment
            $this->paymentExecutor->executeCapture($commandSubject);
        } else {
            // Authorize and capture the payment
            $this->paymentExecutor->executeAuthorizeCapture($commandSubject);
        }
    }
}
