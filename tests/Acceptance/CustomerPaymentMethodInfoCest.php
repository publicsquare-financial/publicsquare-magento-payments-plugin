<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class CustomerPaymentMethodInfoCest extends AcceptanceBase
{
    public function customerPaymentMethodInfoShows(AcceptanceTester $I): void
    {
        $this->_initialize($I);

        $this->_customerLogin($I);

        // add product.
        $this->_addProductToCart($I);

        $this->_goToCheckoutWhileLoggedIn($I);

        $this->_checkoutWithCard($I);


        // check order payment method info
        $this->_customerGoToAnOrder($I, false);

        $I->see('Credit/Debit Card');
        $I->see('Credit Card Type');
        $I->see('visa');
        $I->see('Credit Card Number');
        $I->see('xxxx-4242');
    }
}
