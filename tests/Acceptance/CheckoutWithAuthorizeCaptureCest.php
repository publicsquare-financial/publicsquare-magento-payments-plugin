<?php

namespace Tests\Acceptance;


use Tests\Support\AcceptanceTester;

class CheckoutWithAuthorizeCaptureCest extends AcceptanceBase
{
    public function checkoutWithAuthorizeCaptureEnabledWorks(AcceptanceTester $I)
    {
        $this->_initialize($I);

        // Step 1: Setup your acceptance test to set the "Payment capture action" in plugin settings to "Authorize & Capture"
        $this->_adminEnableAuthorizeCapture($I);

        // Step 2: Run through a checkout
        $this->_addProductToCart($I);
        $this->_goToCheckout($I);
        $amount = $I->grabTextFrom('.grand.totals span.price');
        $this->_checkoutWithCard($I);

        // Step 3: Confirm that the order is paid
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
