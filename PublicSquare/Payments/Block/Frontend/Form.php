<?php

namespace PublicSquare\Payments\Block\Frontend;

use Magento\Backend\Model\Session\Quote;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\Config\Config;
use PublicSquare\Payments\Block\Form as BaseForm;
use PublicSquare\Payments\ICardInputCustomizationJSON;
use PublicSquare\Payments\Model\Adminhtml\Source\CcType;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Config as PaymentConfig;
use PublicSquare\Payments\Helper\Config as PSQConfig;

class Form extends BaseForm implements ICardInputCustomizationJSON
{
    /**
     * Template used to render the payment form on storefront (e.g., multishipping billing step).
     */
    protected $_template = 'PublicSquare_Payments::form/multishipping-cc.phtml';

    private string|null $cardInputStyleJSON;

    public function __construct(
        Context       $context,
        PaymentConfig $paymentConfig,
        Quote         $sessionQuote,
        Config        $gatewayConfig,
        CcType        $ccType,
        Data          $paymentDataHelper,
        PSQConfig     $psqConfig,
        array         $data = [],
    )
    {
        parent::__construct($context, $paymentConfig, $sessionQuote, $psqConfig, $ccType, $paymentDataHelper, $data);
        $this->cardInputStyleJSON = $psqConfig->getCardInputCustomizationJSON();

    }

    public function getPublicApiKey(): string
    {
        return (string)$this->gatewayConfig->getPublicAPIKey();
    }

    public function getCardInputCustomizationJSON(): string|null
    {
        return $this->cardInputStyleJSON;
    }


}


