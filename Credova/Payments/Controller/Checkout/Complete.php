<?php

namespace Credova\Payments\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Credova\Financial\Model\CredovaSalesOrderFactory;
use Magento\Multishipping\Model\Checkout\Type\Multishipping\State;

class Complete extends \Magento\Framework\App\Action\Action
{
    protected $checkoutSession;
    protected $logger;
    protected $messageManager;
    protected $orderFactory;
    protected $maskedQuoteIdToQuoteId;
    protected $credovaSalesOrderFactory;
    protected $multistate;
    protected $_session;

    /***
     * Constructor @param \Magento\Framework\App\Action\Context  $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Api\Data\OrderInterfaceFactory $orderFactory,
        ManagerInterface $messageManager,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        \Credova\Payments\Logger\Logger $logger,
        CredovaSalesOrderFactory $credovaSalesOrder,
        State $multistate,
        \Magento\Framework\Session\Generic $session
    ) {
        parent::__construct($context);
        $this->checkoutSession       = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->messageManager = $messageManager;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->logger             = $logger;
        $this->credovaSalesOrderFactory     = $credovaSalesOrder;
        $this->multistate = $multistate;
        $this->_session = $session;
    }
    public function execute()
    {
        $this->logger->debug("====Redirect Controller Start=====");

        $data = $this->getRequest()->getParams();
        if (isset($data['refergues'])) {
            $maskedHashId = $data['refergues'];
            $quoteId = $this->maskedQuoteIdToQuoteId->execute($maskedHashId);
        }
        if (isset($data['refercust'])) {
            $quoteId = $data['refercust'];
        }
        sleep(3);
        $this->logger->debug("quoteId :-" . $quoteId);
        $crdvModel = $this->credovaSalesOrderFactory->create();
        $credovaCollection = $crdvModel->getCollection()->addFieldToFilter('quote_id', $quoteId)->getFirstItem();
        if (is_null($credovaCollection->getIncrementId())) {
            $this->logger->debug("========WAITING FOR 5s - status =========");
            sleep(5);
            $credovaCollection = $crdvModel->getCollection()->addFieldToFilter('quote_id', $quoteId)->getFirstItem();
            if (is_null($credovaCollection->getIncrementId())) {
                return $this->_redirect('credova/standard/cancel');
            }
        }
        $orderId = $credovaCollection->getIncrementId();
        $this->logger->debug($orderId);
        if (!is_null($orderId)) {
            if ($credovaCollection->getIsMultishipping()) {
                $orderId = [];
                $this->logger->debug("==== Redirect Controller Multiship =====");
                $credovaCollection = $crdvModel->getCollection()->addFieldToFilter('quote_id', $quoteId);
                foreach ($credovaCollection as $order) {
                    $orderIds[] = $order->getIncrementId();
                    $orderId[$order->getOrderId()] = $order->getIncrementId();
                }
                // Check if the quote is multishipping
                if ($orderIds) {
                    $this->multistate->setCompleteStep(State::STEP_OVERVIEW);
                    $this->multistate->setActiveStep(State::STEP_SUCCESS);
                    $this->checkoutSession->setDisplaySuccess(true);
                    $this->checkoutSession->clearQuote();
                    $this->_session->setOrderIds($orderId);

                    $orderIds = implode(",", $orderIds);
                    // Redirect to the multiship checkout success page with the order ids as a query parameter
                    return $this->_redirect("multishipping/checkout/success?order_ids=" . $orderIds);
                } else {
                    $this->messageManager->addNotice(("No order found"));
                    return $this->_redirect('checkout/cart');
                }
            } else {
                $order = $this->orderFactory->create()->loadByIncrementId($orderId);
                if (!is_null($order->getId())) {
                    $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
                    $this->checkoutSession->setLastQuoteId($order->getQuoteId());
                    $this->checkoutSession->setLastOrderId($order->getEntityId());
                    $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
                    $this->checkoutSession->setCartWasUpdated(false);

                    return $this->_redirect('checkout/onepage/success');
                } else {
                    $this->messageManager->addNotice(("No order found"));
                    return $this->_redirect('checkout/cart');
                }
            }
        } else {
            $this->logger->debug("========WAITING FOR 5s - order =========");
            sleep(5);
            $crdvModel = $this->credovaSalesOrderFactory->create();
            $credovaCollection = $crdvModel->getCollection()->addFieldToFilter('quote_id', $quoteId)->getFirstItem();
            $orderId = $credovaCollection->getIncrementId();
            $this->logger->debug($orderId);
            if (!is_null($orderId)) {
                $order = $this->orderFactory->create()->loadByIncrementId($orderId);
                if ($credovaCollection->getIsMultishipping()) {
                    $orderId = [];
                    $this->logger->debug("==== Redirect Controller Multiship =====");
                    $credovaCollection = $crdvModel->getCollection()->addFieldToFilter('quote_id', $quoteId);
                    foreach ($credovaCollection as $order) {
                        $orderIds[] = $order->getIncrementId();
                        $orderId[$order->getOrderId()] = $order->getIncrementId();
                    }
                    // Check if the quote is multishipping
                    if ($orderIds) {
                        $this->multistate->setCompleteStep(State::STEP_OVERVIEW);
                        $this->multistate->setActiveStep(State::STEP_SUCCESS);
                        $this->checkoutSession->setDisplaySuccess(true);
                        $this->checkoutSession->clearQuote();
                        $this->_session->setOrderIds($orderId);

                        $orderIds = implode(",", $orderIds);
                        // Redirect to the multiship checkout success page with the order ids as a query parameter
                        return $this->_redirect("multishipping/checkout/success?order_ids=" . $orderIds);
                    } else {
                        $this->messageManager->addNotice(("No order found"));
                        return $this->_redirect('checkout/cart');
                    }
                } else {
                    $order = $this->orderFactory->create()->loadByIncrementId($orderId);
                    if (!is_null($order->getId())) {
                        $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
                        $this->checkoutSession->setLastQuoteId($order->getQuoteId());
                        $this->checkoutSession->setLastOrderId($order->getEntityId());
                        $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
                        $this->checkoutSession->setCartWasUpdated(false);
                        return $this->_redirect('checkout/onepage/success');
                    } else {
                        $this->messageManager->addNotice(("No order found"));
                        return $this->_redirect('checkout/cart');
                    }
                }
            }
        }
    }
}
