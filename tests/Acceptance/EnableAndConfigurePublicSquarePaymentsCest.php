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
    }

//    public function addProductInventory(AcceptanceTester $I)
//    {
//        $this->_initialize($I);
//        $this->_addInventoryToProduct($I, "Gift Card", 1000);
//    }
}
