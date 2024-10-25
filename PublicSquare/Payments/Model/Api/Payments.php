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
use PublicSquare\Payments\Exception\SaveInvoiceException;
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
use PublicSquare\Payments\Exception\CreateTransactionException;
use Magento\Framework\App\ResourceConnection;

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

    /** @var \PublicSquare\Payments\Logger\Logger */
    protected $logger;

    /** @var ResourceConnection */
    protected $resourceConnection;

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
        \Magento\Checkout\Model\Session                          $checkoutSession,
        CartTotalRepositoryInterface                             $cartTotalRepository,
        CartManagementInterface                                  $cartManagement,
        CustomerRepositoryInterface                              $customerRepository,
        StoreManagerInterface                                    $storeManager,
        CustomerFactory                                          $customerFactory,
        Config                                                   $configHelper,
        InvoiceService                                           $invoiceService,
        OrderRepositoryInterface                                 $orderRepository,
        InvoiceRepositoryInterface                               $invoiceRepository,
        InvoiceSender                                            $invoiceSender,
        Builder                                                  $transactionBuilder,
        CreditCardTokenFactory                                   $creditCardTokenFactory,
        PaymentTokenRepositoryInterface                          $paymentTokenRepository,
        EncryptorInterface                                       $encryptor,
        PaymentTokenManagementInterface                          $tokenManagement,
        //\Psr\Log\LoggerInterface $logger,
        \PublicSquare\Payments\Logger\Logger                     $logger,
        ResourceConnection                                       $resourceConnection,
    )
    {
        $this->paymentsRequestFactory = $paymentsRequestFactory;
        $this->checkoutSession = $checkoutSession;
        $this->cartTotalRepository = $cartTotalRepository;
        $this->cartManagement = $cartManagement;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
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
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
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
        $hasCommitted = false;
        try {

            if (!$cardId && !$publicHash) {
                throw new \Magento\Framework\Exception\CouldNotSaveException(__('!$cardId && !$publicHash' . self::ERROR_MESSAGE));
            }

            // start transaction here.
            $this->resourceConnection->getConnection()->beginTransaction();

            $quote = $this->checkoutSession->getQuote();
            $billingAddress = $quote->getBillingAddress();
            $shippingAddress = $quote->getShippingAddress();
            $phoneNumber = $billingAddress->getTelephone();
            $email = $billingAddress->getEmail();
            $customer = $quote->getCustomer();

            // If the setting to lookup a customer by email is enabled, try to find an existing customer with that email
            if (!$customer && $this->configHelper->getGuestCheckoutCustomerLookup()) {
                try {
                    $customer = $this->customerRepository->get($email);
                    // If a customer is found, assign them to the quote
                    if ($customer) {
                        $quote->setCustomer($customer);
                    }
                } catch (\Magento\Framework\Exception\NoSuchEntityException $th) {}
            }

            // publicHash will be provided if the payment method is from the vault
            if ($publicHash && $customer) {
                try {
                    $cardId = $this->getCardIdFromPublicHash($publicHash, $customer->getId());
                    if (!$cardId) {
                        throw new \Magento\Framework\Exception\CouldNotSaveException(__('$publicHash && !$cardId' . self::ERROR_MESSAGE));
                    }
                } catch (\Throwable $th) {
                    throw new \Magento\Framework\Exception\CouldNotSaveException(__('Error retrieving card id from public hash' . self::ERROR_MESSAGE));
                }
            }

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
                // Authorize only, because the CaptureCommand will handle capturing the payment
                "capture" => false,
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

            $quote->getPayment()->importData(["method" => Config::CODE]);
            $quote->setPaymentMethod(Config::CODE)
                ->setInventoryProcessed(false)
                ->collectTotals()
                ->save();

            $data["amount"] = $quote->getGrandTotal() * 100;

            /*
            @var \PublicSquare\Payments\Api\Authenticated\Payments $request
             */
            $request = $this->paymentsRequestFactory->create(["payment" => $data]);
            $response = $request->getResponseData();

            if (!empty($response['status']) && $response['status'] == 'declined') {
                $errorMessage = !empty($response['declined_reason']) ? $response['declined_reason'] : 'Declined';
                throw new \Magento\Framework\Exception\CouldNotSaveException(
                    __($errorMessage)
                );
            }
            else if (!empty($response['status']) && $response['status'] == 400) {
                $errorMessage = !empty($response['detail']) ? $response['detail'] : 'Payment error';
                throw new \Magento\Framework\Exception\CouldNotSaveException(
                    __($errorMessage)
                );
            }
            else if (array_key_exists("errors", $response)) {
                throw new \Magento\Framework\Exception\CouldNotSaveException(
                    __(sprintf("The payment for order #%d cannot be processed. ", $orderId)) . __(implode(",", $response["errors"]))
                );
            }
            else if (array_key_exists("fraud_details", $response) && $response["fraud_details"]["decision"] === "reject") {
                $order->cancel()->save();
                throw new \Magento\Framework\Exception\CouldNotSaveException(
                    __(sprintf("The payment for order #%d cannot be processed.", $orderId))
                );
            }
            else if (!array_key_exists("id", $response)) {
                throw new \Magento\Framework\Exception\CouldNotSaveException(
                    __(implode(",", $response["errors"]))
                );
            }

            // Create Order From Quote
            $orderId = $this->cartManagement->placeOrder($quote->getId());
            $order = $this->orderRepository->get($orderId);

            // commit once we have a successful transaction
            $this->resourceConnection->getConnection()->commit();
            $hasCommitted = true;

            // Create transaction
            $transactionId = $this->createTransaction($order, $response);

            $this->invoiceOrder($order, $transactionId);

            if ($customer && $saveCard) {
                $this->savePaymentMethod($customer->getId(), $response['payment_method']);
            }

            if ($orderId) {
                $result["order_id"] = $orderId;
            } else {
                $result = ["error" => 1, "msg" => "Your custom message"];
            }
            return $result;
        } catch (\Exception $e) {
            if (!$hasCommitted) {
                $this->resourceConnection->getConnection()->rollBack();
            }
            $quote->setIsActive(1); // keep the items in the cart
            $quote->save();
            $this->logger->error($e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw $e;
        }
    } //end createApplication()

    private function invoiceOrder(
        $order,
        $transactionId,
        $save = true
    )
    {
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->setState(\Magento\Sales\Model\Order\Invoice::STATE_OPEN);
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);

        if ($transactionId) {
            $invoice->setTransactionId($transactionId);
            $order->getPayment()->setLastTransId($transactionId);
        }
        $invoice->register();

        if ($save) {
            try {
                $this->invoiceRepository->save($invoice);
                $this->orderRepository->save($order);
                $this->sendInvoiceEmail($invoice);
                $invoice->save();
            } catch (\Exception $e) {
                throw new SaveInvoiceException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return $invoice;
    }

    private function sendInvoiceEmail($invoice)
    {
        try {
            $this->invoiceSender->send($invoice);
            return true;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }

        return false;
    }

    public function createTransaction($order = null, $paymentData = array())
    {
        try {
            //get payment object from order object
            $payment = $order->getPayment();
            $payment->setLastTransId($paymentData['id']);
            $payment->setTransactionId($paymentData['id']);
            $payment->setIsTransactionClosed(0);
            $payment->setCcLast4($paymentData['payment_method']['card']['last4']);
            $payment->setCcType($paymentData['payment_method']['card']['brand']);
            $payment->setCcExpMonth($paymentData['payment_method']['card']['exp_month']);
            $payment->setCcExpYear($paymentData['payment_method']['card']['exp_year']);
            $payment->setCcTransId($paymentData['id']);
            $payment->setAdditionalInformation(Transaction::RAW_DETAILS, $paymentData);
            if (array_key_exists('fraud_details', $paymentData)) {
                if (array_key_exists('decision', $paymentData['fraud_details'])) {
                    $payment->setAdditionalInformation('fraud_decision', $paymentData['fraud_details']['decision']);
                }
                if (array_key_exists('rules', $paymentData['fraud_details'])) {
                    $payment->setAdditionalInformation('fraud_rules', $paymentData['fraud_details']['rules']);
                }
            }
            if (array_key_exists('avs_code', $paymentData['payment_method']['card'])) {
                $payment->setAdditionalInformation('avsCode', $paymentData['payment_method']['card']['avs_code']);
            }
            if (array_key_exists('cvv2_reply', $paymentData['payment_method']['card'])) {
                $payment->setAdditionalInformation('cvv2Reply', $paymentData['payment_method']['card']['cvv2_reply']);
            }
            //get the object of builder class
            $trans = $this->transactionBuilder;
            $transaction = $trans->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($paymentData['id'])
                ->setFailSafe(true)
                ->build(Transaction::TYPE_AUTH);
            $payment->setParentTransactionId(null);
            $payment->save();
            $order->save();
            return $transaction->save()->getTransactionId();
        } catch (Exception $e) {
            throw new CreateTransactionException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function savePaymentMethod($customerId, $paymentData)
    {
        try {
            $paymentToken = $this->creditCardTokenFactory->create();

            // Use the exp_month and exp_year to generate the expiration date
            $expirationDate = date('Y-m-t 23:59:59', strtotime($paymentData['card']['exp_year'] . '-' . $paymentData['card']['exp_month']));
            $paymentToken->setExpiresAt($expirationDate);
            $paymentToken->setGatewayToken($paymentData['card']['id']);
            $paymentToken->setTokenDetails(json_encode([
                'type' => $paymentData['card']['brand'],
                'maskedCC' => $paymentData['card']['last4'],
                'expirationDate' => $paymentData['card']['exp_month'] . '/' . $paymentData['card']['exp_year'],
            ]));
            $paymentToken->setIsActive(true);
            $paymentToken->setIsVisible(true);
            $paymentToken->setPaymentMethodCode(Config::CODE);
            $paymentToken->setWebsiteId($this->storeManager->getStore()->getWebsiteId());
            $paymentToken->setCustomerId($customerId);
            $paymentToken->setPublicHash($this->generatePublicHash($paymentToken));
            $this->paymentTokenRepository->save($paymentToken);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
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

    private function getCardIdFromPublicHash($publicHash, $customerId): string
    {
        $paymentToken = $this->tokenManagement->getByPublicHash($publicHash, $customerId);
        return $paymentToken->getGatewayToken();
    }
} //end class
