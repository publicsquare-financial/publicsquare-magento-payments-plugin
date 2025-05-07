<?php

namespace Tests\Bases;

use Tests\Support\AcceptanceTester;

/**
 * How to: generate a new RSA key pair with openssl v3
 * Private key: openssl genrsa -traditional -out id_rsa 2048
 * Public key: openssl rsa -in id_rsa -pubout > id_rsa.pub
 * Then remove "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8A" from the public key
 */

class WebhookBase extends AcceptanceBase
{
  protected $rollbackTransactions = false;

  protected function setWebhookSigningSecret(AcceptanceTester $I)
  {
    // // Create a new RSA key pair
    // $newPK = openssl_pkey_new([
    //   'private_key_bits' => 2048,
    //   'private_key_type' => OPENSSL_KEYTYPE_RSA,
    // ]);

    // // Get the private key in PKCS1 format
    // openssl_pkey_export($newPK, $privateKey);
    // // $privateKey = str_replace('-----BEGIN PRIVATE KEY-----', '-----BEGIN RSA PRIVATE KEY-----', $privateKey);
    // // $privateKey = str_replace('-----END PRIVATE KEY-----', '-----END RSA PRIVATE KEY-----', $privateKey);

    // // Get the public key in PKCS1 format
    // $keyDetails = openssl_pkey_get_details($newPK);
    // $publicKey = $keyDetails['key'];
    // $publicKey = str_replace('-----BEGIN PUBLIC KEY-----', '', $publicKey);
    // $publicKey = str_replace('-----END PUBLIC KEY-----', '', $publicKey);
    // $publicKey = str_replace("\n", '', $publicKey);
    // $publicKey = str_replace('MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8A', '', $publicKey);

    $privateKey = <<<EOD
-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEA62rPt5magwvl0GkP9dnJtFnZwQbHjTx7kmLPzxkh34tXFMUh
V2vsbpMtRQJAfvXml8P5wSiI8z1whGTh14NVyhJQ67oWW3b5JgvWaNptGrlsJ0IE
wAoDs9DfcyRxbzECkYnFiKoVK10tGrbNh69miS0HtX6DVbSvswJZvaMFmNdbp+qA
MRQYZnSBPjrp/jpqZfNpjv/zyBeUg5wGc1BIqwECXsVRMSAY0f26r56JU+qTCrwg
kLkLh1qK6YHkD1f4RcwC66cql70Y4R66ZGstqHfU7ee5H0sGsfENOtSEr0HOrq94
sbLRXOp+zgD8P1gj9++pLZ98yWHfmueoyLK8TQIDAQABAoIBAGUu22iGVKrOilAx
V/LLUK57j+QvDcXeoR4h6j+RBcYXFa6Pq+zvLge4qlRmy2HGPv4r9UTYL0Scu6er
1IXUpSLdDHrE2KcnU57Op7EZcJtz5tBYx8HijATVWbUbjMXFwtza4lQDBmZE/gXl
XCFdeiyrdgzD+57ysjG2aOvhDZ7K3kmlH76NntACAJ2v17FgbF+FZ7gSpFh+jsQu
AtP6Gjhf3RB4KI1fuSbm+nf+iY7ZM7EdXs5jmMLMZQN8l4tKIB0ilgusJP6w0HTg
sEiiWZ2W9naWaBlVLi8Z5wCjwr+304c04PXoZ2i+Mz18WczJhBiKZ+dDILELzFIJ
L7W9HuECgYEA9r7lGSKHl2nYWuFFR1TIN71CJfHIiAfKKLJiy+G3gKVvv6otkexY
Y2Vj2SGJbIYTxjeoN9vLoOYcOUUfhVHWLobX2oRjE6zj2gajnxBn7k3EuZZCeczP
EnY4TWGvq9xvu3haZqdpAun18/+Rrwon8eVjX+GC4DMzZ/F8fsiZ4W8CgYEA9D8l
vo/q+UPa49biZFdb+FlH2K+EYIaepCPY+WfRgkYKDK5wHN1Q62KQ/5eZD5hf5tUa
mUbmhGHxGy+aU54ChUNT9MwpsOJcolZKM3RqeG32LZ0udOjLIsygC+BRGDErnr65
6bxC/xRQHckhgxKx+AisaynW4u3AjxfPVoGwaAMCgYEAqLCnpgByXLTzQmaCW5r9
6wWL5K8hHsbckegrHSIqt1vjQ1DQKNRBNWsK0VZZQoWDnV9NtSqiU1UedJTqUNY7
LMHpbq5VogzwFY22bTflJgmq9gphVi4MX53NLjIbzM4+4RcODuJjK6fSC8dszROP
bZQa1WEyfZ7jhSuWpoL0mScCgYBSqVx41fRMUC6wlXUhSH+T2YN7Tkua73SZUJiK
MByz3khgalj/K9fLEhzIo+HlaUhrswvBfEFf5FXZQY8VZZCs0VCEtOQXPUTknBeY
unmeMHj0jxG991tod6Bi5JQNf/anTx1Ugaaa9aD3s65n0dfxfd38lrhnLNfSldhS
CqpNSQKBgE9jgIrvP/Z4S3tebL5NQ2oC/fpBv9kgOrxjaectpmuiO8vxKchv8vUF
/XrzraIRnqSdiHKLglzvBG1lpbjwJk8r0jIk+EcrpGojd3aLtSDweKofuwwVchiU
3AF0TYpE9Z8uAHkig3MNntASojjcEXe97tSOiILnrzVny6kgU2Wd
-----END RSA PRIVATE KEY-----
EOD;

    $publicKey = "MIIBCgKCAQEA62rPt5magwvl0GkP9dnJtFnZwQbHjTx7kmLPzxkh34tXFMUhV2vsbpMtRQJAfvXml8P5wSiI8z1whGTh14NVyhJQ67oWW3b5JgvWaNptGrlsJ0IEwAoDs9DfcyRxbzECkYnFiKoVK10tGrbNh69miS0HtX6DVbSvswJZvaMFmNdbp+qAMRQYZnSBPjrp/jpqZfNpjv/zyBeUg5wGc1BIqwECXsVRMSAY0f26r56JU+qTCrwgkLkLh1qK6YHkD1f4RcwC66cql70Y4R66ZGstqHfU7ee5H0sGsfENOtSEr0HOrq94sbLRXOp+zgD8P1gj9++pLZ98yWHfmueoyLK8TQIDAQAB";

    // Store both keys in the database
    if (!$I->tryToSeeInDatabase('core_config_data', ['path' => 'payment/publicsquare_payments/publicsquare_webhook_signing_secret'])) {
      $I->haveInDatabase('core_config_data', [
        'path' => 'payment/publicsquare_payments/publicsquare_webhook_signing_secret',
        'value' => $publicKey,
        'scope' => 'default',
        'scope_id' => 0
      ]);
    } else {
      $I->updateInDatabase('core_config_data', [
        'value' => $publicKey,
      ], [
        'path' => 'payment/publicsquare_payments/publicsquare_webhook_signing_secret',
      ]);
    }

    if (!$I->tryToSeeInDatabase('core_config_data', ['path' => 'payment/publicsquare_payments/publicsquare_webhook_signing_secret_pk'])) {
      $I->haveInDatabase('core_config_data', [
        'path' => 'payment/publicsquare_payments/publicsquare_webhook_signing_secret_pk',
        'value' => $privateKey,
        'scope' => 'default',
        'scope_id' => 0
      ]);
    } else {
      $I->updateInDatabase('core_config_data', [
        'value' => $privateKey,
      ], [
        'path' => 'payment/publicsquare_payments/publicsquare_webhook_signing_secret_pk',
      ]);
    }

    $I->runShellCommand('bin/magento cache:clean config');

    return $privateKey;
  }

  protected function createResponseBodySignature(AcceptanceTester $I, mixed $requestBody): string
  {
    $privateKey = $I->grabFromDatabase('core_config_data', 'value', ['path' => 'payment/publicsquare_payments/publicsquare_webhook_signing_secret_pk']);
    openssl_sign(json_encode($requestBody), $signature, $privateKey, OPENSSL_ALGO_SHA256);
    return base64_encode($signature);
  }

  protected function getRecentSalesOrderPayment(AcceptanceTester $I, string $status = 'processing'): array
  {
    $orders = $I->grabEntriesFromDatabase('sales_order', ['status' => $status]);
    if (count($orders) === 0) {
      throw new \Exception("No order found");
    }
    $order = $orders[count($orders) - 1];
    $paymentId = $I->grabFromDatabase('sales_order_payment', 'cc_trans_id', ['entity_id' => $order['entity_id']]);
    $additionalInformation = json_decode($I->grabFromDatabase('sales_order_payment', 'additional_information', ['cc_trans_id' => $paymentId]), true);
    return [
      'orderId' => $order['increment_id'],
      'paymentId' => $paymentId,
      'amount' => $additionalInformation['raw_details_info']['amount']
    ];
  }

  protected function getRecentOrderCreditMemo(AcceptanceTester $I, string $status = 'processing'): array
  {
    $creditMemos = $I->grabEntriesFromDatabase('sales_creditmemo', ['transaction_id !=' => null]);
    if (count($creditMemos) === 0) {
      throw new \Exception("No credit memo found");
    }
    $creditMemo = $creditMemos[count($creditMemos) - 1];
    $paymentId = $I->grabFromDatabase('sales_order_payment', 'cc_trans_id', ['entity_id' => $creditMemo['order_id']]);
    return [
      'orderId' => $creditMemo['order_id'],
      'refundId' => $creditMemo['transaction_id'],
      'paymentId' => $paymentId
    ];
  }

  protected function getRecentSalesOrderPaymentThatsBeenRefunded(AcceptanceTester $I): array
  {
    $creditMemo = $this->getRecentOrderCreditMemo($I);
    $paymentId = $creditMemo['paymentId'];
    $orderId = $creditMemo['orderId'];
    $additionalInformation = json_decode($I->grabFromDatabase('sales_order_payment', 'additional_information', ['cc_trans_id' => $paymentId]), true);
    return [
      'paymentId' => $paymentId,
      'orderId' => $orderId,
      'amount' => $additionalInformation['raw_details_info']['amount']
    ];
  }
}
