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
use Credova\Payments\Api\Data;
use Credova\Payments\Helper\Config;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;

class Payments implements PaymentsInterface
{

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Credova\Payments\Api\Authenticated\PaymentsFactory
     */
    private $applicationRequestFactory;

    /**
     * @var Config
     */
    private $configHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var CartTotalRepositoryInterface
     */
    protected $cartTotalRepository;

    /**
     * @var CartRepositoryInterface
     */
    public $quoteRepository;

    /**
     * @var StockRegistryInterface|null
     */
    private $stockRegistry;

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
        \Credova\Payments\Api\Authenticated\PaymentsFactory $applicationRequestFactory,
        Config $configHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\UrlInterface $urlBuilder,
        CartTotalRepositoryInterface $cartTotalRepository,
        CartRepositoryInterface $quoteRepository,
        StockRegistryInterface $stockRegistry
    ) {
        $this->applicationRequestFactory = $applicationRequestFactory;
        $this->configHelper              = $configHelper;
        $this->checkoutSession           = $checkoutSession;
        $this->urlBuilder                = $urlBuilder;
        $this->cartTotalRepository       = $cartTotalRepository;
        $this->quoteRepository           = $quoteRepository;
        $this->stockRegistry = $stockRegistry;
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
        $quote = $this->quoteRepository->get($quoteId);
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $billingAddress;
        $customer = $quote->getCustomer();
        $phoneNumber = $billingAddress->getTelephone();

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
            'capture'   => true,
            'payment_method' => [
                'card'          => $card
            ],
            'customer'  => [
                'id'            => '',
                'business_name' => '',
                'first_name'    => $customer->getFirstName(),
                'last_name'     => $customer->getLastName(),
                'email'         => $customer->getEmail(),
                'phone'         => $phoneNumber
            ],
            'billing_details' => [
                'address_line_1'    => $billingAddress->getStreet()[0],
                'address_line_2'    => $billingAddress->getStreet()[1],
                'city'              => $billingAddress->getCity(),
                'state'             => $billingAddress->getRegion(),
                'postal_code'       => $billingAddress->getPostcode(),
                'country'           => $billingAddress->getCountryId(),
            ],
            'shipping_address' => [
                'address_line_1'    => $shippingAddress->getStreet()[0],
                'address_line_2'    => $shippingAddress->getStreet()[1],
                'city'              => $shippingAddress->getCity(),
                'state'             => $shippingAddress->getRegion(),
                'postal_code'       => $shippingAddress->getPostcode(),
                'country'           => $shippingAddress->getCountryId(),
            ],
            'checkout_session' => $this->checkoutSession,
            'customer_' => $customer
        ];
        
        $cartTotal = $this->cartTotalRepository->get($quoteId);
        
        foreach ($cartTotal->getItems() as $item) {
            $data['products'][] = [
                'id'          => $item->getItemId(),
                'description' => $item->getName(),
                'quantity'    => $item->getQty(),
                'value'       => (float) $item->getBaseRowTotal(), // price * qty
            ];
            $data['amount'] += $item->getBaseRowTotal();
        }
        return [$data];

        if ($this->checkoutSession->getCheckoutState() == 'multishipping_overview') {
            $shipping_amount = $tax_amount = $discount_amount = 0;
            foreach ($this->checkoutSession->getQuote()->getAllShippingAddresses() as $address) {
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
                $data['products'][] = [
                    'id'          => 'shipping',
                    'description' => 'Shipping & Handling',
                    'quantity'    => '1',
                    'value'       => (float) $shipping_amount, // price * qty
                ];
            }
            if ($tax_amount != 0) {
                $data['products'][] = [
                    'id'          => 'tax',
                    'description' => 'Tax',
                    'quantity'    => '1',
                    'value'       => (float) $tax_amount, // price * qty
                ];
            }
            if ($discount_amount != 0) {
                $data['products'][] = [
                    'id'          => 'discount',
                    'description' => 'Discount',
                    'quantity'    => '1',
                    'value'       => (float)  -$discount_amount, // price * qty
                ];
            }
        } else {
            foreach ($cartTotal->getTotalSegments() as $segment) {
                if (!in_array($segment->getCode(), $this->getExcludeSegments())) {
                    if ($segment->getValue() != 0) {
                        $data['products'][] = [
                            'id'          => $segment->getCode(),
                            'description' => $segment->getTitle() ? $segment->getTitle() : $segment->getCode(),
                            'quantity'    => 1,
                            'value'       => (float) $segment->getValue(),
                        ];
                    }
                }
            }
        }

        /*
        @var \Credova\Payments\Api\Authenticated\Payments $request
         */
        $request  = $this->applicationRequestFactory->create(['payments' => $data]);
        $response = $request->getResponseData();

        if (!array_key_exists('id', $response)) {
            // TODO: Properly handle API errors
            // throw new \Exception($response);
            throw new \Magento\Framework\Exception\CouldNotSaveException(
                __(
                    implode(",", $response['errors'])
                )
            );

            return false;
        }


        $quote->setCredovaPublicId($response['id']);
        $quote->save();


        return $response;
    } //end createApplication()

    /**
     * Return callback url
     *
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->urlBuilder->getRouteUrl('credova/standard/response');
    }

    /**
     * Return redirect url
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->urlBuilder->getRouteUrl('credova/standard/redirect');
    }

    /**
     * Return redirect url
     *
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->urlBuilder->getRouteUrl('credova/standard/cancel');
    }

    /**
     * Exclude segments
     *
     * @return string[]
     */
    public function getExcludeSegments()
    {
        return $this->excludeTotalSegments;
    }

    protected function getUrii(): string
    {
        if ($this->configHelper->getEnvironment() == 1) {
            $host = rtrim('https://api-staging.credova.com/', '/');
        } else {
            $host = rtrim('https://api.credova.com/', '/');
        }

        return $host;
    } //end getUrii()

    /**
     * get stock status
     *
     * @param int $productId
     * @return bool
     */
    public function getStockStatus($productId)
    {
        /** @var StockItemInterface $stockItem */
        $stockItem = $this->stockRegistry->getStockItem($productId);
        $isInStock = $stockItem ? $stockItem->getIsInStock() : false;
        return $isInStock;
    }
} //end class
