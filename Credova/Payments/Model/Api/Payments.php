<?php

/**
 * Application
 *
 * @category  Credova
 * @package   Credova_Payments
 * @author    Credova <info@credova.com>
 * @copyright 2024 Credova
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://credova.com/
 */

namespace Credova\Payments\Model\Api;

use Credova\Payments\Api\PaymentsInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Quote\Model\QuoteFactory;
use Credova\Payments\Helper\Config;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Payment\Transaction\Builder;

class Payments implements PaymentsInterface
{
    const PAYMENT_METHOD = "credova_payments";

    /**
     * @var \Credova\Payments\Api\Authenticated\PaymentsFactory
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
     * Exclude segment from CartTotal
     *
     * @var string[]
     */
    private $excludeTotalSegments = [
        TotalsInterface::KEY_GRAND_TOTAL,
        TotalsInterface::KEY_SUBTOTAL,
    ];

    public function __construct(
        \Credova\Payments\Api\Authenticated\PaymentsFactory $paymentsRequestFactory,
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
        Builder $transactionBuilder
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
    } //end __construct()

    /**
     * Creates an application in Financial and returns the public id
     *
     * @param string $cardId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createPayment($cardId)
    {
        $quoteId = $this->checkoutSession->getQuoteId();
        $quote = $this->quoteFactory->create()->load($quoteId);
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();
        $phoneNumber = $billingAddress->getTelephone();
        $firstName = $billingAddress->getFirstName();
        $lastName = $billingAddress->getLastName();
        $email = $billingAddress->getEmail();
        $capture = $this->configHelper->getCaptureAction() === "sale";

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
        $customer = $this->customerRepository->getById(
            $customer->getEntityId()
        );
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
                "id" => "",
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

        foreach ($cartTotal->getItems() as $item) {
            // $data['products'][] = [
            //     'id'          => $item->getItemId(),
            //     'description' => $item->getName(),
            //     'quantity'    => $item->getQty(),
            //     'value'       => (float) $item->getBaseRowTotal(), // price * qty
            // ];
            $data["amount"] += $item->getBaseRowTotal();
        }
        $data["amount"] += $shippingAddress
            ->getShippingRateByCode($shippingAddress->getShippingMethod())
            ->getData("price");

        if (
            $this->checkoutSession->getCheckoutState() ==
            "multishipping_overview"
        ) {
            $shipping_amount = $tax_amount = $discount_amount = 0;
            foreach ($quote->getAllShippingAddresses() as $address) {
                $addressValidation = $address->validate();
                if ($addressValidation !== true) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __(
                            "Verify the shipping address information and continue."
                        )
                    );
                }
                $method = $address->getShippingMethod();
                $rate = $address->getShippingRateByCode($method);

                $shipping_amount += $rate->getData("price");
            }

            foreach ($cartTotal->getItems() as $item) {
                $tax_amount += $item->getTaxAmount();
                $discount_amount += $item->getDiscountAmount();
            }
            if ($shipping_amount != 0) {
                // $data['products'][] = [
                //     'id'          => 'shipping',
                //     'description' => 'Shipping & Handling',
                //     'quantity'    => '1',
                //     'value'       => (float) $shipping_amount, // price * qty
                // ];
                $data["amount"] += $shipping_amount;
            }
            if ($tax_amount != 0) {
                // $data['products'][] = [
                //     'id'          => 'tax',
                //     'description' => 'Tax',
                //     'quantity'    => '1',
                //     'value'       => (float) $tax_amount, // price * qty
                // ];
                $data["amount"] += $tax_amount;
            }
            if ($discount_amount != 0) {
                // $data['products'][] = [
                //     'id'          => 'discount',
                //     'description' => 'Discount',
                //     'quantity'    => '1',
                //     'value'       => (float)  -$discount_amount, // price * qty
                // ];
                $data["amount"] -= $discount_amount;
            }
        } else {
            // foreach ($cartTotal->getTotalSegments() as $segment) {
            //     if (!in_array($segment->getCode(), $this->getExcludeSegments())) {
            //         if ($segment->getValue() != 0) {
            //             $data['products'][] = [
            //                 'id'          => $segment->getCode(),
            //                 'description' => $segment->getTitle() ? $segment->getTitle() : $segment->getCode(),
            //                 'quantity'    => 1,
            //                 'value'       => (float) $segment->getValue(),
            //             ];
            //         }
            //     }
            // }
        }
        $data["amount"] = $data["amount"] * 100;

        /*
        @var \Credova\Payments\Api\Authenticated\Payments $request
         */
        $request = $this->paymentsRequestFactory->create(["payment" => $data]);
        $response = $request->getResponseData();

        if (!array_key_exists("id", $response)) {
            // TODO: Properly handle API errors
            // throw new \Exception($response);
            throw new \Magento\Framework\Exception\CouldNotSaveException(
                __(implode(",", $response["errors"]))
            );
        }

        $quote->setPaymentMethod(static::PAYMENT_METHOD);
        $quote->setCredovaPublicId($response["id"]);
        $quote->setInventoryProcessed(false);
        $quote->save();

        // Set Sales Order Payment
        $payment = $quote->getPayment();
        $payment->importData(["method" => static::PAYMENT_METHOD])->setLastTransId($response['id'])->save();

        // Collect Totals & Save Quote
        $quote->collectTotals()->save();

        // Create Order From Quote
        $orderId = $this->cartManagement->placeOrder($quote->getId());
        $order = $this->orderRepository->get($orderId);

        // Create transaction
        $transactionId = $this->createTransaction($order, $response, );

        $this->invoiceOrder($order, $transactionId, $capture, true);

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
        $transactionType = $capture ? \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE : \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH;
        try {
            //get payment object from order object
            $payment = $order->getPayment();
            $payment->setLastTransId($paymentData['id']);
            $payment->setTransactionId($paymentData['id']);
            $payment->setIsTransactionClosed($capture);
            $payment->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData]
            );
            $formatedPrice = $order->getBaseCurrency()->formatTxt(
                $order->getGrandTotal()
            );
            $message = __('The authorized amount is %1.', $formatedPrice);
            //get the object of builder class
            $trans = $this->transactionBuilder;
            $transaction = $trans->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($paymentData['id'])
                ->setAdditionalInformation(
                    [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData]
                )
                ->setFailSafe(true)
                //build method creates the transaction and returns the object
                ->build($transactionType);
            $payment->addTransactionCommentsToOrder(
                $transaction,
                $message
            );
            $payment->setParentTransactionId(null);
            $payment->save();
            $order->save();
            return  $transaction->save()->getTransactionId();
        } catch (Exception $e) {
            //log errors here
        }
    }
} //end class
