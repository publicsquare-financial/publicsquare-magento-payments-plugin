<?php

namespace Tests\Acceptance;

use Tests\Pages\AdminOrderDetailPage;
use Tests\Support\AcceptanceTester;


class AdminOrderPaymentMethodTableCest extends AdminOrderDetailPage
{
    /**
     * @param AcceptanceTester $I
     * @return void
     * @skip This test needs to be re-worked
     */
    public function paymentInformationTableIsVisible(AcceptanceTester $I, )
    {
        // This goes to normal checkout...
        $this->_doSuccessfulCheckout($I);
        $this->_goToOrderDetail($I);
        $this->_paymentInformationTableIsVisible($I);
    }

    /**
     * @param AcceptanceTester $I
     * @return void
     * @skip This test needs to be re-worked
     *
     */
    public function paymentDetailsLinkHasCorrectUrl(AcceptanceTester $I)
    {
        $this->_doSuccessfulCheckout($I);
        $this->_goToOrderDetail($I);
        $this->_paymentDetailsLinkHasCorrectUrl($I);
    }
}
