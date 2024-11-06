<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class CheckoutEmailEmptyCest extends AcceptanceBase
{
    const GENERIC_DECLINE_MESSAGE = 'The payment could not be processed. Reason: ';
    const GENERIC_FRAUDULENT_MESSAGE = 'The payment could not be completed. Please verify your details and try again.';

    public function checkoutEmailEmptyWorks(AcceptanceTester $I)
    {
        $this->_initialize($I);
        $this->_addProductToCart($I);
        $this->_goToCheckout($I);
        $quoteId = (int)$I->executeJS('return window.checkoutConfig.quoteItemData[0].quote_id;');
        // Nullify the email field in the billing address.
        $I->updateInDatabase('quote_address', ['email' => null], ['quote_id' => $quoteId, 'address_type' => 'billing']);
        $I->seeInDatabase('quote_address', ['email' => null, 'quote_id' => $quoteId, 'address_type' => 'billing']);
        $this->_checkoutWithCard($I);
        $I->seeInDatabase('sales_order_address', ['email' => $this->customerEmail]);
    }
}
