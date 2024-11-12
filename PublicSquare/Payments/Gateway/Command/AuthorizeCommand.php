<?php

namespace PublicSquare\Payments\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Framework\Exception\LocalizedException;
use PublicSquare\Payments\Api\Authenticated\PaymentAuthorizeFactory;

class AuthorizeCommand implements CommandInterface
{
    /**
     * @var \PublicSquare\Payments\Api\Authenticated\PaymentAuthorizeFactory
     */
    private $paymentsRequestFactory;

    public function __construct(PaymentAuthorizeFactory $paymentsRequestFactory) {
        $this->paymentsRequestFactory = $paymentsRequestFactory;
    }

    public function execute(array $commandSubject)
    {
        // Implement authorization logic here
        $payment = $commandSubject['payment']->getPayment();
        $amount = $commandSubject['amount'] * 100;
        // throw new LocalizedException(__('AuthorizeCommand => '.json_encode($commandSubject).' '.$payment->getLastTransId()));
    }
}
