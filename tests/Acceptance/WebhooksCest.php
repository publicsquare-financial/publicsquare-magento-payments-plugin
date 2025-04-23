<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use Tests\Bases\WebhookBase;

class WebhooksCest extends WebhookBase
{
    public function paymentUpdateWorks(AcceptanceTester $I)
    {
        $this->setWebhookSigningSecret($I);
        $paymentId = $this->getRecentOrderPaymentId($I);
        $requestBody = [
            "id" => "evnt_5jxWRFNLCAWeegrkCAG3a9DGc",
            "account_id" => "acc_B518niGwGYKzig6vtrRVZGGGV",
            "environment" => "production",
            "event_type" => "payment:update",
            "entity_type" => "connection",
            "entity_id" => "conn_73t7igFxDZvN9hypi7yoPwbxy",
            "entity" => [
                "id" => $paymentId,
                "merchant_account_id" => "acc_B518niGwGYKzig6vtrRVZGGGV",
                "merchant_account_name" => "Test Company, LLC",
                "seller_account_id" => "acc_8ooQs32UCdriBvrHnVWbTmJbY",
                "seller_account_name" => "Widgets Co",
                "status" => "succeeded",
                "created_at" => "2024-06-30T01:02:29.212Z",
                "modified_at" => "2024-06-30T01:02:29.212Z"
            ],
            "created_at" => "2024-06-30T01:02:29.212Z"
        ];
        $signature = $this->createResponseBodySignature($I, $requestBody);
        $I->haveHttpHeader('X-Signature', $signature);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/publicsquare/webhooks', $requestBody);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $additionalInformation = json_decode($I->grabFromDatabase('sales_order_payment', 'additional_information', ['cc_trans_id' => $paymentId]), true);
        codecept_debug($additionalInformation['raw_details_info']);
        $I->assertArrayHasKey('status', $additionalInformation['raw_details_info']);
    }

    // public function refundUpdateWorks(AcceptanceTester $I)
    // {
    //     $this->_initialize($I);
    // }
}
