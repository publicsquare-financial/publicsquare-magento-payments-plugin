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
        $this->_checkoutWithCard($I);

        // Step 3: Confirm that the order is paid
        $I->seeInDatabase('sales_order', ['customer_email' => $this->customerEmail]);
        $I->seeInDatabase('sales_order', [
            'customer_email' => $this->customerEmail,
            'grand_total' => 80.0000,
            'total_paid' => 80.0000,
            'status' => 'processing',
        ]);
    }
}
