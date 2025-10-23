<?php

namespace PublicSquare\Payments\Block\Customer\Payments;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Model\PaymentTokenRepository;

class AddCard extends Template
{
    private Template\Context $context;
    /**
     * @var bool $vaultEnabled Global flag indicating that the vault module is enabled
     */
    private bool $vaultEnabled;
    /**
     * @var bool $psqVaultConfigEnabled Flag from the PSQ Payments plugin settings enabling vault for this plugin.
     */
    private bool $psqVaultConfigEnabled;

    private \PublicSquare\Payments\Helper\Config $psqConfig;
    private PaymentTokenRepositoryInterface $paymentTokenRepository;
//    private ObjectManager $objectManager;

    public function __construct(
        Template\Context                $context,
        Manager                         $moduleManager,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
\PublicSquare\Payments\Helper\Config $psqConfig,
//        ObjectManager                   $objectManager,
        array                           $data = []
    )
    {
        parent::__construct($context, $data);
        $this->context = $context;
        $this->vaultEnabled = $moduleManager->isEnabled('Magento_Vault');
        // TODO: How to read the plugin configuration?
        $this->psqVaultConfigEnabled = $this->vaultEnabled;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->psqConfig = $psqConfig;
//        $this->objectManager = $objectManager;

        /*$encryptor = $this->objectManager->get(EncryptorInterface::class);
        $paymentToken = $this->objectManager->create(PaymentToken::class);
        $paymentToken->setCustomerId('');
        $tokenRepository = $this->objectManager->create(PaymentTokenRepository::class);
        $tokenRepository->save($paymentToken);*/
    }

    /**
     * @return bool if this Template should render the add credit card button
     */
    public function isEnabled(): bool
    {
        return $this->vaultEnabled && $this->psqVaultConfigEnabled;
    }

    public function formState(): string {
        return "--hidden";
    }
    public function formAction(): string {
        return "todo";
    }

    public function billingAddresses(): array {
        return [];
    }

    public function filterCardTypes(object $billingAddress): array {
        return [];
    }

    public function getPublicKey(): string {
        return $this->psqConfig->getPublicAPIKey();
    }
}
