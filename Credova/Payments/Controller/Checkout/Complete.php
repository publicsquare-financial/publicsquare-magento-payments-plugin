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
        return $this->_redirect('checkout/onepage/success');
    }
}
