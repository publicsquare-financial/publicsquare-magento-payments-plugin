<?php

namespace PublicSquare\Payments\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use PublicSquare\Payments\Api\Authenticated\PaymentRefundFactory;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandException;

class RefundCommand implements CommandInterface
{
    /**
     * @var PaymentRefundFactory
     */
    private $paymentRefundFactory;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    protected $logger;

    public function __construct(PaymentRefundFactory $paymentRefundFactory, TransactionRepositoryInterface $transactionRepository, \PublicSquare\Payments\Logger\Logger $logger,) {
        $this->paymentRefundFactory = $paymentRefundFactory;
        $this->transactionRepository = $transactionRepository;
        $this->logger = $logger;
    }

    public function execute(array $commandSubject)
    {
        $payment = $commandSubject['payment']->getPayment();
        $amount = $commandSubject['amount'] * 100;
        $transactionId = $this->getTransactionId($payment);

        if (!$transactionId)
        {
            throw new CommandException(__('Sorry, it is not possible to invoice this order because the payment is still pending.'));
        }

        try
        {
            $this->paymentRefundFactory->create([
                'paymentId' => $transactionId,
                'amount' => $amount
            ])->getResponse();
        }
        catch (\Exception $e)
        {
            $this->logger->error('Refund failed', ['exception' => $e]);
            throw new CommandException(__('Sorry, refund failed. '));
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

        preg_match('/pmt_[a-zA-Z0-9]+/', $transactionId, $matches);

        if (empty($matches))
        {
            throw new CommandException(__("The payment can only be refunded via the PublicSquare Dashboard. You can retry in offline mode instead."));
        }

        return $matches[0];
    }
}