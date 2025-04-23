<?php

namespace Tests\Bases;

use Tests\Support\AcceptanceTester;

class WebhookBase extends AcceptanceBase
{
  public function setWebhookSigningSecret(AcceptanceTester $I)
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
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
-----END RSA PRIVATE KEY-----
EOD;

    $publicKey = "AAAAB3NzaC1yc2EAAAADAQABAAABgQDAlFe0lKirF8rLHFc/ywckTbUe7U1iiX66aSUQpGxpg3RFDj48afvZ1Otblr79ZYVwrDNvZBPr+k5j+FjAvUvgf/m6vEyoX0gD8srGqFDImpIzk20myd+fbGZ72LHInPKOUwxpL4uN4Tfa7mMv0XyuN/DeICkVsa3Ca9XYcojnTOWSyJ/RbvUg/Sh3NSBEQtw4jB1JrIUCOUoxti65Yt6TZcmiF42IufXTgzAJuP496YlFCHcByM5eyvBNBr6jfrXf6wwAzZXC/frCyCMFOjms/DABPK4WPuTWjcDw2j/ytClmkqdqLZI/NKdy/3vzGLT7iH6N/kIo78Ck/ii6EVPhsJFWvRkQ1V+9HLNTKtq9rANXmCjgk53RGc3XHM5DFthxQd+JxPJ08m0ajKyJugCpkm9+WG5/PJ8V7kfcrUbBDGImpJk/puXn2Dp00eWE0qOs+oE8wfQItZT9tc25wuZp4MBlkh6v7mLQ6oCI1seNArNuBKWdFBjAeI577HA8pLM=";

    // Store both keys in the database
    if (!$I->tryToSeeInDatabase('core_config_data', ['path' => 'payment/publicsquare_payments/webhook_signing_secret'])) {
      $I->haveInDatabase('core_config_data', [
        'path' => 'payment/publicsquare_payments/webhook_signing_secret',
        'value' => base64_encode($publicKey),
        'scope' => 'default',
        'scope_id' => 0
      ]);
    } else {
      $I->updateInDatabase('core_config_data', [
        'value' => base64_encode($publicKey),
      ], [
        'path' => 'payment/publicsquare_payments/webhook_signing_secret',
      ]);
    }

    if (!$I->tryToSeeInDatabase('core_config_data', ['path' => 'payment/publicsquare_payments/webhook_signing_secret_pk'])) {
      $I->haveInDatabase('core_config_data', [
        'path' => 'payment/publicsquare_payments/webhook_signing_secret_pk',
        'value' => $privateKey,
        'scope' => 'default',
        'scope_id' => 0
      ]);
    } else {
      $I->updateInDatabase('core_config_data', [
        'value' => $privateKey,
      ], [
        'path' => 'payment/publicsquare_payments/webhook_signing_secret_pk',
      ]);
    }

    return $privateKey;
  }

  public function createResponseBodySignature(AcceptanceTester $I, mixed $requestBody): string
  {
    $privateKey = $I->grabFromDatabase('core_config_data', 'value', ['path' => 'payment/publicsquare_payments/webhook_signing_secret_pk']);
    // $privateKey = base64_decode($privateKey);
    codecept_debug('> Private key: ' . $privateKey);
    $signature = openssl_sign(json_encode($requestBody), $signature, $privateKey, OPENSSL_ALGO_SHA256);
    return base64_encode($signature);
  }

  public function getRecentOrderPaymentId(AcceptanceTester $I, string $status = 'processing'): string
  {
    $orders = $I->grabEntriesFromDatabase('sales_order', ['status' => $status]);
    if (count($orders) === 0) {
      throw new \Exception("No order found");
    }
    $order = $orders[count($orders) - 1];
    $paymentId = $I->grabFromDatabase('sales_order_payment', 'cc_trans_id', ['entity_id' => $order['entity_id']]);
    return $paymentId;
  }
}
