<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class AcceptanceBase
{
    const IFRAME_CSS = '#publicsquare-elements-form iframe';

    protected $customerEmail = "";  // this will be dynamicaly produced

    protected $rollbackTransactions = true;

    //public function _before(\Codeception\TestInterface $test)
    public function _before(AcceptanceTester $I)
    {
        if ($this->rollbackTransactions) {
            echo "Beginning SQL Transaction. \n";
            $I->startDatabaseTransaction();
        }
    }

    //public function _after(\Codeception\TestInterface $test)
    public function _after(AcceptanceTester $I)
    {
        if ($this->rollbackTransactions) {
            echo "Rolling back SQL Transaction. \n";
            $I->rollbackDatabaseTransaction();
        }
    }

    protected function _initialize(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $this->_getPastBrowserWarning($I);
    }

    protected function _clickElementIfExists(AcceptanceTester $I, $selector): void
    {
        try {
            $I->seeElement($selector);
            $I->click($selector);
        } catch (\Exception $e) {
            // do nothing
        }
    }

    protected function _getPastBrowserWarning(AcceptanceTester $I): void
    {
        try {
            $I->see('Your connection is not private');
            // get past cert error page
            $I->click('button[id="details-button"]');
            $I->waitForElementClickable('#proceed-link');
            $I->click('#proceed-link');
        } catch (\Exception $e) {
            // do nothing if we don't see 'Your connection is not private'
        }
    }

    protected function _adminLogin(AcceptanceTester $I): void
    {
        $I->amOnPage('/admin');

        try {
            $I->see("Dashboard");   // skip logging in if we are already logged in.
        }
        catch (\Exception $e) {
            // login page
            $I->waitForElement('#username');
            $I->fillField('#username', 'admin');
            $I->fillField('#login', 'AdminPassword123');
            $I->click('.form-actions .action-login');
            $I->waitForText('Dashboard');
        }
    }

    protected function _customerLogin(AcceptanceTester $I): void
    {
        $I->amOnPage('/customer/account/login');
        // login page
        $I->fillField('#email', 'roni_cost@example.com');
        $I->fillField('#password', 'roni_cost3@example.com');
        $I->click('.form-login .action.login.primary');
        $I->waitForText('My Account');
    }

    protected function _waitForLoading(AcceptanceTester $I): void
    {
        $I->waitForElementNotVisible('img[alt="Loading..."]', 30);
        $I->waitForElementNotVisible('.loading-mask', 30);
        $I->waitForElementNotVisible('.admin__form-loading-mask', 30);
        $I->waitForElementNotVisible('.admin__data-grid-loading-mask', 30);
        $I->waitForElementNotVisible('.popup-loading img', 30);
    }

    protected function _goToPublicSquarePayments(AcceptanceTester $I): void
    {
        $this->_adminLogin($I);

        $this->_clickElementIfExists($I, '.admin__form-loading-mask');
        $this->_clickElementIfExists($I, '.admin-usage-notification .action-secondary');

        $this->_waitForLoading($I);

        //$I->waitForElementVisible('#menu-magento-backend-stores a');
        $I->waitForElementClickable('#menu-magento-backend-stores a', 30);
        $I->click('#menu-magento-backend-stores a');
        $I->waitForText('Configuration');
        $I->waitForText('Terms and Conditions');
        $I->click('.submenu .item-system-config a');
        $I->waitForText('Country Options');
        $I->click('#system_config_tabs div.config-nav-block:nth-child(5)');
        $I->waitForText('Payment Methods');
        $I->click('Payment Methods');

        // click on button.
        $I->click('#payment_us_publicsquare_payments-head');
        $I->waitForText('PublicSquare Secret API key');
    }

    protected function _adminEnableAuthorizeCapture(AcceptanceTester $I): void
    {
        $this->_goToPublicSquarePayments($I);

        $I->uncheckOption('#payment_us_publicsquare_payments_payment_action_inherit');

        // select sale
        $I->selectOption('select#payment_us_publicsquare_payments_payment_action', 'sale');
        $I->click('Save Config');
        $I->waitForText('You saved the configuration');
        $I->see('You saved the configuration');
    }


    protected function _adminEnableAndConfigurePublicSquarePayments(AcceptanceTester $I): void
    {
        $this->_goToPublicSquarePayments($I);

        $I->uncheckOption('#payment_us_publicsquare_payments_active_inherit');

        // select sale
        $I->selectOption('select#payment_us_publicsquare_payments_active', '1');

        $public_key = getenv("PUBLICSQUARE_PUBLIC_KEY");
        $secret_key = getenv("PUBLICSQUARE_SECRET_KEY");
        echo "public_key=$public_key, secret_key=$secret_key\n";
        if ($public_key && $secret_key) {
            echo "Setting the PublicSquare public_key and secret_key\n";
            $I->fillField("#payment_us_publicsquare_payments_publicsquare_api_public_key", $public_key);
            $I->fillField("#payment_us_publicsquare_payments_publicsquare_api_secret_key", $secret_key);
        }

        $I->click('Save Config');
        $I->waitForText('You saved the configuration');
        $I->see('You saved the configuration');
    }




    protected function _adminEnableAuthorize(AcceptanceTester $I): void
    {
        $I->amOnPage('/admin');

        // login page
        $I->fillField('#username', 'admin');
        $I->fillField('#login', 'AdminPassword123');
        $I->click('.form-actions .action-login');

        $I->waitForText('Dashboard');
        $I->click('#menu-magento-backend-stores a');
        $I->waitForText('Configuration');
        $I->waitForText('Terms and Conditions');
        $I->click('.submenu .item-system-config a');
        $I->waitForText('Country Options');
        $I->click('#system_config_tabs div.config-nav-block:nth-child(5)');
        $I->waitForText('Payment Methods');
        $I->click('Payment Methods');
        //$I->selectOption('select#payment_us_recommended_solutions_magento_payments_legacy_apple_pay_payment_action', 'authorize_capture');
        $I->selectOption('select#payment_us_recommended_solutions_magento_payments_legacy_hosted_fields_payment_action', 'authorize');
        $I->click('Save Config');
        $I->waitForText('You saved the configuration');
        $I->see('You saved the configuration');
    }

    protected function _adminGoToOrder(AcceptanceTester $I): void
    {
        $this->_adminLogin($I);
        $I->amOnPage('/admin/sales/order/index/');
        $I->waitForText('Orders');
        $I->waitForText('View');
        $I->waitForElementClickable('.data-grid a');
        $I->click('.data-grid a');
    }

    protected function _adminGoToOrderInvoice(AcceptanceTester $I): void
    {
        $this->_adminGoToOrder($I);

        $I->waitForElementVisible('#sales_order_view_tabs_order_invoices');
        $I->click('#sales_order_view_tabs_order_invoices');
        $I->waitForText('View');
        $I->waitForElementClickable('.data-grid a');
        $I->click('.data-grid a');
    }

    protected function _adminGoToOrderCreditMemo(AcceptanceTester $I): void
    {
        $this->_adminGoToOrderInvoice($I);
        $I->waitForText('Credit Memo');
        $I->click('Credit Memo');
    }

    protected function _adminCreateRefund(AcceptanceTester $I): void
    {
        $this->_adminGoToOrderCreditMemo($I);

        $I->waitForElementClickable('.submit-button.refund.primary');
        $I->click('.submit-button.refund.primary');
        $I->waitForText('You created the credit memo');
    }

    protected function _adminCreateVirtualProduct(AcceptanceTester $I): void
    {
        $this->_adminLogin($I);
        $I->waitForElementClickable('#menu-magento-catalog-catalog>a');
        $I->click('#menu-magento-catalog-catalog>a');
        $I->waitForElementClickable('[data-ui-id="menu-magento-catalog-catalog-products"] span');
        $I->click('[data-ui-id="menu-magento-catalog-catalog-products"] span');
        $I->waitForText('Add Product');
        $I->waitForElementClickable('button[data-ui-id="products-list-add-new-product-button-dropdown"]');
        $I->click('button[data-ui-id="products-list-add-new-product-button-dropdown"]');
        $I->waitForElementClickable('[data-ui-id="products-list-add-new-product-button-item-virtual"]');
        $I->click('[data-ui-id="products-list-add-new-product-button-item-virtual"]');
        $I->waitForElementVisible('input[name="product[name]"]');
        $I->fillField('input[name="product[name]"]', 'Gift Card');
        $I->waitForElementVisible('input[name="product[sku]"]');
        $I->fillField('input[name="product[sku]"]', 'gift-card');
        $I->waitForElementVisible('input[name="product[price]"]');
        $I->fillField('input[name="product[price]"]', '75');
        $I->fillField('input[name="product[quantity_and_stock_status][qty]"]', '100');
        $I->selectOption('select[name="product[quantity_and_stock_status][is_in_stock]"]', 1);
        $I->click('Save');
        $this->_waitForLoading($I);
    }

    protected function _customerGoToAnOrder(AcceptanceTester $I, $doLogin=true): void
    {
        if ($doLogin) {
            $this->_customerLogin($I);
        }
        $I->amOnPage('/sales/order/history/');
        $I->waitForText('My Orders');
        $I->click('View Order');
        $I->waitForText('Payment Method');
    }


    protected function _addProductToCart(AcceptanceTester $I): void
    {
        $I->amOnPage('/livingston-all-purpose-tight.html');
        $I->waitForElement('.swatch-option.text');
        $I->click('(//div[@class="swatch-option text"])[1]');
        $I->click('div[data-option-label="Black"]');
        $I->click('Add to Cart');
        $I->waitForText('shopping cart');
    }

    protected function _addVirtualProductToCart(AcceptanceTester $I): void
    {
        $I->amOnPage('/gift-card.html');
        $I->waitForElementClickable('button[title="Add to Cart"]');
        $I->click('Add to Cart');
        $I->waitForText('shopping cart');
    }

    protected function _changeCartQuantity(AcceptanceTester $I, $quantity = 10)
    {
        $I->amOnPage('/checkout/cart');
        $I->waitForElementVisible('input[data-role="cart-item-qty"]');
        $I->fillField('input[data-role="cart-item-qty"]', $quantity);
        $I->click('button[name="update_cart_action"]');
        $this->_waitForLoading($I);
    }

    protected function _generateUniqueEmail()
    {
        $time = time();
        $this->customerEmail = "BillyBob{$time}@example.com";
    }

    protected function _goToCheckout(AcceptanceTester $I) {
        $I->amOnPage('/checkout');
        $I->waitForElement('#customer-email');
        $this->_generateUniqueEmail();
        $I->fillField('#customer-email', $this->customerEmail);
        $I->fillField('firstname', 'Billy');
        $I->fillField('lastname', 'Bob');
        $I->fillField('street[0]', '123 Main St');
        $I->selectOption('select[name="country_id"]', 'US');
        $I->selectOption('select[name="region_id"]', '15');
        $I->fillField('city', 'Newark');
        $I->fillField('postcode', '19711');
        $I->fillField('telephone', '1234567890');
        $firstRadio = '.table-checkout-shipping-method tbody tr:nth-child(1) input[type="radio"]';
        $I->waitForElementClickable($firstRadio);
        $I->click($firstRadio);
        $I->click('Next');
        $I->waitForText('Payment Method');
    }

    protected function _goToVirtualProductCheckout(AcceptanceTester $I) {
        $I->amOnPage('/checkout');
        $I->waitForElement('#customer-email');
        $this->_generateUniqueEmail();
        $I->fillField('#customer-email', $this->customerEmail);
    }

    protected function _goToCheckoutWhileLoggedIn(AcceptanceTester $I) {
        $I->amOnPage('/checkout');
        $firstRadio = '.table-checkout-shipping-method tbody tr:nth-child(1) input[type="radio"]';
        $I->waitForElementClickable($firstRadio);
        $I->click($firstRadio);
        $I->click('Next');
    }

    protected function _makeSurePaymentMethodIsVisible(AcceptanceTester $I)
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
        $this->_waitForLoading($I);
    }

    protected function _checkoutWithCard(AcceptanceTester $I, $cardNumber='4242424242424242', $waitString='Thank you for your purchase!')
    {
        $this->_makeSurePaymentMethodIsVisible($I);
        $I->waitForElementVisible($this::IFRAME_CSS);
        $x = $I->grabAttributeFrom($this::IFRAME_CSS, 'id');
        $I->switchToIframe('//*[@id="'.$x.'"]');
        $I->fillField('//*[@id="cardNumber"]', $cardNumber);
        $I->fillField('//*[@id="expirationDate"]', '12/29');
        $I->fillField('//*[@id="cvc"]', '123');
        $I->switchToIframe();
        $submitButton = '.payment-method._active button[type="submit"]';
        $I->waitForElementClickable($submitButton);
        $I->click($submitButton);
        $I->waitForElementNotVisible('.loading-mask', 30);
        $I->waitForText($waitString);
    }

    protected function _checkoutWithVirtualCard(AcceptanceTester $I, $cardNumber='4242424242424242', $waitString='Thank you for your purchase!')
    {
        $this->_makeSurePaymentMethodIsVisible($I);
        $I->fillField('.payment-method._active input[name="firstname"]', 'Billy');
        $I->fillField('.payment-method._active input[name="lastname"]', 'Bob');
        $I->fillField('.payment-method._active input[name="street[0]"]', '123 Main St');
        $I->selectOption('.payment-method._active select[name="country_id"]', 'US');
        $I->selectOption('.payment-method._active select[name="region_id"]', '15');
        $I->fillField('.payment-method._active input[name="city"]', 'Newark');
        $I->fillField('.payment-method._active input[name="postcode"]', '19711');
        $I->fillField('.payment-method._active input[name="telephone"]', '1234567890');
        $I->click('.payment-method._active button.action-update');
        $this->_waitForLoading($I);
        $I->waitForElementVisible($this::IFRAME_CSS);
        $x = $I->grabAttributeFrom($this::IFRAME_CSS, 'id');
        $I->switchToIframe('//*[@id="'.$x.'"]');
        $I->fillField('//*[@id="cardNumber"]', $cardNumber);
        $I->fillField('//*[@id="expirationDate"]', '12/29');
        $I->fillField('//*[@id="cvc"]', '123');
        $I->switchToIframe();
        $submitButton = '.payment-method._active button[type="submit"]';
        $I->waitForElementClickable($submitButton);
        $I->click($submitButton);
        $I->waitForText($waitString);
    }
}
