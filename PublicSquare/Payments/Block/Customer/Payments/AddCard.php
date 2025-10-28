<?php

namespace PublicSquare\Payments\Block\Customer\Payments;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\View\Element\Template;
use PublicSquare\Payments\Logger\Logger;

class AddCard extends Template
{

    /**
     * @var bool $vaultEnabled Global flag indicating that the vault module is enabled
     */
    private bool $vaultEnabled;

    /**
     * @var bool $psqVaultConfigEnabled Flag from the PSQ Payments plugin settings enabling vault for this plugin.
     */
    private bool $psqVaultConfigEnabled;

    private string $customerName;

    private string $publicKey;


    public function __construct(
        Template\Context                     $context,
        Manager                              $moduleManager,
        \PublicSquare\Payments\Helper\Config $psqConfig,
        Session                              $session,
        ScopeConfigInterface                 $config,
        Logger                               $logger,
        array                                $data = []
    )
    {
        parent::__construct($context, $data);
        $this->psqConfig = $psqConfig;
        $this->session = $session;

        $this->vaultEnabled = $moduleManager->isEnabled('Magento_Vault');
        $this->psqVaultConfigEnabled = $config->getValue('payment/publicsquare_payments_cc_vault/active', ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        $this->customerName = $session->getCustomer()->getName();
        $this->publicKey = $psqConfig->getPublicAPIKey();
        $logger->debug("Magento_Vault: {$this->vaultEnabled} PSQVaultEnabled: {$this->psqVaultConfigEnabled}");
    }

    /**
     * @return bool if this Template should render the add credit card button
     */
    public function isEnabled(): bool
    {
        return $this->vaultEnabled && $this->psqVaultConfigEnabled;
    }

    public function formAction(): string
    {
        return "/publicsquare-payments/customer/card";
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }
}
