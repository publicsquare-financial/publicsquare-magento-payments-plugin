<?php

namespace PublicSquare\Payments\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use PublicSquare\Payments\Gateway\PaymentExecutor;

class AuthorizeCommand implements CommandInterface
{
    /**
     * @var PaymentExecutor
     */
    private $paymentExecutor;

    public function __construct(
        PaymentExecutor $paymentExecutor
    ) {
        $this->paymentExecutor = $paymentExecutor;
    }

    public function execute(array $commandSubject)
    {
        $this->paymentExecutor->executeAuthorize($commandSubject);
    }
}
