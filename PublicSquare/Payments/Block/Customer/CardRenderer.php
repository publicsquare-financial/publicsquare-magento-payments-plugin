<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace PublicSquare\Payments\Block\Customer;

use PublicSquare\Payments\Helper\Config;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractCardRenderer;

/**
 * @api
 * @since 100.1.0
 * @deprecated Starting from Magento 2.3.6 Braintree payment method core integration is deprecated
 * in favor of official payment integration available on the marketplace
 */
class CardRenderer extends AbstractCardRenderer
{
    /**
     * Can render specified token
     *
     * @param PaymentTokenInterface $token
     * @return boolean
     * @since 100.1.0
     */
    public function canRender(PaymentTokenInterface $token)
    {
        return $token->getPaymentMethodCode() === Config::CODE;
    }

    /**
     * @return string
     * @since 100.1.0
     */
    public function getNumberLast4Digits()
    {
        return $this->getTokenDetails()['maskedCC'];
    }

    /**
     * @return string
     * @since 100.1.0
     */
    public function getExpDate()
    {
        return $this->getTokenDetails()['expirationDate'];
    }

    /**
     * @return string
     * @since 100.1.0
     */
    public function getIconUrl()
    {
        return Config::PUBLICSQUARE_CARD_IMAGES_BASE_PATH.strtolower($this->getTokenDetails()['type']).'.svg';
    }

    /**
     * @return int
     * @since 100.1.0
     */
    public function getIconHeight()
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['height'];
    }

    /**
     * @return int
     * @since 100.1.0
     */
    public function getIconWidth()
    {
        return 45;
    }

    /**
     * @param PaymentTokenInterface $token
     * @return string
     */
    public function renderTokenCard(PaymentTokenInterface $token)
    {
        $html = '<div class="publicsquare-vault-card-details">';
        $html .= '<img src="' . $this->getIconUrl() . '" width="' . $this->getIconWidth() . '" height="' . $this->getIconHeight() . '">';
        $html .= '<span>' . $this->getNumberLast4Digits() . '</span>';
        $html .= '<span>' . $this->getExpDate() . '</span>';
        $html .= '</div>';
        return $html;
    }
}
