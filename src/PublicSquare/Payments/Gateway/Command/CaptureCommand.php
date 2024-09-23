<?php

namespace PublicSquare\Payments\Gateway\Command;

use ErrorException;
use Magento\Payment\Gateway\CommandInterface;
use PublicSquare\Payments\Api\Authenticated\PaymentCaptureFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\TransactionRepositoryInterface;

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

    public function __construct(PaymentCaptureFactory $paymentCaptureRequestFactory, TransactionRepositoryInterface $transactionRepository) {
        $this->paymentCaptureRequestFactory = $paymentCaptureRequestFactory;
        $this->transactionRepository = $transactionRepository;
    }

    public function execute(array $commandSubject)
    {
        $payment = $commandSubject['payment']->getPayment();
        $amount = $commandSubject['amount'] * 100;
        // throw new LocalizedException(__('CaptureCommand => '.json_encode(get_object_vars($payment)).' '.$payment->getLastTransId().' '.json_encode($payment->getOrder()->getId())));
        $tid = $payment->getLastTransId();
        $transaction = $this->transactionRepository->get($tid);
        // PSQ payment id
        $transactionId = $transaction->getTxnId();

        if (!$transactionId)
        {
            throw new LocalizedException(__('Sorry, it is not possible to invoice this order because the payment is still pending.'));
        }

        try
        {
            $request = $this->paymentCaptureRequestFactory->create(['payment' => [
                'payment_id' => $transactionId,
                'amount' => $amount
            ]]);
            $response = $request->getResponseData();
            if ($response['status'] != 'succeeded')
            {
                throw new LocalizedException(__('Sorry, capture failed 1.'.json_encode($response)));
            }
        }
        catch (\Exception $e)
        {
            // $this->helper->throwError($e->getMessage());
            throw new LocalizedException(__('Sorry, capture failed 2. amount: '.$amount.' error: '.$e->getMessage()));
        }
        
        // // Use the token to make the API call to PublicSquare for capture
        // // Implement the actual API call here
        
        // // If successful, set the transaction ID and mark as captured
        // // $payment->setTransactionId('unique_transaction_id');
        $payment->setIsTransactionClosed(0);
        $payment->save();
    }
}