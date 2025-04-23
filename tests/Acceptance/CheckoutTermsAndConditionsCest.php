<?php

namespace Tests\Acceptance;

use Tests\Bases\AcceptanceBase;
use Tests\Support\AcceptanceTester;

class CheckoutTermsAndConditionsCest extends AcceptanceBase
{
    public function checkoutWithoutTermsAndConditionsAgreementFails(AcceptanceTester $I)
    {
        $this->_enableTermsAndConditions($I);
        $this->_initialize($I);
        $this->_addProductToCart($I);
        $this->_goToCheckout($I);
        $this->_checkoutWithCard($I, '4242424242424242', 'Please check your checkout details.');
    }

    public function checkoutTermsAndConditionsWorks(AcceptanceTester $I)
    {
        // Enable the terms and conditions checkbox
        $this->_enableTermsAndConditions($I);
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
        $this->_cleanup($I);
    }

    private function _enableTermsAndConditions(AcceptanceTester $I)
    {
        $termsAndConditionsEnabled = $I->grabFromDatabase('core_config_data', 'value', ['path' => 'checkout/options/enable_agreements']);
        if (!$termsAndConditionsEnabled) {
            $I->haveInDatabase('core_config_data', [
                'scope' => 'default',
                'scope_id' => 0,
                'path' => 'checkout/options/enable_agreements',
                'value' => 1
            ]);
            $I->haveInDatabase('checkout_agreement', [
                'name' => 'Terms & Conditions',
                'content' => 'Do you agree to the terms and conditions?',
                'checkbox_text' => 'I agree to the terms and conditions',
                'is_active' => 1,
                'mode' => 1
            ]);
            $agreementId = $I->grabFromDatabase('checkout_agreement', 'agreement_id', ['name' => 'Terms & Conditions']);
            $I->haveInDatabase('checkout_agreement_store', [
                'agreement_id' => $agreementId,
                'store_id' => 1
            ]);
            $I->runShellCommand('bin/magento cache:clean config');
        }
    }

    private function _disableTermsAndConditions(AcceptanceTester $I)
    {
        $this->_cleanup($I);
    }

    private function _cleanup(AcceptanceTester $I)
    {
        $I->runShellCommand('bin/magento cache:clean config');
    }
}
