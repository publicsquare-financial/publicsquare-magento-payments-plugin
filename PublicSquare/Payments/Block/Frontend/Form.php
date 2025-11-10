<?php
namespace PublicSquare\Payments\Block\Frontend;

use PublicSquare\Payments\Block\Form as BaseForm;

class Form extends BaseForm
{
    /**
     * Template used to render the payment form on storefront (e.g., multishipping billing step).
     */
    protected $_template = 'PublicSquare_Payments::form/multishipping-cc.phtml';

    public function getPublicApiKey(): string
    {
        return (string) $this->gatewayConfig->getPublicAPIKey();
    }
}


