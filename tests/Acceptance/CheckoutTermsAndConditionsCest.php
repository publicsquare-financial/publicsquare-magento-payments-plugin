<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class CheckoutTermsAndConditionsCest extends AcceptanceBase
{
    public function checkoutTermsAndConditionsWorks(AcceptanceTester $I)
    {
        // Enable the terms and conditions checkbox
        $termsAndConditionsEnabled = $I->grabFromDatabase('core_config_data', 'value', ['path' => 'checkout/options/enable_agreements']);
        if ($termsAndConditionsEnabled != 1) {
            $I->haveInDatabase('core_config_data', [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'checkout/options/enable_agreements',
                'value' => 1
            ]);
        }
        $this->_initialize($I);
        $this->_addProductToCart($I);
        $this->_goToCheckout($I);
        $amount = $I->grabTextFrom('.grand.totals span.price');
        $this->_checkoutWithCard($I, '4242424242424242', 'Thank you for your purchase!', true);
        // verify order was created and paid.
        $I->seeInDatabase('sales_order', [
            'customer_email' => $this->customerEmail,
            'grand_total' => number_format(floatval(str_replace('$', '', $amount)), 4, '.', ''),
            'total_paid' => number_format(floatval(str_replace('$', '', $amount)), 4, '.', ''),
            'status' => 'processing',
            // Added to confirm this was a guest checkout
            'customer_group_id' => 0
        ]);
    }
}
