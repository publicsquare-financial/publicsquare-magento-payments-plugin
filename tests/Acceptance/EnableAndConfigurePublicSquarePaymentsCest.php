<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class EnableAndConfigurePublicSquarePaymentsCest extends AcceptanceBase
{
    public function __construct() {
        $this->rollbackTransactions = false;
    }

    public function enableAndConfigurePublicSquarePayments(AcceptanceTester $I)
    {
        $this->_initialize($I);
        $this->_adminEnableAndConfigurePublicSquarePayments($I);
        //$this->_updateItemAvailableStock($I, 'MP09', 50);
        //$this->_updateItemAvailableStock($I, 'MP09', 30);
    }
}
