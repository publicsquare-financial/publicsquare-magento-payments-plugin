<?php

namespace PublicSquare\Payments\Block;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\View\Element\Template;

class CreditCardForm extends Template
{
    private string $cardFormLayout;
    private string $customerName;
    private bool $psqVaultConfigEnabled;
    private bool $vaultConfigEnabled;
    private array $creditCardTypes;

    public function __construct(

        Template\Context                     $context,
        \PublicSquare\Payments\Helper\Config $psqConfig,
        Manager                              $moduleManager,

        ScopeConfigInterface                 $config,
        Session                              $session,
        array                                $data = [],

    )
    {
        parent::__construct($context, $data);
        $this->cardFormLayout = $psqConfig->getCardFormLayout();

        $this->psqVaultConfigEnabled = $config->getValue('payment/publicsquare_payments_cc_vault/active', ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        $this->vaultConfigEnabled = $moduleManager->isEnabled('Magento_Vault');

        $this->customerName = $session->getCustomer()->getName();
        $this->creditCardTypes = $psqConfig->getFilteredCcAvailableTypes();
    }

    public function getTemplate(): string
    {
        if ($this->cardFormLayout === "split-a") {
            return "PublicSquare_Payments::card-form-split.phtml";
        } else {
            return "PublicSquare_Payments::card-form-single.phtml";
        }
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function showCardholderInput(): bool
    {
        return $this->getData('showCardholderInput');
    }

    public function getCreditCardTypes(): array
    {
        return $this->creditCardTypes;
    }

}