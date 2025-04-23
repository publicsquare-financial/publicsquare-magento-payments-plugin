<?php

namespace Tests\Acceptance;

use Tests\Bases\AcceptanceBase;
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

        // get the order number
        $orderNumber = $I->executeJS('return jQuery(".order-number").text(); ');

        // check order payment method info
        $this->_customerGoToAnOrder($I, false);

        $I->see('Credit/Debit Card');
        $I->see('Credit Card Type');
        $I->see('visa');
        $I->see('Credit Card Number');
        $I->see('xxxx-4242');

        // verify that customers don't see these payment method details.
        $I->dontSee('Payment ID:');
        $I->dontSee('AVS Response:');
        $I->dontSee('CVV Response');
        $I->dontSee('Fraud Decision');

        // verify emails
        // $I->amOnUrl('http://localhost:1080/');
        // $I->see("MailCatcher");
        // $I->waitForElementVisible("#messages");

        // // make sure both emails are present.
        // $I->see("Invoice for your Main Website Store order");
        // $I->see("Your Main Website Store order confirmation");

        // // verify invoice email
        // $I->click('//td[contains(text(), "Invoice for your Main Website Store order")]');
        // $I->switchToIframe("#message .body");
        // $I->waitForText("Credit/Debit Card");
        // $I->see("Order #$orderNumber");
        // $I->see('Credit/Debit Card');
        // $I->dontSee('Payment ID:');
        // $I->dontSee('AVS Response:');
        // $I->dontSee('CVV Response');
        // $I->dontSee('CVV Response');
        // $I->dontSee('Fraud Decision');

        // // verify order confirmation email
        // $I->switchToIframe();   // Switch back to the main page (outside the iframe)
        // $I->click('//td[contains(text(), "Your Main Website Store order confirmation")]');
        // $I->switchToIframe("#message .body");
        // $I->see("Order #$orderNumber");
        // $I->see('Credit/Debit Card');
        // $I->dontSee('Payment ID:');
        // $I->dontSee('AVS Response:');
        // $I->dontSee('CVV Response');
        // $I->dontSee('Fraud Decision');
    }
}
