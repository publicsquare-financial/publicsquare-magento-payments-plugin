<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class BootstrapCest
{
    public function helloMagento(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        $I->see('Copyright © 2013-present Magento, Inc. All rights reserved.');
    }
}
