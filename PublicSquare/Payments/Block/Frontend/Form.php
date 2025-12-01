<?php

namespace PublicSquare\Payments\Block\Frontend;

use Magento\Backend\Model\Session\Quote;
use Magento\Csp\Helper\CspNonceProvider;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\Manager;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\Config\Config;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Config as PaymentConfig;
use PublicSquare\Payments\Block\CreditCardForm;
use PublicSquare\Payments\Block\Form as BaseForm;
use PublicSquare\Payments\Helper\Config as PSQConfig;
use PublicSquare\Payments\Model\Adminhtml\Source\CcType;
use PublicSquare\Payments\Model\ICardInputCustomizationJSON;

class Form extends BaseForm implements ICardInputCustomizationJSON
{
    /**
     * Template used to render the payment form on storefront (e.g., multishipping billing step).
     */
    protected $_template = 'PublicSquare_Payments::form/multishipping-cc.phtml';

    private string|null $cardInputStyleJSON;
    private string $cardFormLayout;

    private CreditCardForm $cardForm;
    private CspNonceProvider|null $cspNonceProvider;

    public function __construct(
        Context                                            $context,
        PaymentConfig                                      $paymentConfig,
        Quote                                              $sessionQuote,
        Config                                             $gatewayConfig,
        CcType                                             $ccType,
        Data                                               $paymentDataHelper,
        PSQConfig                                          $psqConfig,
        Manager                                            $moduleManager,
        \Magento\Customer\Model\Session                    $session,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array                                              $data = [],
    )
    {
        parent::__construct($context, $paymentConfig, $sessionQuote, $psqConfig, $ccType, $paymentDataHelper, $data);
        $this->cardInputStyleJSON = $psqConfig->getCardInputCustomizationJSON();
        $this->cardFormLayout = $psqConfig->getCardFormLayout();

        // Since we can't wire the card form Block we'll just construct the block here.
        $cardFormData = ["showCardholderInput" => true];
        $this->cardForm = new CreditCardForm(
            $context,
            $psqConfig,
            $moduleManager,
            $scopeConfig,
            $session,
            $cardFormData,
        );

        if($moduleManager->isEnabled('Magento_Csp')) {
            try {
                $this->cspNonceProvider = ObjectManager::getInstance()->get(CspNonceProvider::class);
            } catch (\Throwable $exception) {
                error_log($exception->getMessage());
                $this->cspNonceProvider = null;
            }
        } else {
            $this->cspNonceProvider = null;
        }

    }

    public function getPublicApiKey(): string
    {
        return (string)$this->gatewayConfig->getPublicAPIKey();
    }

    public function getCardInputCustomizationJSON(): string|null
    {
        return $this->cardInputStyleJSON;
    }

    public function getChildHtml($alias = '', $useCache = true): string|null
    {
        // Render the cardform:
        // This works because the legacy checkout flow used by
        // multi-ship does not have child html.
        return $this->cardForm->toHtml();
    }

    public function getCspNonceProvider(): CspNonceProvider|null
    {
        return $this->cspNonceProvider;

    }

    public function getCardFormLayout(): string
    {
        return $this->cardFormLayout;
    }

}


