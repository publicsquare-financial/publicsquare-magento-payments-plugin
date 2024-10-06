<?php

/**
 * Application
 *
 * @category  PublicSquare
 * @package   PublicSquare_Payments
 * @author    PublicSquare <info@publicsquare.com>
 * @copyright 2024 PublicSquare
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://publicsquare.com/
 */

namespace PublicSquare\Payments\Model\Api;

use PublicSquare\Payments\Api\PaymentsInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Quote\Model\QuoteFactory;
use PublicSquare\Payments\Helper\Config;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Payment\Transaction\Builder;
use Magento\Vault\Model\CreditCardTokenFactory;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Vault\Api\PaymentTokenManagementInterface;

class Payments implements PaymentsInterface
{
    const ERROR_MESSAGE = 'Unfortunately, we were unable to process your payment. Please try again or contact support for assistance.';
    /**
     * @var \PublicSquare\Payments\Api\Authenticated\PaymentsFactory
     */
    private $paymentsRequestFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var CartTotalRepositoryInterface
     */
    protected $cartTotalRepository;

    /**
     * @var CartManagementInterface
     */
    protected $cartManagement;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var Config
     */
    private $configHelper;

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * @var InvoiceSender
     */
    protected $invoiceSender;

    /**
     * @var Builder
     */
    protected $transactionBuilder;

    /**
     * @var CreditCardTokenFactory
     */
    protected $creditCardTokenFactory;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    protected $paymentTokenRepository;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var PaymentTokenManagementInterface
     */
    protected $tokenManagement;

    /**
     * Exclude segment from CartTotal
     *
     * @var string[]
     */
    private $excludeTotalSegments = [
        TotalsInterface::KEY_GRAND_TOTAL,
        TotalsInterface::KEY_SUBTOTAL,
    ];

    public function __construct(
        \PublicSquare\Payments\Api\Authenticated\PaymentsFactory $paymentsRequestFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        CartTotalRepositoryInterface $cartTotalRepository,
        CartManagementInterface $cartManagement,
        CustomerRepositoryInterface $customerRepository,
        StoreManagerInterface $storeManager,
        CustomerFactory $customerFactory,
        QuoteFactory $quoteFactory,
        Config $configHelper,
        InvoiceService $invoiceService,
        OrderRepositoryInterface $orderRepository,
        InvoiceRepositoryInterface $invoiceRepository,
        InvoiceSender $invoiceSender,
        Builder $transactionBuilder,
        CreditCardTokenFactory $creditCardTokenFactory,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        EncryptorInterface $encryptor,
        PaymentTokenManagementInterface $tokenManagement
    ) {
        $this->paymentsRequestFactory = $paymentsRequestFactory;
        $this->checkoutSession = $checkoutSession;
        $this->cartTotalRepository = $cartTotalRepository;
        $this->cartManagement = $cartManagement;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->quoteFactory = $quoteFactory;
        $this->configHelper = $configHelper;
        $this->invoiceService = $invoiceService;
        $this->orderRepository = $orderRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->invoiceSender = $invoiceSender;
        $this->transactionBuilder = $transactionBuilder;
        $this->creditCardTokenFactory = $creditCardTokenFactory;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->encryptor = $encryptor;
        $this->tokenManagement = $tokenManagement;
    } //end __construct()

    /**
     * Creates an application in Financial and returns the public id
     *
     * @param string $cardId
     * @param bool $saveCard
     * @return string
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function createPayment($cardId = '', $saveCard = false, $publicHash = '')
    {
        if (!$cardId && !$publicHash) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(__('!$cardId && !$publicHash'.self::ERROR_MESSAGE));
        }

        $quoteId = $this->checkoutSession->getQuoteId();
        $quote = $this->quoteFactory->create()->load($quoteId);
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();
        $phoneNumber = $billingAddress->getTelephone();
        $firstName = $billingAddress->getFirstName();
        $lastName = $billingAddress->getLastName();
        $email = $billingAddress->getEmail();
        $capture = $this->configHelper->getPaymentAction() !== "authorize";

        // Find or create new customer
        $customer = $this->customerFactory->create();
        $store = $this->storeManager->getStore();
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail($email);
        if (!$customer->getEntityId()) {
            $customer
                ->setWebsiteId($websiteId)
                ->setStore($store)
                ->setFirstname($firstName)
                ->setLastname($lastName)
                ->setEmail($email)
                ->setPassword($email);
            $customer->save();
        }
        $customerId = $customer->getEntityId();
        
        // publicHash will be provided if the payment method is from the vault
        if ($publicHash) {
            try {
                $cardId = $this->getCardIdFromPublicHash($publicHash, $customerId);
                if (!$cardId) {
                    throw new \Magento\Framework\Exception\CouldNotSaveException(__('$publicHash && !$cardId'.self::ERROR_MESSAGE));
                }
            } catch (\Throwable $th) {
                throw new \Magento\Framework\Exception\CouldNotSaveException(__('Error retrieving card id from public hash'.self::ERROR_MESSAGE));
            }
        }
        
        $customer = $this->customerRepository->getById($customerId);
        $quote->assignCustomer($customer);

        $phoneNumber = str_replace(" ", "-", $phoneNumber);
        $phoneNumber = preg_replace("/\D+/", "", $phoneNumber);

        if (preg_match('/(\d{3})(\d{3})(\d{4})$/', $phoneNumber, $matches)) {
            $phoneNumber = $matches[1] . "-" . $matches[2] . "-" . $matches[3];
        } else {
            $phoneNumber = $phoneNumber;
        }

        if (substr_count($phoneNumber, "-") == 3) {
            $phoneNumber = substr($phoneNumber, strpos($phoneNumber, "-") + 1);
        }

        $data = [
            "amount" => 0,
            "currency" => "USD",
            "capture" => $capture,
            "payment_method" => [
                "card" => $cardId,
            ],
            "customer" => [
                "external_id" => "",
                "business_name" => "",
                "first_name" => $billingAddress->getFirstName(),
                "last_name" => $billingAddress->getLastName(),
                "email" => $email,
                "phone" => $phoneNumber,
            ],
            "billing_details" => [
                "address_line_1" => $billingAddress->getStreet()[0],
                "address_line_2" => array_key_exists(
                    1,
                    $billingAddress->getStreet()
                )
                    ? $billingAddress->getStreet()[1]
                    : "",
                "city" => $billingAddress->getCity(),
                "state" => $billingAddress->getRegionId(),
                "postal_code" => $billingAddress->getPostcode(),
                "country" => $billingAddress->getCountryId(),
            ],
            "shipping_address" => [
                "address_line_1" => $shippingAddress->getStreet()[0],
                "address_line_2" => array_key_exists(
                    1,
                    $shippingAddress->getStreet()
                )
                    ? $shippingAddress->getStreet()[1]
                    : "",
                "city" => $shippingAddress->getCity(),
                "state" => $shippingAddress->getRegionId(),
                "postal_code" => $shippingAddress->getPostcode(),
                "country" => $shippingAddress->getCountryId(),
            ],
        ];

        $cartTotal = $this->cartTotalRepository->get($quoteId);

        $quote->setPaymentMethod(Config::CODE);
        // $quote->setPublicSquarePublicId($response["id"]);
        $quote->setInventoryProcessed(false);
        $quote->save();

        // Set Sales Order Payment
        $payment = $quote->getPayment();
        $payment->importData(["method" => Config::CODE])->save();

        // Collect Totals & Save Quote
        $quote->collectTotals()->save();

        // Create Order From Quote
        $orderId = $this->cartManagement->placeOrder($quote->getId());

        $order = $this->orderRepository->get($orderId);

        $data["amount"] = $quote->getGrandTotal() * 100;

        $data["external_id"] = $order->getIncrementId();
        /*
        @var \PublicSquare\Payments\Api\Authenticated\Payments $request
         */
        $request = $this->paymentsRequestFactory->create(["payment" => $data]);
        $response = $request->getResponseData();

        if (!array_key_exists("id", $response)) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(
                __(implode(",", $response["errors"]))
            );
        }

        // Create transaction
        $transactionId = $this->createTransaction($order, $response, $capture);

        $this->invoiceOrder($order, $transactionId, $capture, true);

        if ($saveCard) {
            $this->savePaymentMethod($customerId, $response['payment_method']);
        }

        if ($orderId) {
            $result["order_id"] = $orderId;
        } else {
            $result = ["error" => 1, "msg" => "Your custom message"];
        }
        return $result;
    } //end createApplication()

    private function invoiceOrder(
        $order,
        $transactionId,
        $capture = false,
        $save = true
    ) {
        $invoice = $this->invoiceService->prepareInvoice($order);
        $captureType = $capture ? \Magento\Sales\Model\Order\Invoice::STATE_PAID : \Magento\Sales\Model\Order\Invoice::STATE_OPEN;
        $invoice->setRequestedCaptureCase($captureType);

        if ($transactionId) {
            $invoice->setTransactionId($transactionId);
            $order->getPayment()->setLastTransId($transactionId);
        }
        $invoice->register();

        if ($save) {
            $this->invoiceRepository->save($invoice);
            $this->orderRepository->save($order);
            $this->sendInvoiceEmail($invoice);
        }

        return $invoice;
    }

    private function sendInvoiceEmail($invoice) {
        try
        {
            $this->invoiceSender->send($invoice);
            return true;
        }
        catch (\Exception $e)
        {
            // $this->logError($e->getMessage(), $e->getTraceAsString());
        }

        return false;
    }

    public function createTransaction($order = null, $paymentData = array(), $capture = false)
    {
        $transactionType = $capture ? Transaction::TYPE_CAPTURE : Transaction::TYPE_AUTH;
        $amount = $order->getGrandTotal();
        try {
            //get payment object from order object
            $payment = $order->getPayment();
            $payment->setLastTransId($paymentData['id']);
            $payment->setTransactionId($paymentData['id']);
            $payment->setIsTransactionClosed($capture ? 1 : 0);
            $payment->setCcLast4($paymentData['payment_method']['card']['last4']);
            $payment->setCcType($paymentData['payment_method']['card']['brand']);
            $payment->setCcExpMonth($paymentData['payment_method']['card']['exp_month']);
            $payment->setCcExpYear($paymentData['payment_method']['card']['exp_year']);
            $payment->setCcTransId($paymentData['id']);
            $payment->setAdditionalInformation(Transaction::RAW_DETAILS, $paymentData);
            //get the object of builder class
            $trans = $this->transactionBuilder;
            $transaction = $trans->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($paymentData['id'])
                ->setFailSafe(true)
                //build method creates the transaction and returns the object
                ->build($transactionType);
            $payment->setParentTransactionId(null);
            $payment->save();
            $order->save();
            return  $transaction->save()->getTransactionId();
        } catch (Exception $e) {
            //log errors here
        }
    }

    private function savePaymentMethod($customerId, $paymentData) {
        $paymentToken = $this->creditCardTokenFactory->create();
        $paymentToken->setExpiresAt('2025-12-31 00:00:00');
        $paymentToken->setGatewayToken($paymentData['card']['id']);
        $paymentToken->setTokenDetails(json_encode([
            'type'              => 'visa',
            'maskedCC'          => $paymentData['card']['last4'],
            'expirationDate'    => $paymentData['card']['exp_month'].'/'.$paymentData['card']['exp_year'],
        ]));
        $paymentToken->setIsActive(true);
        $paymentToken->setIsVisible(true);
        $paymentToken->setPaymentMethodCode(Config::VAULT_CODE);
        $paymentToken->setWebsiteId($this->storeManager->getStore()->getWebsiteId());
        $paymentToken->setCustomerId($customerId);
        $paymentToken->setPublicHash($this->generatePublicHash($paymentToken));
        $this->paymentTokenRepository->save($paymentToken);
    }

    /**
     * Generate vault payment public hash
     *
     * @param PaymentTokenInterface $paymentToken
     * @return string
     */
    protected function generatePublicHash(PaymentTokenInterface $paymentToken)
    {
        $hashKey = $paymentToken->getGatewayToken();
        if ($paymentToken->getCustomerId()) {
            $hashKey = $paymentToken->getCustomerId();
        }

        $hashKey .= $paymentToken->getPaymentMethodCode()
            . $paymentToken->getType()
            . json_encode($paymentToken->getTokenDetails());

        return $this->encryptor->getHash($hashKey);
    }

    private function getCardIdFromPublicHash($publicHash, $customerId): string {
        $paymentToken = $this->tokenManagement->getByPublicHash($publicHash, $customerId);
        return $paymentToken->getGatewayToken();
    }
} //end class
