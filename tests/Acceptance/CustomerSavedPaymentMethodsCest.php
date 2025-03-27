<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class CustomerSavedPaymentMethodsCest extends AcceptanceBase
{
    public function _before(AcceptanceTester $I)
    {
        $I->amOnPage('/customer/account/logout');
        $I->amOnPage('/customer/account/login/');
        $I->fillField('login[username]', 'roni_cost@example.com');
        $I->fillField('login[password]', 'roni_cost3@example.com');
        $I->click('button[type="submit"].action.login.primary');
        $I->waitForText('My Account');
    }

    public function customerPaymentMethodsPageWorks(AcceptanceTester $I)
    {
        $this->_initialize($I);
        $this->_addProductToCart($I);
        $this->_goToCheckout($I);
        $this->_checkoutWithCard($I, '4242424242424242', 'Thank you for your purchase!', false, true);
        $I->amOnPage('/vault/cards/listaction/');
        $I->see('Stored Payment Methods');
        $I->see('ending 4242');
        $I->see('12/2029');
    }

    public function checkoutWithSavedCard(AcceptanceTester $I)
    {
        $this->_initialize($I);
        $this->_addProductToCart($I);
        $this->_goToCheckout($I);
        $this->_checkoutWithSavedCard($I, 'Thank you for your purchase!');
    }
}
