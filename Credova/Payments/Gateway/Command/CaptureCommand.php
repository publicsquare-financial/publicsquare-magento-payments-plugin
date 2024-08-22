<?php

namespace Credova\Payments\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;

class CaptureCommand implements CommandInterface
{
    public function execute(array $commandSubject)
    {
        $payment = $commandSubject['payment']->getPayment();
        $amount = $commandSubject['amount'];
        
        $token = $payment->getAdditionalInformation('credova_token');
        
        // Use the token to make the API call to Credova for capture
        // Implement the actual API call here
        
        // If successful, set the transaction ID and mark as captured
        $payment->setTransactionId('unique_transaction_id');
        $payment->setIsTransactionClosed(0);
    }
}