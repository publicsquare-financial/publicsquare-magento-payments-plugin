<?php

namespace PublicSquare\Payments\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use PublicSquare\Payments\Api\Authenticated\RefundsFactory;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;

class RefundCommand implements CommandInterface
{
    /**
     * @var RefundsFactory
     */
    private $refundsRequestFactory;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    public function __construct(RefundsFactory $refundsRequestFactory, TransactionRepositoryInterface $transactionRepository) {
        $this->refundsRequestFactory = $refundsRequestFactory;
        $this->transactionRepository = $transactionRepository;
    }

    public function execute(array $commandSubject)
    {
        $payment = $commandSubject['payment']->getPayment();
        $amount = $commandSubject['amount'] * 100;
        $transactionId = $this->getTransactionId($payment);

        if (!$transactionId)
        {
            throw new LocalizedException(__('Sorry, it is not possible to invoice this order because the payment is still pending.'));
        }

        try
        {
            $request = $this->refundsRequestFactory->create(['refund' => [
                'payment_id' => $transactionId,
                'amount' => $amount
            ]]);
            $response = $request->getResponseData();
            if ($response['status'] != 'succeeded')
            {
                throw new LocalizedException(__('Sorry, refund failed.'));
            }
        }
        catch (\Exception $e)
        {
            // $this->helper->throwError($e->getMessage());
            throw new LocalizedException(__('Sorry, refund failed. '.$e->getMessage()));
        }
    }

    public function getTransactionId(\Magento\Payment\Model\InfoInterface $payment)
    {
        if ($payment->getCreditmemo() && $payment->getCreditmemo()->getInvoice())
            $invoice = $payment->getCreditmemo()->getInvoice();
        else
            $invoice = null;

        if ($payment->getRefundTransactionId())
        {
            $transactionId = $payment->getRefundTransactionId();
        }
        else if ($invoice && $invoice->getTransactionId())
        {
            $transactionId = $invoice->getTransactionId();
        }
        else
        {
            $transactionId = $payment->getLastTransId();
        }

        preg_match('/pmt_[a-zA-Z0-9]{22}/', $transactionId, $matches);

        if (empty($matches))
        {
            throw new LocalizedException(__("The payment can only be refunded via the PublicSquare Dashboard. You can retry in offline mode instead."));
        }

        return $matches[0];
    }
}