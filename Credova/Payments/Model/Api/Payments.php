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
        Config $configHelper
    ) {
        $this->paymentsRequestFactory    = $paymentsRequestFactory;
        $this->checkoutSession           = $checkoutSession;
        $this->cartTotalRepository       = $cartTotalRepository;
        $this->cartManagement            = $cartManagement;
        $this->customerRepository        = $customerRepository;
        $this->storeManager              = $storeManager;
        $this->customerFactory           = $customerFactory;
        $this->quoteFactory              = $quoteFactory;
        $this->configHelper              = $configHelper;
    } //end __construct()

    /**
     * Creates an application in Financial and returns the public id
     *
     * @param string $card
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createPayment($card)
    {
        $quoteId = $this->checkoutSession->getQuoteId();
        $quote = $this->quoteFactory->create()->load($quoteId);
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();
        $phoneNumber = $billingAddress->getTelephone();
        $firstName = $billingAddress->getFirstName();
        $lastName = $billingAddress->getLastName();
        $email = $billingAddress->getEmail();

        // Find or create new customer
        $customer = $this->customerFactory->create();
        $store = $this->storeManager->getStore();
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail($email);
        if (!$customer->getEntityId()) {
            $customer->setWebsiteId($websiteId)->setStore($store)->setFirstname($firstName)->setLastname($lastName)->setEmail($email)->setPassword($email);
            $customer->save();
        }
        $customer = $this->customerRepository->getById($customer->getEntityId());
        $quote->assignCustomer($customer);

        $phoneNumber = str_replace(' ', '-', $phoneNumber);
        $phoneNumber = preg_replace('/\D+/', '', $phoneNumber);

        if (preg_match('/(\d{3})(\d{3})(\d{4})$/', $phoneNumber, $matches)) {
            $phoneNumber = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
        } else {
            $phoneNumber = $phoneNumber;
        }

        if (substr_count($phoneNumber, '-') == 3) {
            $phoneNumber = substr($phoneNumber, strpos($phoneNumber, "-") + 1);
        }

        $data = [
            'amount'    => 0,
            'currency'  => 'USD',
            'capture'   => $this->configHelper->getCaptureAction() === 'authorize',
            'payment_method' => [
                'card'          => $card
            ],
            'customer'  => [
                'id'            => '',
                'business_name' => '',
                'first_name'    => $billingAddress->getFirstName(),
                'last_name'     => $billingAddress->getLastName(),
                'email'         => $email,
                'phone'         => $phoneNumber
            ],
            'billing_details' => [
                'address_line_1'    => $billingAddress->getStreet()[0],
                'address_line_2'    => array_key_exists(1, $billingAddress->getStreet()) ? $billingAddress->getStreet()[1] : '',
                'city'              => $billingAddress->getCity(),
                'state'             => $billingAddress->getRegionId(),
                'postal_code'       => $billingAddress->getPostcode(),
                'country'           => $billingAddress->getCountryId(),
            ],
            'shipping_address' => [
                'address_line_1'    => $shippingAddress->getStreet()[0],
                'address_line_2'    => array_key_exists(1, $shippingAddress->getStreet()) ? $shippingAddress->getStreet()[1] : '',
                'city'              => $shippingAddress->getCity(),
                'state'             => $shippingAddress->getRegionId(),
                'postal_code'       => $shippingAddress->getPostcode(),
                'country'           => $shippingAddress->getCountryId(),
            ]
        ];
        
        $cartTotal = $this->cartTotalRepository->get($quoteId);
        
        foreach ($cartTotal->getItems() as $item) {
            // $data['products'][] = [
            //     'id'          => $item->getItemId(),
            //     'description' => $item->getName(),
            //     'quantity'    => $item->getQty(),
            //     'value'       => (float) $item->getBaseRowTotal(), // price * qty
            // ];
            $data['amount'] += $item->getBaseRowTotal();
        }
        $data['amount'] += $shippingAddress->getShippingRateByCode($shippingAddress->getShippingMethod())->getData('price');

        if ($this->checkoutSession->getCheckoutState() == 'multishipping_overview') {
            $shipping_amount = $tax_amount = $discount_amount = 0;
            foreach ($quote->getAllShippingAddresses() as $address) {
                $addressValidation = $address->validate();
                if ($addressValidation !== true) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Verify the shipping address information and continue.')
                    );
                }
                $method = $address->getShippingMethod();
                $rate   = $address->getShippingRateByCode($method);

                $shipping_amount += $rate->getData('price');
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
                $data['amount'] += $shipping_amount;
            }
            if ($tax_amount != 0) {
                // $data['products'][] = [
                //     'id'          => 'tax',
                //     'description' => 'Tax',
                //     'quantity'    => '1',
                //     'value'       => (float) $tax_amount, // price * qty
                // ];
                $data['amount'] += $tax_amount;
            }
            if ($discount_amount != 0) {
                // $data['products'][] = [
                //     'id'          => 'discount',
                //     'description' => 'Discount',
                //     'quantity'    => '1',
                //     'value'       => (float)  -$discount_amount, // price * qty
                // ];
                $data['amount'] -= $discount_amount;
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
        $data['amount'] = $data['amount'] * 100;

        /*
        @var \Credova\Payments\Api\Authenticated\Payments $request
         */
        $request  = $this->paymentsRequestFactory->create(['payment' => $data]);
        $response = $request->getResponseData();


        if (!array_key_exists('id', $response)) {
            // TODO: Properly handle API errors
            // throw new \Exception($response);
            throw new \Magento\Framework\Exception\CouldNotSaveException(
                __(
                    implode(",", $response['errors'])
                )
            );
        }

        $quote->setPaymentMethod(static::PAYMENT_METHOD);
        $quote->setCredovaPublicId($response['id']);
        $quote->setInventoryProcessed(false);
        $quote->save();

        // Set Sales Order Payment
        $quote->getPayment()->importData(['method' => static::PAYMENT_METHOD]);

        // Collect Totals & Save Quote
        $quote->collectTotals()->save();

        // Create Order From Quote
        $orderId = $this->cartManagement->placeOrder($quote->getId());

        if ($orderId) {
            $result['order_id'] = $orderId;
        } else {
            $result = ['error' => 1, 'msg' => 'Your custom message'];
        }
        return $result;
    } //end createApplication()
} //end class
