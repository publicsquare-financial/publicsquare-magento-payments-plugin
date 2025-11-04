<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace PublicSquare\Payments\Block\Frontend;

use PublicSquare\Payments\Block\Form as BaseForm;

/**
 * Frontend payment form block for PublicSquare Payments.
 *
 * This extends the base Form block to reuse all logic (card types filtering,
 * CVV config, vault enablement, etc.) while allowing a distinct class to be
 * referenced from frontend DI configuration.
 */
class Form extends BaseForm
{
    /**
     * Template used to render the payment form on storefront (e.g., multishipping billing step).
     */
    protected $_template = 'PublicSquare_Payments::form/multishipping-cc.phtml';
}


