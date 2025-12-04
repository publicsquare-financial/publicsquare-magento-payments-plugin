<?php

namespace Tests\Acceptance;

use Tests\Pages\AdminOrderDetailPage;
use Tests\Support\AcceptanceTester;

class AdminOrderPaymentMethodTableCest extends AdminOrderDetailPage
{
    public function paymentInformationTableIsVisible(AcceptanceTester $I, )
    {
        $this->_doSuccessfulCheckout($I);
        $this->_goToOrderDetail($I);
        $this->_paymentInformationTableIsVisible($I);
    }
    public function paymentDetailsLinkHasCorrectUrl(AcceptanceTester $I)
    {
        $this->_doSuccessfulCheckout($I, '4242424242424242', 'Thank you for your purchase!', false, false, '#publicsquare-elements-form iframe');
        $this->_goToOrderDetail($I);
        $this->_paymentDetailsLinkHasCorrectUrl($I);
    }
}
