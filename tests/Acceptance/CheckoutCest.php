<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class CheckoutCest extends AcceptanceBase
{
    const GENERIC_DECLINE_MESSAGE = 'The payment could not be processed. Reason: ';
    const GENERIC_FRAUDULENT_MESSAGE = 'The payment could not be completed. Please verify your details and try again.';

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
       $I->seeInDatabase('sales_order', [
           'customer_email' => $this->customerEmail,
           'grand_total' => number_format(floatval(str_replace('$', '', $amount)), 4, '.', ''),
           'total_paid' => number_format(floatval(str_replace('$', '', $amount)), 4, '.', ''),
           'status' => 'processing',
           // Added to confirm this was a guest checkout
           'customer_group_id' => 0
       ]);
   }
//
    public function makeSureFailedChargeDoesNotCreateASalesOrder(AcceptanceTester $I)
    {
        $this->_initialize($I);

        // add product.
        $this->_addProductToCart($I);

        // do checkout
        $this->_goToCheckout($I);
        $amount = $I->grabTextFrom('.grand.totals span.price');
        $this->_checkoutWithCard($I, '4000000000000002', 'Decline');

        // verify order was not created.
        $I->dontSeeInDatabase('sales_order', ['customer_email' => $this->customerEmail]);

    }

    /**
     * Declined Card test using data provider.
     *
     * @dataProvider declinedChargesProvider
     */
    public function declinedChargesTest(AcceptanceTester $I, \Codeception\Example $example) {

        echo "Testing cardNumber: {$example['cardNumber']} Message: {$example['message']}\n";
        $this->_initialize($I);

        // add product.
        $this->_addProductToCart($I);

        // do checkout
        $this->_goToCheckout($I);

        // Reason: Declined
        $this->_checkoutWithCard($I, '4000000000000002', $this::GENERIC_DECLINE_MESSAGE.'Decline');
        // Reason: Insufficient Funds
        $this->_checkoutWithCard($I, '4000000000009995', $this::GENERIC_DECLINE_MESSAGE.'Insufficient Funds');
        // Reason: Lost/Stolen
        $this->_checkoutWithCard($I, '4000000000009987', $this::GENERIC_DECLINE_MESSAGE.'Lost/Stolen');
        // Reason: Rejected fraud decision
        $this->_checkoutWithCard($I, '4100000000000019', $this::GENERIC_FRAUDULENT_MESSAGE);
        // Reason: CVC check fails
        $this->_checkoutWithCard($I, '4000000000000101', $this::GENERIC_FRAUDULENT_MESSAGE);
        // Reason: Address check fails
        $this->_checkoutWithCard($I, '4000000000000010', $this::GENERIC_FRAUDULENT_MESSAGE);
        // Reason: payment failed
        $this->_checkoutWithCard($I, '4111111111111111', $this::GENERIC_FRAUDULENT_MESSAGE);

        // verify order was not created.
        $I->dontSeeInDatabase('sales_order', ['customer_email' => $this->customerEmail]);
    }

    protected function declinedChargesProvider() : array  // to make it public use `_` prefix
    {
        return [
            ['cardNumber'=>"4000000000000002", 'message'=> $this::GENERIC_DECLINE_MESSAGE.'Decline'],
            ['cardNumber'=>"4000000000009995", 'message'=> $this::GENERIC_DECLINE_MESSAGE.'Insufficient Funds'],
            ['cardNumber'=>"4000000000009987", 'message'=> $this::GENERIC_DECLINE_MESSAGE.'Lost/Stolen'],
            ['cardNumber'=>"4100000000000019", 'message'=> $this::GENERIC_FRAUDULENT_MESSAGE],
            ['cardNumber'=>"4000000000000101", 'message'=> $this::GENERIC_FRAUDULENT_MESSAGE],
            ['cardNumber'=>"4000000000000010", 'message'=> $this::GENERIC_FRAUDULENT_MESSAGE],
            ['cardNumber'=>"4111111111111111", 'message'=> $this::GENERIC_FRAUDULENT_MESSAGE],

        ];
    }
}
