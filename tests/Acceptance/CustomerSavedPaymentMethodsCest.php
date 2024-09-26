<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class CustomerSavedPaymentMethodsCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->amOnPage('/customer/account/login/');
        $I->fillField('login[username]', 'roni_cost@example.com');
        $I->fillField('login[password]', 'roni_cost3@example.com');
        $I->click('button[type="submit"].action.login.primary');
        $I->waitForText('My Account');
    }

    public function paymentMethodsPageWorks(AcceptanceTester $I)
    {
        $I->click('Stored Payment Methods');
        $I->see('Stored Payment Methods');
    }
}
