<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class RefundCest extends AcceptanceBase
{
    public function refundWorks(AcceptanceTester $I)
    {
        $this->_initialize($I);

        // add product.
        $this->_addProductToCart($I);

        // do checkout
        $this->_goToCheckout($I);
        $amount = $I->grabTextFrom('.grand.totals span.price');
        $this->_checkoutWithCard($I);
        $this->_adminCreateRefund($I);
    }
}
