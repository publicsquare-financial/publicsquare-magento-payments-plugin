<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace PublicSquare\Payments\Model\InstantPurchase\CreditCard;

use Magento\InstantPurchase\PaymentMethodIntegration\AvailabilityCheckerInterface;

/**
 * Availability of Braintree vaults for instant purchase.
 *
 * @deprecated Starting from Magento 2.3.6 Braintree payment method core integration is deprecated
 * in favor of official payment integration available on the marketplace
 */
class AvailabilityChecker implements AvailabilityCheckerInterface
{
    public function __construct()
    {
    }

    /**
     * @inheritdoc
     */
    public function isAvailable(): bool
    {
        return false;
    }
}
