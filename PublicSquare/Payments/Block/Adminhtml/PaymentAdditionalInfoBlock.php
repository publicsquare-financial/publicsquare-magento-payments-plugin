<?php

namespace PublicSquare\Payments\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Sales\Model\ResourceModel\Order;

class PaymentAdditionalInfoBlock extends Template
{
    private array $whitelist = [
        'psq_refund_id',
        'psq_settlement_id',
    ];
    protected $_coreRegistry = null;

    public function __construct(\Magento\Backend\Block\Template\Context $context,
                                \Magento\Framework\Registry             $registry,
                                array                                   $data = [])
    {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
    }

    public function getOrder()
    {
        $viewType = $this->getData('viewType');
        $order = null;
        switch ($viewType) {
            case 'order':
                $order = $this->_coreRegistry->registry('current_order');
                break;
            case 'creditmemo':
                $order = $this->_coreRegistry->registry('current_creditmemo')->getOrder();
                break;
            case 'invoice':
                $order = $this->_coreRegistry->registry('current_invoice')->getOrder();
                break;
            default:
                $this->_logger->warning('PublicSquare: No view type set for PaymentAdditionalInfoBlock! Defaulting to order view type.');
                return $this->_coreRegistry->registry('current_order');
        }
        $this->_logger->info(
            'PublicSquare: Got order for view type: ' . $viewType,
            [
                'order' => $order?->getId(),
                'viewType' => $viewType,
            ],
        );
        return $order;
    }

    function paymentAdditionalInfo(): array
    {
        return array_filter(
            $this->getOrder()?->getPayment()?->getAdditionalInformation() ?? [],
            function ($value, $key) {
                return in_array($key, $this->whitelist, false);
            },
            ARRAY_FILTER_USE_BOTH,
        );
    }

    function isVisible(): bool
    {
        return count($this->paymentAdditionalInfo()) > 0;
    }
}