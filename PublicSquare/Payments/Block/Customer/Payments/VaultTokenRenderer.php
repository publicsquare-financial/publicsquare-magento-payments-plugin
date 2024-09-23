<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace PublicSquare\Payments\Block\Customer\Payments;

use PublicSquare\Payments\Helper\Config;
use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractTokenRenderer;

/**
 * Class VaultTokenRenderer
 *
 * @api
 * @since 100.1.3
 * @deprecated Starting from Magento 2.3.6 PublicSquare payment method core integration is deprecated
 * in favor of official payment integration available on the marketplace
 */
class VaultTokenRenderer extends AbstractTokenRenderer
{
    /**
     * @var Config
     */
    private $config;

    /**
     * Initialize dependencies.
     *
     * @param Template\Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return string
     * @since 100.1.0
     */
    public function getIconUrl()
    {
        return 'https://assets.publicsquare.com/sc/web/assets/images/cards/'.strtolower($this->getTokenDetails()['type']).'.svg';
    }

    /**
     * @return int
     * @since 100.1.0
     */
    public function getIconHeight()
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['height'];
        // return 71;
    }

    /**
     * @return int
     * @since 100.1.0
     */
    public function getIconWidth()
    {
        // return $this->getIconForType($this->getTokenDetails()['type'])['width'];
        return 45;
    }

    /**
     * Can render specified token
     *
     * @param PaymentTokenInterface $token
     * @return boolean
     * @since 100.1.3
     */
    public function canRender(PaymentTokenInterface $token)
    {
        return $token->getPaymentMethodCode() === Config::VAULT_CODE;
    }

    /**
     * Get email of PayPal payer
     * @return string
     * @since 100.1.3
     */
    public function getPayerEmail()
    {
        return $this->getTokenDetails()['payerEmail'];
    }
}
