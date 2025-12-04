<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use Codeception\Step\Argument\PasswordArgument;

class AcceptanceBase
{
    const IFRAME_CSS = '#psq-card iframe';

    const DEFAULT_CONTAINER_SELECTOR = '#publicsquare_payments';

    protected $customerEmail = "";  // this will be dynamicaly produced

    protected $rollbackTransactions = false;

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
            $password = new PasswordArgument('AdminPassword1234');
            $I->fillField('#login', $password);
            $I->click('.form-actions .action-login');
            $I->waitForText('Dashboard');
        }
        // Maybe click "Don't allow" if Adobe usage data collection modal is shown
        $this->_waitForLoading($I);
        $I->tryToClick("Don't Allow");
    }

    protected function _customerLogin(AcceptanceTester $I): void
    {
        $I->amOnPage('/customer/account/login');
        // login page
        $I->fillField('[type="email"]', 'roni_cost@example.com');
        $password = new PasswordArgument('roni_cost3@example.com');
        $I->fillField('[type="password"]', $password);
        $I->click('.form-login .action.login.primary');
        $I->waitForText('My Account');
    }

    protected function _waitForLoading(AcceptanceTester $I): void
    {
        $I->waitForElementNotVisible('img[alt="Loading..."]', 60);
        $I->waitForElementNotVisible('.loading-mask', 60);
        $I->waitForElementNotVisible('.admin__form-loading-mask', 60);
        $I->waitForElementNotVisible('.admin__data-grid-loading-mask', 60);
        $I->waitForElementNotVisible('.popup-loading img', 60);
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
        $I->selectOption('select#payment_us_publicsquare_payments_payment_action', 'authorize_capture');
        $I->click('Save Config');
        $I->waitForText('You saved the configuration');
        $I->see('You saved the configuration');
    }

    protected function _adminEnableAuthorize(AcceptanceTester $I): void
    {
        $I->amOnPage('/admin');

        // login page
        $I->fillField('#username', 'admin');
        $I->fillField('#login', 'AdminPassword1234');
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
        $I->fillField('input[name="product[quantity_and_stock_status][qty]"]', '1000');
        $I->selectOption('select[name="product[quantity_and_stock_status][is_in_stock]"]', 1);
        $I->click('Save');
        $this->_waitForLoading($I);
        $I->runShellCommand('bin/magento indexer:reindex cataloginventory_stock');
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
        $I->waitForElementNotVisible(".loading-mask", 60);
        try {
            // Check if there is a saved address
            $I->grabTextFrom('.new-address-popup>button.action-show-popup');
            // Then do nothing
        } catch (\Exception $e) {
            // If there is no saved address, then we need to create a new one
            /**
             * This is a workaround to fix the issue where the customer-email field is not visible.
             * https://github.com/magento/magento2/issues/38274
             */
            try {
                $I->waitForElement('#customer-email', 5);
            } catch (\Exception $e) {
                // do nothing
                $I->reloadPage();
                $I->waitForElement('#customer-email');
            }
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
        }
        $firstRadio = '.table-checkout-shipping-method tbody tr:nth-child(1) input[type="radio"]';
        $I->waitForElementClickable($firstRadio);
        $I->click($firstRadio);
        $I->click('Next');
        $I->waitForText('Payment Method');
        $I->waitForText('Order Total');
    }

    protected function _goToVirtualProductCheckout(AcceptanceTester $I) {
        $I->amOnPage('/checkout');
        $I->waitForElementNotVisible(".loading-mask", 60);
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

    protected function _makeSurePaymentMethodIsVisible(AcceptanceTester $I, $containerSelector = self::DEFAULT_CONTAINER_SELECTOR, $iframeSelector = self::IFRAME_CSS)
    {
        echo "Checking for payment method to be visible...\n";
        $I->waitForElementNotVisible(".loading-mask", 60);
        $I->waitForElementVisible($containerSelector, 30);
        $I->waitForElementClickable($containerSelector, 30);
        $I->waitForElementNotVisible(".loading-mask", 60);
        $I->click($containerSelector);
        $I->waitForElementVisible($iframeSelector);
        $x = $I->grabAttributeFrom($iframeSelector, 'id');
        $I->switchToIframe('//*[@id="'.$x.'"]');
        $I->waitForElementVisible('//*[@id="cardNumber"]');
        $I->waitForElementVisible('//*[@id="expirationDate"]');
        $I->waitForElementVisible('//*[@id="cvc"]');
        $I->switchToIframe();
        $this->_waitForLoading($I);
        echo "Payment method is visible.\n";
    }

    protected function _clearField(AcceptanceTester $I, $field)
    {
        $I->click($field);
        $I->pressKey($field, [\Facebook\WebDriver\WebDriverKeys::COMMAND, 'a']);
        $I->pressKey($field, [\Facebook\WebDriver\WebDriverKeys::BACKSPACE]);
    }

    protected function _clearCardForm(AcceptanceTester $I, $containerSelector = self::DEFAULT_CONTAINER_SELECTOR, $iframeSelector = self::IFRAME_CSS)
    {
        $this->_makeSurePaymentMethodIsVisible($I, $containerSelector, $iframeSelector);
        $I->waitForElementVisible($iframeSelector);
        $x = $I->grabAttributeFrom($iframeSelector, 'id');
        $I->switchToIframe('//*[@id="'.$x.'"]');
        $this->_clearField($I, '//*[@id="cardNumber"]');
        $this->_clearField($I, '//*[@id="expirationDate"]');
        $this->_clearField($I, '//*[@id="cvc"]');
        $I->wait(1);
        $I->switchToIframe();
    }

    protected function _fillCardForm(AcceptanceTester $I, $cardNumber = '4242424242424242', $expirationDate = '12/29', $cvc = '123', $containerSelector = self::DEFAULT_CONTAINER_SELECTOR, $iframeSelector = self::IFRAME_CSS)
    {
        echo "Filling card form\n";

        $this->_makeSurePaymentMethodIsVisible($I, $containerSelector, $iframeSelector);
        $this->_clearCardForm($I, $containerSelector, $iframeSelector);
        $I->waitForElementVisible($iframeSelector);
        $x = $I->grabAttributeFrom($iframeSelector, 'id');
        $I->switchToIframe('//*[@id="'.$x.'"]');
        $I->fillField('//*[@id="cardNumber"]', $cardNumber);
        $I->fillField('//*[@id="expirationDate"]', $expirationDate);
        $I->fillField('//*[@id="cvc"]', $cvc);
        $I->switchToIframe();
        echo "Card form filled\n";
    }

    protected function _enableSaveCard(AcceptanceTester $I)
    {
        $I->waitForElementVisible('input[name="vault[is_enabled]"]');
        $I->click('input[name="vault[is_enabled]"]');
    }

    protected function _checkoutWithCard(AcceptanceTester $I, $cardNumber = '4242424242424242', $waitString = 'Thank you for your purchase!', $termsAndConditions = false, $saveCard = false, $containerSelector = self::DEFAULT_CONTAINER_SELECTOR)
    {
        $I->reloadPage();
        $I->amOnPage('/checkout/#payment');
        $this->_fillCardForm($I, $cardNumber, '12/29', '123', $containerSelector);
        if ($saveCard) {
            $this->_enableSaveCard($I);
        }
        if ($termsAndConditions) {
            $this->_checkTermsAndConditions($I);
        }
        $submitButton = '.payment-method._active button[type="submit"]';
        $I->waitForElementClickable($submitButton);
        $I->click($submitButton);
        $I->waitForText($waitString, 30);
        $I->waitForElementNotVisible('.loading-mask', 60);
    }

    protected function _checkoutWithVirtualCard(AcceptanceTester $I, $cardNumber='4242424242424242', $containerSelector = self::DEFAULT_CONTAINER_SELECTOR, $waitString='Thank you for your purchase!', $iframeSelector = self::IFRAME_CSS)
    {
        $this->_makeSurePaymentMethodIsVisible($I, $containerSelector, $iframeSelector);
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
        $this->_fillCardForm($I, $cardNumber);
        $submitButton = '.payment-method._active button[type="submit"]';
        $I->waitForElementClickable($submitButton);
        $I->click($submitButton);
        $I->waitForText($waitString);
    }

    protected function _checkoutWithSavedCard(AcceptanceTester $I, $waitString = 'Thank you for your purchase!')
    {
        $this->_waitForLoading($I);
        $I->see('Payment Method');
        $I->checkOption('.psq-form .psq-form__cell--vault input.psq-form__input--vault');
        $submitButton = '.payment-method._active button[type="submit"]';
        $this->_waitForLoading($I);
        $I->waitForElementClickable($submitButton);
        $I->click($submitButton);
        $I->waitForText($waitString, 10);
        $I->waitForElementNotVisible('.loading-mask', 60);
    }

    protected function _checkTermsAndConditions(AcceptanceTester $I)
    {
        $I->checkOption('._active .checkout-agreement input');
    }

    protected function _addInventoryToProduct(AcceptanceTester $I, $productName, $quantity=1000)
    {

        $this->_adminLogin($I);

        $I->click("Catalog");
        $I->waitForText("Products");
        $I->waitForText("Categories");
        $I->click("Products");
        $I->waitForElementVisible('.data-grid-search-control');
        $I->fillField('.data-grid-search-control', "$productName\n");
        $I->waitForText("$productName");
        $I->waitForElement("a[aria-label='Edit $productName']");
        $I->click("a[aria-label='Edit $productName']");
        $I->waitForElement("input[name='product[quantity_and_stock_status][qty]']");
        $I->fillField("input[name='product[quantity_and_stock_status][qty]']", "$quantity");
        $I->click("Save");
        $I->waitForText("You saved the product.");
    }

    protected function _doSuccessfulCheckout(AcceptanceTester $I,$cardNumber = '4242424242424242', $waitString = 'Thank you for your purchase!', $termsAndConditions = false, $saveCard = false, $cardContainerSelector = self::DEFAULT_CONTAINER_SELECTOR) {
        $this->_initialize($I);
        $this->_addProductToCart($I);
        $this->_goToCheckout($I);
        $this->_checkoutWithCard($I, $cardNumber, $waitString, $termsAndConditions, $saveCard, $cardContainerSelector);}
}
