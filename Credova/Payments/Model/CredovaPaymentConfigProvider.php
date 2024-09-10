<?php

/**
 * CredovaFinancial
 *
 * @category  Credova
 * @package   Credova_Financial
 * @author    Credova <info@credova.com>
 * @copyright 2019 Credova
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://credova.com/
 */

namespace Credova\Payments\Model;

use Credova\Payments\Helper\Config;
use Magento\Framework\UrlInterface;

class CredovaPaymentConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var Config
     */
    private $credovaConfig;

    /**
     * Injected url builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param \Magento\Checkout\Model\Session            $checkoutSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param Config                                     $credovaConfig
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        Config $credovaConfig,
        UrlInterface $urlInterface
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->credovaConfig   = $credovaConfig;
        $this->urlBuilder      = $urlInterface;
    }

    public function getConfig()
    {
        return [
            'payment' => [
                $this->credovaConfig::CODE => [
                    'pk'         => $this->credovaConfig->getPublicAPIKey(),
                    'cancelUrl'     => $this->urlBuilder->getUrl('credova_payments/checkout/cancel', ['_secure' => true]),
                    'successUrl'    => $this->urlBuilder->getUrl('credova_payments/checkout/complete', ['_secure' => true]),
                    'vaultEnabled'  => $this->credovaConfig->getCCVaultActive(),
                    'ccVaultCode'   => \Credova\Payments\Model\Transparent::CC_VAULT_CODE
                ],
            ]
        ];
    }
}
