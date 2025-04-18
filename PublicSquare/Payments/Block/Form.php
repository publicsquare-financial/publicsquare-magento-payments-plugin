<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace PublicSquare\Payments\Block;

use Magento\Backend\Model\Session\Quote;
use PublicSquare\Payments\Helper\Config as GatewayConfig;
use PublicSquare\Payments\Model\Adminhtml\Source\CcType;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Form\Cc;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Config;
use Magento\Vault\Model\VaultPaymentInterface;

class Form extends Cc
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Quote
     */
    protected $sessionQuote;

    /**
     * @var Config
     */
    protected $gatewayConfig;

    /**
     * @var CcType
     */
    protected $ccType;

    /**
     * @var Data
     */
    private $paymentDataHelper;

    /**
     * @param Context $context
     * @param Config $paymentConfig
     * @param Quote $sessionQuote
     * @param GatewayConfig $gatewayConfig
     * @param CcType $ccType
     * @param Data $paymentDataHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $paymentConfig,
        Quote $sessionQuote,
        GatewayConfig $gatewayConfig,
        CcType $ccType,
        Data $paymentDataHelper,
        array $data = []
    ) {
        parent::__construct($context, $paymentConfig, $data);
        $this->context = $context;
        $this->sessionQuote = $sessionQuote;
        $this->gatewayConfig = $gatewayConfig;
        $this->ccType = $ccType;
        $this->paymentDataHelper = $paymentDataHelper;
    }

    /**
     * Get list of available card types of order billing address country
     * @return array
     */
    public function getCcAvailableTypes()
    {
        $configuredCardTypes = $this->getConfiguredCardTypes();
        $countryId = $this->sessionQuote->getQuote()->getBillingAddress()->getCountryId();
        return $this->filterCardTypesForCountry($configuredCardTypes, $countryId);
    }

    /**
     * Check if cvv validation is available
     * @return boolean
     */
    public function useCvv()
    {
        return $this->gatewayConfig->isCvvEnabled($this->sessionQuote->getStoreId());
    }

    /**
     * Check if vault enabled
     * @return bool
     */
    public function isVaultEnabled()
    {
        // Determine if this is a page in the admin area
        $isAdmin = $this->context->getAppState()->getAreaCode() === 'adminhtml';
        if ($isAdmin) {
            return false;
        }

        $vaultPayment = $this->getVaultPayment();
        return $vaultPayment->isActive($this->sessionQuote->getStoreId());
    }

    /**
     * Get card types available for Braintree
     * @return array
     */
    private function getConfiguredCardTypes()
    {
        $types = $this->ccType->getCcTypeLabelMap();
        $configCardTypes = array_fill_keys(
            $this->gatewayConfig->getAvailableCardTypes($this->sessionQuote->getStoreId()),
            ''
        );

        return array_intersect_key($types, $configCardTypes);
    }

    /**
     * Filter card types for specific country
     * @param array $configCardTypes
     * @param string $countryId
     * @return array
     */
    private function filterCardTypesForCountry(array $configCardTypes, $countryId)
    {
        $filtered = $configCardTypes;
        $countryCardTypes = $this->gatewayConfig->getCountryAvailableCardTypes(
            $countryId,
            $this->sessionQuote->getStoreId()
        );

        // filter card types only if specific card types are set for country
        if (!empty($countryCardTypes)) {
            $availableTypes = array_fill_keys($countryCardTypes, '');
            $filtered = array_intersect_key($filtered, $availableTypes);
        }
        return $filtered;
    }

    /**
     * Get configured vault payment for Braintree
     * @return VaultPaymentInterface
     */
    private function getVaultPayment()
    {
        return $this->paymentDataHelper->getMethodInstance(GatewayConfig::VAULT_CODE);
    }
}
