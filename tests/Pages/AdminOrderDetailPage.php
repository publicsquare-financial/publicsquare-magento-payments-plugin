<?php

namespace Tests\Pages;

use Codeception\Util\Locator;
use Tests\Bases\AcceptanceBase;
use Tests\Support\AcceptanceTester;

class AdminOrderDetailPage extends AcceptanceBase
{
  protected function _goToOrderDetail(AcceptanceTester $I) {
    $this->_initialize($I);
    $this->_adminLogin($I);
    $I->amOnPage('/admin/sales/order/index/');
    $I->waitForText('Orders');
    $this->_waitForLoading($I);
    $I->waitForElementVisible('a.action-menu-item');
    $this->_waitForLoading($I);
    $link = Locator::firstElement('.data-grid-actions-cell>a');
    $I->waitForElementClickable($link);
    $I->click($link);
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

  protected function _paymentDetailsLinkHasCorrectUrl(AcceptanceTester $I) {
    $I->waitForElementVisible('.payment-details-link');
    $url = $I->grabAttributeFrom('.payment-details-link', 'href');
    $I->assertTrue(str_starts_with($url, 'https://portal.publicsquare.com/payments/'));
  }
}
