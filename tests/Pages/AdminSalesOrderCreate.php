<?php

namespace Tests\Pages;

use Tests\Acceptance\AcceptanceBase;
use Tests\Support\AcceptanceTester;
use Codeception\Util\Locator;

class AdminSalesOrderCreate extends AcceptanceBase
{
  protected function _goToNewSalesOrder(AcceptanceTester $I)
  {
    $this->_initialize($I);
    $this->_adminLogin($I);
    $I->amOnPage('/admin/sales/order/index/');
    $I->waitForElementClickable('.page-actions-buttons button[title="Create New Order"]');
    $I->click('.page-actions-buttons button[title="Create New Order"]');
    $I->waitForElementClickable('#edit_form .actions button[title="Create New Customer"]');
    $I->click('#edit_form .actions button[title="Create New Customer"]');
    $this->_waitForLoading($I);
    sleep(1);
  }

  protected function _addProductToOrder(AcceptanceTester $I)
  {
    $I->click('Add Products');
    $this->_waitForLoading($I);
    // Select first visible product and add to order
    $secondCheckboxCss = '#sales_order_create_search_grid_table>tbody>tr:nth-child(2) input[type="checkbox"]';
    $I->waitForElementClickable($secondCheckboxCss);
    $I->click($secondCheckboxCss);
    $this->_waitForLoading($I);
    $I->waitForElementClickable('[title="Add Selected Product(s) to Order"]');
    $I->click('[title="Add Selected Product(s) to Order"]');
    $this->_waitForLoading($I);
    // If a configure modal appears (e.g., configurable product), accept it and close the modal
    $I->executeJS("var btn=document.querySelector('.modal-popup .action-primary, .modal-slide .action-primary'); if(btn){btn.click();}");
    $this->_waitForLoading($I);
    $I->executeJS("var close=document.querySelector('.modal-popup .action-close, .modal-slide .action-close'); if(close){close.click();}");
    $this->_waitForLoading($I);
  }

  protected function _addCustomerToOrder(AcceptanceTester $I)
  {
    $this->_generateUniqueEmail();
    $I->fillField('order[account][email]', $this->customerEmail);
    $I->fillField('order[billing_address][firstname]', 'Billy');
    $I->fillField('order[billing_address][lastname]', 'Bob');
    $I->fillField('order[billing_address][street][0]', '123 Main St');
    $I->selectOption('order[billing_address][country_id]', 'US');
    $I->selectOption('order[billing_address][region_id]', '15');
    $I->fillField('order[billing_address][city]', 'Newark');
    $I->fillField('order[billing_address][postcode]', '19711');
    $I->fillField('order[billing_address][telephone]', '1234567890');
  }

  protected function _addShippingMethodToOrder(AcceptanceTester $I)
  {
    // Open shipping method section
    $I->waitForElementClickable('#order-shipping-method-summary>a');
    $I->click('#order-shipping-method-summary>a');
    $this->_waitForLoading($I);
    // Load shipping methods
    
    $I->waitForElementVisible("//span[normalize-space()='Get shipping methods and rates']");
    $I->click("//span[normalize-space()='Get shipping methods and rates']/parent::a");
    $this->_waitForLoading($I);
    // Select Flat Rate explicitly
    $I->waitForElementClickable('#s_method_flatrate_flatrate');
    $I->click('#s_method_flatrate_flatrate');
    $this->_waitForLoading($I);
  }

  protected function _addPaymentMethodToOrder(AcceptanceTester $I, string $cardNumber = '4242424242424242')
  {
    $I->waitForElementClickable('#p_method_publicsquare_payments');
    $I->click('#p_method_publicsquare_payments');
    $this->_waitForLoading($I);
    $this->_fillCardForm($I, $cardNumber, '12/29', '123', '#publicsquare-elements-form');
  }

  protected function _submitOrder(AcceptanceTester $I, string $expectedMessage = 'You created the order.', bool $acceptPopup = false)
  {
    $I->waitForElementClickable('#edit_form #order-totals button');
    $I->click('#edit_form #order-totals button');
    if ($acceptPopup) {
      $I->acceptPopup();
    } else {
      $this->_waitForLoading($I);
      $I->waitForText($expectedMessage);
    }
  }
}
