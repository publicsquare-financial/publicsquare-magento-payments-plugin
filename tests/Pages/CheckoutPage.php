<?php

namespace Tests\Pages;

use Tests\Support\AcceptanceTester;

class CheckoutPage
{
  const IFRAME_CSS = '#publicsquare-elements-form iframe';
  public function __construct()
  {
    $this->url = '/checkout';
  }

  public function fillCustomerEmail(AcceptanceTester $I)
  {
    $I->fillField('#customer-email', 'roni_cost@example.com');
  }

  public function addProductToCart(AcceptanceTester $I) {
    $I->amOnPage('/livingston-all-purpose-tight.html');
    $I->waitForElement('.swatch-option.text');
    $I->click('(//div[@class="swatch-option text"])[1]');
    $I->click('div[data-option-label="Black"]');
    $I->click('Add to Cart');
    $I->waitForText('shopping cart');
  }

  public function goToCheckout(AcceptanceTester $I) {
    $I->amOnPage('/checkout');
    $I->waitForElement('#customer-email');
    $I->fillField('#customer-email', 'roni_cost@example.com');
    $I->fillField('firstname', 'roni');
    $I->fillField('lastname', 'cost');
    $I->fillField('street[0]', '123 Main St');
    $I->selectOption('select[name="country_id"]', 'US');
    $I->selectOption('select[name="region_id"]', '23');
    $I->fillField('city', 'Chicago');
    $I->fillField('postcode', '60601');
    $I->fillField('telephone', '1234567890');
    $firstRadio = '.table-checkout-shipping-method tbody tr:nth-child(1) input[type="radio"]';
    $I->waitForElementClickable($firstRadio);
    $I->click($firstRadio);
    $I->click('Next');
  }

  public function makeSurePaymentMethodIsVisible(AcceptanceTester $I)
  {
    $publicsquarePayments = '#publicsquare_payments';
    $I->waitForElementClickable($publicsquarePayments);
    $I->click($publicsquarePayments);
    $I->see('Credit/Debit Card Number');
    $I->waitForElementVisible($this::IFRAME_CSS);
    $x = $I->grabAttributeFrom($this::IFRAME_CSS, 'id');
    $I->switchToIframe('//*[@id="'.$x.'"]');
    $I->waitForElementVisible('//*[@id="cardNumber"]');
    $I->waitForElementVisible('//*[@id="expirationDate"]');
    $I->waitForElementVisible('//*[@id="cvc"]');
    $I->switchToIframe();
  }

  public function checkoutWithCard(AcceptanceTester $I)
  {
    $this->makeSurePaymentMethodIsVisible($I);
    $I->waitForElementVisible($this::IFRAME_CSS);
    $x = $I->grabAttributeFrom($this::IFRAME_CSS, 'id');
    $I->switchToIframe('//*[@id="'.$x.'"]');
    $I->fillField('//*[@id="cardNumber"]', '4111111111111111');
    $I->fillField('//*[@id="expirationDate"]', '12/25');
    $I->fillField('//*[@id="cvc"]', '123');
    $I->switchToIframe();
    $submitButton = '.payment-method._active button[type="submit"]';
    $I->waitForElementClickable($submitButton);
    $I->click($submitButton);
    $I->waitForText('Thank you for your purchase!');
  }
}