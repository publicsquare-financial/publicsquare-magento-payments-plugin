<?php

namespace Tests\Acceptance;

use Tests\Pages\AdminOrderDetailPage;
use Tests\Support\AcceptanceTester;


class AdminOrderPaymentMethodTableCest extends AdminOrderDetailPage
{
    public function paymentInformationTableIsVisible(AcceptanceTester $I, )
    {
        // This goes to normal checkout...
        $this->_doSuccessfulCheckout($I);
        $this->_goToOrderDetail($I);
        $this->_paymentInformationTableIsVisible($I);
    }
    public function paymentDetailsLinkHasCorrectUrl(AcceptanceTester $I)
    {
        $this->_doSuccessfulCheckout($I);
        $this->_goToOrderDetail($I);
        $this->_paymentDetailsLinkHasCorrectUrl($I);
    }
}
