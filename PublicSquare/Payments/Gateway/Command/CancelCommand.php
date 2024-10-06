<?php

namespace PublicSquare\Payments\Gateway\Command;

use ErrorException;
use Magento\Payment\Gateway\CommandInterface;
use PublicSquare\Payments\Api\Authenticated\PaymentCancelFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\TransactionRepositoryInterface;

class CancelCommand implements CommandInterface
{
    /**
     * @var \PublicSquare\Payments\Api\Authenticated\PaymentCancelFactory
     */
    private $paymentCancelRequestFactory;

    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    private $transactionRepository;
    
    public function __construct(PaymentCancelFactory $paymentCancelRequestFactory, TransactionRepositoryInterface $transactionRepository) {
        $this->paymentCancelRequestFactory = $paymentCancelRequestFactory;
        $this->transactionRepository = $transactionRepository;
    }

    public function execute(array $commandSubject)
    {
        $payment = $commandSubject['payment']->getPayment();
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
            $request = $this->paymentCancelRequestFactory->create(['payment' => [
                'payment_id' => $transactionId
            ]]);
            $response = $request->getResponseData();
            if ($response['status'] != 'cancelled')
            {
                throw new LocalizedException(__(json_encode($response)));
            } else {
                $payment->setAdditionalInformation(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $response);
            }
        }
        catch (\Exception $e)
        {
            // $this->helper->throwError($e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
        }
        
        $payment->setIsTransactionClosed(0);
        $payment->save();
    }
}