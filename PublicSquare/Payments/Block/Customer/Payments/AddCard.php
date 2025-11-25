<?php

namespace PublicSquare\Payments\Block\Customer\Payments;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\View\Element\Template;
use PublicSquare\Payments\ICardInputCustomizationJSON;
use PublicSquare\Payments\Logger\Logger;

class AddCard extends Template implements ICardInputCustomizationJSON
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

    private string|null $cardInputCustomJSON;

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

        $this->vaultEnabled = $moduleManager->isEnabled('Magento_Vault');
        $this->psqVaultConfigEnabled = $config->getValue('payment/publicsquare_payments_cc_vault/active', ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        $this->customerName = $session->getCustomer()->getName();
        $this->publicKey = $psqConfig->getPublicAPIKey();

        $this->cardInputCustomJSON = $psqConfig->getCardInputCustomizationJSON();
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
        return $this->getUrl("publicsquare-payments/customer/card");
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getCardInputCustomizationJSON(): string|null
    {
        return $this->cardInputCustomJSON;
    }
}
