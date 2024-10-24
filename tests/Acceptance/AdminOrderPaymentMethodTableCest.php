<?php

namespace Tests\Acceptance;

use Tests\Pages\AdminOrderDetailPage;
use Tests\Support\AcceptanceTester;

class AdminOrderPaymentMethodTableCest extends AdminOrderDetailPage
{
    public function paymentInformationTableIsVisible(AcceptanceTester $I)
    {
        $this->_goToOrderDetail($I);
        $this->_paymentInformationTableIsVisible($I);
    }
}
