<?php
namespace Credova\Payments\Model\Api;

use Credova\Payments\Api\OrdersInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Credova\Payments\Helper\Config;
use Credova\Payments\Api\Authenticated\PaymentsFactory;

class Orders implements OrdersInterface
{
    private $resultFactory;
    private $checkoutSession;
    private $orderSender;
    private $transactionBuilder;
    private $config;
    private $paymentsFactory;
    private $request;

    public function __construct(
        ResultFactory $resultFactory,
        CheckoutSession $checkoutSession,
        OrderSender $orderSender,
        BuilderInterface $transactionBuilder,
        Config $config,
        PaymentsFactory $paymentsFactory,
        RequestInterface $request
    ) {
        $this->resultFactory = $resultFactory;
        $this->checkoutSession = $checkoutSession;
        $this->orderSender = $orderSender;
        $this->transactionBuilder = $transactionBuilder;
        $this->config = $config;
        $this->paymentsFactory = $paymentsFactory;
        $this->request = $request;
    }

    public function create($cardId)
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        
        try {
            $order = $this->checkoutSession->getLastRealOrder();
            $payment = $order->getPayment();
            
            $payment->setAdditionalInformation('credova_token', $cardId);
            
            // Create payment using Credova API
            $paymentsApi = $this->paymentsFactory->create();
            $billingAddress = $order->getBillingAddress();
            $shippingAddress = $order->getShippingAddress();
            $response = $paymentsApi->createPayment([
                'amount' => $order->getGrandTotal(),
                'currency' => $order->getOrderCurrencyCode(),
                'payment_method' => [
                    'card' => $cardId,
                ],
                'orderId' => $order->getIncrementId(),
                'customerId' => $order->getCustomerId() ?: 'guest',
                'customer' => [
                    'first_name' => $billingAddress->getFirstname(),
                    'last_name' => $billingAddress->getLastname(),
                    'email' => $order->getCustomerEmail(),
                    'phone' => $billingAddress->getTelephone(),
                ],
                'billing_details' => [
                    'address_line_1' => $billingAddress->getStreetLine(1),
                    'address_line_2' => $billingAddress->getStreetLine(2),
                    'city' => $billingAddress->getCity(),
                    'state' => $billingAddress->getRegionCode(),
                    'postal_code' => $billingAddress->getPostcode(),
                    'country' => $billingAddress->getCountryId(),
                ],
                'shipping_address' => [
                    'address_line_1' => $shippingAddress->getStreetLine(1),
                    'address_line_2' => $shippingAddress->getStreetLine(2),
                    'city' => $shippingAddress->getCity(),
                    'state' => $shippingAddress->getRegionCode(),
                    'postal_code' => $shippingAddress->getPostcode(),
                    'country' => $shippingAddress->getCountryId(),
                ],
            ]);

            if ($response && isset($response['id'])) {
                $payment->setTransactionId($response['id']);
                $payment->setIsTransactionClosed(0);
                $payment->setAdditionalInformation('credova_payment_id', $response['id']);

                // Create transaction
                $transaction = $this->transactionBuilder->setPayment($payment)
                    ->setOrder($order)
                    ->setTransactionId($response['id'])
                    ->setAdditionalInformation(['response' => $response])
                    ->setFailSafe(true)
                    ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

                $payment->addTransactionCommentsToOrder($transaction, __('Payment captured by Credova'));
                $payment->setParentTransactionId(null);

                // Set order status
                $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                // $order->setStatus($this->config->getOrderStatusAfterPayment());

                $order->save();
                $payment->save();
                $transaction->save();

                // Send order email
                $this->orderSender->send($order);

                return $result->setData(['success' => true]);
            } else {
                throw new \Exception(__('Failed to create payment with Credova'));
            }
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}