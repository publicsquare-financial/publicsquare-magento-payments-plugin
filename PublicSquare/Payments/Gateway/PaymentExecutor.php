<?php
namespace PublicSquare\Payments\Gateway;

use Magento\Sales\Model\Order\Payment\Interceptor;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Model\QuoteRepository;
use PublicSquare\Payments\Api\Authenticated\PaymentCreateFactory;
use PublicSquare\Payments\Api\Authenticated\PaymentCaptureFactory;
use PublicSquare\Payments\Api\Authenticated\PaymentCancelFactory;
use PublicSquare\Payments\Logger\Logger;
use Magento\Sales\Api\TransactionRepositoryInterface;

class PaymentExecutor
{
	/**
	 * @var QuoteRepository
	 */
	private $quoteRepository;

	/**
	 * @var PaymentCreateFactory
	 */
	private $paymentCreateFactory;

	/**
	 * @var PaymentCaptureFactory
	 */
	private $paymentCaptureFactory;

	/**
	 * @var PaymentCancelFactory
	 */
	private $paymentCancelFactory;

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var array
	 */
	private $_commandSubject;

	/**
	 * @var TransactionRepositoryInterface
	 */
	private $transactionRepository;

	public function __construct(
		QuoteRepository $quoteRepository,
		PaymentCreateFactory $paymentCreateFactory,
		Logger $logger,
		TransactionRepositoryInterface $transactionRepository,
		PaymentCaptureFactory $paymentCaptureFactory,
		PaymentCancelFactory $paymentCancelFactory
	) {
		$this->quoteRepository = $quoteRepository;
		$this->paymentCreateFactory = $paymentCreateFactory;
		$this->logger = $logger;
		$this->transactionRepository = $transactionRepository;
		$this->paymentCaptureFactory = $paymentCaptureFactory;
		$this->paymentCancelFactory = $paymentCancelFactory;
	}

	public function setCommandSubject(array $commandSubject)
	{
		$this->_commandSubject = $commandSubject;
	}

	public function getCommandSubject()
	{
		return $this->_commandSubject;
	}

	public function executeAuthorize(array $commandSubject)
	{
		$this->logger->info("executeAuthorize");
		try {
			$this->createNewPayment($commandSubject, false);
		} catch (\Exception $e) {
			$this->throwUserFriendlyException($e);
		}
	}

	public function executeCapture(array $commandSubject)
	{
		$this->logger->info("executeCapture");
		try {
			$this->setCommandSubject($commandSubject);
	
			$payment = $this->getPayment();
			$transaction = $this->getTransaction();
			// PSQ payment id
			$transactionId = $transaction->getTxnId();
			$order = $payment->getOrder();
	
			$currentStatus = $payment->getAdditionalInformation("raw_details_info")[
				"status"
			];
	
			if (!$transactionId || !str_starts_with($transactionId, "pmt_")) {
				throw new CouldNotSaveException(
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
			} else {
				$response = $this->paymentCaptureFactory->create([
					"paymentId" => $transactionId,
					"amount" => $this->getAmount(),
					"externalId" => $order->getIncrementId() ?? ($order->getId() ?? ""),
				])->getResponseData();
				$this->setPaymentFromPSQResponse($payment, $response);
			}
		} catch (\Exception $e) {
			$this->throwUserFriendlyException($e);
		}
	}

	public function executeAuthorizeCapture(array $commandSubject)
	{
		$this->logger->info("executeAuthorizeCapture");
		try {
			$this->createNewPayment($commandSubject, true);
		} catch (\Exception $e) {
			$this->throwUserFriendlyException($e);
		}
	}

	public function executeCancel(array $commandSubject)
	{
		$this->logger->info("executeCancel");
		try {
			$this->setCommandSubject($commandSubject);
	
			$payment = $this->getPayment();
			$transaction = $this->getTransaction();
			// PSQ payment id
			$transactionId = $transaction->getTxnId();
	
			if (!$transactionId)
			{
				throw new CouldNotSaveException(__('Sorry, it is not possible to cancel this order.'));
			}
	
			$response = $this->paymentCancelFactory->create([
				'paymentId' => $transactionId
			])->getResponseData();
			$payment->setAdditionalInformation(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $response);
			$payment->setIsTransactionClosed(0);
		} catch (\Exception $e) {
			$this->throwUserFriendlyException($e);
		}
	}

	public function executeRefund(array $commandSubject)
	{
		$this->logger->info("executeRefund");
		try {
			$this->setCommandSubject($commandSubject);
		} catch (\Exception $e) {
			$this->throwUserFriendlyException($e);
		}
	}

	private function setPaymentFromPSQResponse(Interceptor $payment, $response)
	{
		$payment->setAdditionalInformation(
			\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
			$response
		);
		$payment->setLastTransId($response["id"]);
		$payment->setTransactionId($response["id"]);
		$payment->setIsTransactionClosed(0);
		$payment->setCcLast4(
				$response["payment_method"]["card"]["last4"]
		);
		$payment->setCcType(
				$response["payment_method"]["card"]["brand"]
		);
		$payment->setCcExpMonth(
				$response["payment_method"]["card"]["exp_month"]
		);
		$payment->setCcExpYear(
				$response["payment_method"]["card"]["exp_year"]
		);
		$payment->setCcTransId($response["id"]);
	}

	private function getPaymentDO()
	{
		$commandSubject = $this->getCommandSubject();
		if (!$commandSubject || !$commandSubject['payment'] instanceof \Magento\Payment\Gateway\Data\PaymentDataObjectInterface) {
            throw new \InvalidArgumentException('$commandSubject not set');
        }

		return $commandSubject['payment'];
	}

	public function getPayment()
	{
		return $this->getPaymentDO()->getPayment();
	}

	public function getOrder()
	{
		return $this->getPaymentDO()->getOrder();
	}

	public function getQuote()
	{
		if ($order = $this->getOrder()) {
            return $this->quoteRepository->get($order->getQuoteId());
        } else {
            throw new \InvalidArgumentException('Payment or order not found');
        }
	}

	private function getAmount()
	{
		$commandSubject = $this->getCommandSubject();
		if (!$commandSubject || !isset($commandSubject['amount'])) {
            throw new \InvalidArgumentException('$commandSubject not set or amount is null');
        }

		return $this->_commandSubject['amount'];
	}

	public function getTransaction()
	{
		$tid = $this->getPayment()->getLastTransId();
        try {
            return $this->transactionRepository->get($tid);
        } catch (\Magento\Framework\Exception\InputException $e) {
			return null;
        }
	}

	public function createNewPayment(array $commandSubject, bool $capture)
	{
		$this->logger->info('createNewPayment');
		try {
			$this->setCommandSubject($commandSubject);

			// Load quote using repository
			$payment = $this->getPayment();
			$quote = $this->getQuote();
			$order = $payment->getOrder();
			$this->logger->info('order', [
				'order' => $order->getId(),
				'incrementId' => $order->getIncrementId(),
			]);

			$billingAddress = $quote->getBillingAddress();
			$shippingAddress = $quote->getShippingAddress();
			$emailToUse = $quote->getCustomerEmail();

			if ($quote->getIsVirtual()) {
				$shippingAddress = null;
			} else if (empty($shippingAddress->getFirstname()) || empty($shippingAddress->getLastname())) {
				$this->logger->warning('Shipping address first/last name is empty', ['quoteId' => $quote->getId(), 'quoteAddressId' => $shippingAddress->getId()]);
			}
            if ($cardId = $payment->getAdditionalInformation('cardId')) {
                $idempotencyKey = $payment->getAdditionalInformation('idempotencyKey');
                $response = $this->paymentCreateFactory->create([
                    "idempotencyKey" => $idempotencyKey,
                    "amount" => $this->getAmount(),
                    "cardId" => $cardId,
                    "capture" => $capture,
                    "phone" => $billingAddress->getTelephone(),
                    "email" => $emailToUse,
                    "shippingAddress" => $shippingAddress,
                    "billingAddress" => $billingAddress,
					"externalId" => $order->getIncrementId() ?? ($order->getId() ?? ""),
                ])->getResponseData();
                $this->setPaymentFromPSQResponse($payment, $response);
            }
        } catch (\Exception $e) {
            $this->throwUserFriendlyException($e);
        }
	}

	public function throwUserFriendlyException(\Exception $e)
	{
		throw new \Magento\Payment\Gateway\Command\CommandException(
			__('%1', $e->getMessage())
		);
	}
}

