<?php

namespace Tests\Acceptance;

use Tests\Pages\CheckoutPage;
use Tests\Support\AcceptanceTester;

class CheckoutCest extends CheckoutPage
{
    public function _before(AcceptanceTester $I)
    {
        $this->addProductToCart($I);
        $this->goToCheckout($I);
    }

    public function paymentMethodIsVisible(AcceptanceTester $I)
    {
        $this->makeSurePaymentMethodIsVisible($I);
    }

    public function checkoutWorks(AcceptanceTester $I)
    {
        $this->checkoutWithCard($I);
    }
}
