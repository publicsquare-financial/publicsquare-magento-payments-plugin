<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class BootstrapCest extends AcceptanceBase
{
    public function helloMagento(AcceptanceTester $I)
    {
        $this->_initialize($I);

        $I->amOnPage('/');
        $I->see('Copyright © 2013-present Magento, Inc. All rights reserved.');
    }
}
