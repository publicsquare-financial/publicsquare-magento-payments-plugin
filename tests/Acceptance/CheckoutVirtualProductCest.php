<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class CheckoutVirtualProductCest extends AcceptanceBase
{
    public function checkoutVirtualProductWorks(AcceptanceTester $I)
    {
        $this->_initialize($I);
        // Create the virtual product
        $this->_adminCreateVirtualProduct($I);

        $this->_addVirtualProductToCart($I);
        $this->_goToVirtualProductCheckout($I);
        $this->_checkoutWithVirtualCard($I);
    }
}
