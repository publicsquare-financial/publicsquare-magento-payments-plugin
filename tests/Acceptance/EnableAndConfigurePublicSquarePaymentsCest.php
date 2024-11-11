<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class EnableAndConfigurePublicSquarePaymentsCest extends AcceptanceBase
{
    public function enableAndConfigurePublicSquarePayments(AcceptanceTester $I)
    {
        $this->_initialize($I);
        $this->_adminEnableAndConfigurePublicSquarePayments($I);
        $I->pause();
    }
}
