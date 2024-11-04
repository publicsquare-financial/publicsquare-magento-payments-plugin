<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class CheckoutRoundingCest extends AcceptanceBase
{
    const GENERIC_DECLINE_MESSAGE = 'The payment could not be processed. Reason: ';
    const GENERIC_FRAUDULENT_MESSAGE = 'The payment could not be completed. Please verify your details and try again.';

    public function checkoutWithRoundingWorks(AcceptanceTester $I)
    {
        $this->_initialize($I);

        $I->updateInDatabase('salesrule', ['discount_amount' => 20.1235], ['name' => '20% OFF Ever $200-plus purchase!*']);

        // add product.
        $this->_addProductToCart($I);

        $this->_changeCartQuantity($I, 10);

        // do checkout
        $this->_goToCheckout($I);
        $amount = (float)str_replace('$', '', $I->grabTextFrom('.grand.totals span.price'));
        
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
