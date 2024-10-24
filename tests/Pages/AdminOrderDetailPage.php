<?php

namespace Tests\Pages;

use Tests\Acceptance\AcceptanceBase;
use Tests\Support\AcceptanceTester;

class AdminOrderDetailPage extends AcceptanceBase
{
  protected function _goToOrderDetail(AcceptanceTester $I) {
    $this->_initialize($I);
    $this->_adminLogin($I);
    $I->amOnPage('/admin/sales/order/index/');
    $I->waitForText('Orders');
    $I->waitForElementVisible('a.action-menu-item');
    $I->click('View');
    $I->waitForText('Order & Account Information');
  }

  protected function _paymentInformationTableIsVisible(AcceptanceTester $I) {
    $I->waitForText('Payment Information');
    $I->see('Payment Information');
    $I->see('Credit Card Type');
    $I->see('Credit Card Number');
    $I->see('Payment Status');
    $I->see('Payment ID');
    $I->see('Payment Details');
    $I->see('AVS Response');
    $I->see('CVV Response');
    $I->see('Fraud Decision');
  }
}
