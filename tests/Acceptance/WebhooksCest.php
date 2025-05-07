<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;
use Tests\Bases\WebhookBase;

class WebhooksCest extends WebhookBase
{
    public function paymentSucceededUpdateWorks(AcceptanceTester $I)
    {
        $this->setWebhookSigningSecret($I);
        $orderIds = $this->getRecentSalesOrderPayment($I);
        $paymentId = $orderIds['paymentId'];
        $orderId = $orderIds['orderId'];
        $requestBody = [
            "id" => "evnt_5jxWRFNLCAWeegrkCAG3a9DGc",
            "account_id" => "acc_B518niGwGYKzig6vtrRVZGGGV",
            "environment" => "production",
            "event_type" => "payment:update",
            "entity_type" => "connection",
            "entity_id" => "conn_73t7igFxDZvN9hypi7yoPwbxy",
            "entity" => [
                "id" => $paymentId,
                "external_id" => $orderId,
                "merchant_account_id" => "acc_B518niGwGYKzig6vtrRVZGGGV",
                "merchant_account_name" => "Test Company, LLC",
                "seller_account_id" => "acc_8ooQs32UCdriBvrHnVWbTmJbY",
                "seller_account_name" => "Widgets Co",
                "status" => 'succeeded',
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
        $I->assertArrayHasKey('status', $additionalInformation['raw_details_info']);
        $I->assertEquals($requestBody['entity']['status'], $additionalInformation['raw_details_info']['status']);
    }

    /**
     * Cancel a payment from the PSQ portal, completely outside of Magento
     * This should NOT create a new credit memo as that will be handled by the refund:update webhook
     */
    public function paymentCancelledUpdateWorks(AcceptanceTester $I)
    {
        $this->setWebhookSigningSecret($I);
        $salesOrderPayment = $this->getRecentSalesOrderPayment($I);
        $paymentId = $salesOrderPayment['paymentId'];
        $orderId = $salesOrderPayment['orderId'];
        $requestBody = [
            "id" => "evnt_5jxWRFNLCAWeegrkCAG3a9DGc",
            "account_id" => "acc_B518niGwGYKzig6vtrRVZGGGV",
            "environment" => "production",
            "event_type" => "payment:update",
            "entity_type" => "connection",
            "entity_id" => "conn_73t7igFxDZvN9hypi7yoPwbxy",
            "entity" => [
                "id" => $paymentId,
                "external_id" => $orderId,
                "merchant_account_id" => "acc_B518niGwGYKzig6vtrRVZGGGV",
                "merchant_account_name" => "Test Company, LLC",
                "seller_account_id" => "acc_8ooQs32UCdriBvrHnVWbTmJbY",
                "seller_account_name" => "Widgets Co",
                "status" => 'cancelled',
                "created_at" => "2024-06-30T01:02:29.212Z",
                "modified_at" => "2024-06-30T01:02:29.212Z",
                "amount" => $salesOrderPayment['amount']
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
        $I->assertArrayHasKey('status', $additionalInformation['raw_details_info']);
        $I->assertEquals('cancelled', $additionalInformation['raw_details_info']['status']);
        $I->dontSeeInDatabase('sales_creditmemo', ['order_id' => $orderId]);
    }

    public function existingRefundUpdateWorks(AcceptanceTester $I)
    {
        $this->setWebhookSigningSecret($I);
        $creditMemo = $this->getRecentOrderCreditMemo($I);
        $refundId = $creditMemo['refundId'];
        $orderId = $creditMemo['orderId'];
        $paymentId = $creditMemo['paymentId'];
        $amount = $creditMemo['amount'];
        $requestBody = [
            "id" => "evnt_5jxWRFNLCAWeegrkCAG3a9DGc",
            "account_id" => "acc_B518niGwGYKzig6vtrRVZGGGV",
            "environment" => "production",
            "event_type" => "refund:update",
            "entity_type" => "connection",
            "entity_id" => "conn_73t7igFxDZvN9hypi7yoPwbxy",
            "entity" => [
                "id" => $refundId,
                "payment_id" => $paymentId,
                "external_id" => $orderId,
                "merchant_account_id" => "acc_B518niGwGYKzig6vtrRVZGGGV",
                "merchant_account_name" => "Test Company, LLC",
                "seller_account_id" => "acc_8ooQs32UCdriBvrHnVWbTmJbY",
                "seller_account_name" => "Widgets Co",
                "status" => 'succeeded',
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
        $I->assertArrayHasKey('status', $additionalInformation['raw_details_info']);
        $I->assertEquals('cancelled', $additionalInformation['raw_details_info']['status']);
        $I->seeInDatabase('sales_order_payment', ['cc_trans_id' => $paymentId, 'amount_refunded' => $amount]);
    }

    /**
     * Create a refund from the PSQ portal, completely outside of Magento
     * This should create a new credit memo in Magento without any items attached to it
     */
    public function newRefundCreatedWorks(AcceptanceTester $I)
    {
        $this->setWebhookSigningSecret($I);
        $salesOrderPayment = $this->getRecentSalesOrderPaymentThatsBeenRefunded($I);
        $paymentId = $salesOrderPayment['paymentId'];
        $orderId = $salesOrderPayment['orderId'];
        $requestBody = [
            "id" => "evnt_5jxWRFNLCAWeegrkCAG3a9DGc",
            "account_id" => "acc_B518niGwGYKzig6vtrRVZGGGV",
            "environment" => "production",
            "event_type" => "refund:update",
            "entity_type" => "connection",
            "entity_id" => "conn_73t7igFxDZvN9hypi7yoPwbxy",
            "entity" => [
                "id" => 'refund_' . time(),
                "payment_id" => $paymentId,
                "external_id" => $orderId,
                "merchant_account_id" => "acc_B518niGwGYKzig6vtrRVZGGGV",
                "merchant_account_name" => "Test Company, LLC",
                "seller_account_id" => "acc_8ooQs32UCdriBvrHnVWbTmJbY",
                "seller_account_name" => "Widgets Co",
                "status" => 'succeeded',
                "created_at" => "2024-06-30T01:02:29.212Z",
                "modified_at" => "2024-06-30T01:02:29.212Z",
                "amount" => $salesOrderPayment['amount']
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
        $I->assertArrayHasKey('status', $additionalInformation['raw_details_info']);
        $I->assertEquals('cancelled', $additionalInformation['raw_details_info']['status']);
        $I->seeInDatabase('sales_creditmemo', ['order_id' => $orderId]);
        $I->dontSeeInDatabase('sales_creditmemo_item', ['order_id' => $orderId]);
        $I->seeInDatabase('sales_order_payment', ['cc_trans_id' => $paymentId, 'amount_refunded' => number_format($salesOrderPayment['amount'] / 100, 4)]);
        $I->seeInDatabase('sales_payment_transaction', ['order_id' => $orderId, 'parent_txn_id' => $paymentId, 'txn_type' => 'refund', 'is_closed' => 1]);
    }
}
