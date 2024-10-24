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
        $amount = $I->grabTextFrom('.grand.totals span.price');
        $this->_checkoutWithCard($I);

        // verify order was created and paid.
        $I->seeInDatabase('sales_order', ['customer_email' => $this->customerEmail]);
        $I->seeInDatabase('sales_order', [
            'customer_email' => $this->customerEmail,
            'grand_total' => number_format(floatval(str_replace('$', '', $amount)), 4, '.', ''),
            'total_paid' => number_format(floatval(str_replace('$', '', $amount)), 4, '.', ''),
            'status' => 'processing',
        ]);
    }
}
