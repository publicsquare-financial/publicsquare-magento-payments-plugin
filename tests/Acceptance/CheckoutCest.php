<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class CheckoutCest extends AcceptanceBase
{
    public function checkoutWorks(AcceptanceTester $I)
    {
        $this->_initialize($I);

        // add product.
        $this->_addProductToCart($I);

        // do checkout
        $this->_goToCheckout($I);
        $this->_checkoutWithCard($I);

        // verify order was created and paid.
        $I->seeInDatabase('sales_order', ['customer_email' => $this->customerEmail]);
        $I->seeInDatabase('sales_order', [
            'customer_email' => $this->customerEmail,
            'grand_total' => 80.0000,
            'total_paid' => 80.0000,
            'status' => 'processing',
        ]);
    }
}
