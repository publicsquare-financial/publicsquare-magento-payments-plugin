<?php

namespace PublicSquare\Payments\Services;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use PublicSquare\Payments\Helper\Config;
use PublicSquare\Payments\Logger\Logger;

class WebhookSignatureService
{

    private Config $config;

    private Logger $logger;

    public function __construct(
        Config $config,
        Logger $logger,
    ) {
        $this->config = $config;
        $this->logger = $logger->withName('PSQ:WebhookSignatureService');
    }

    public function verify(string $signature, string $body): bool {
        $webhookKey = str_replace(['\n', ' '], '', $this->config->getWebhookKey());
        if (!$webhookKey) {
            $this->logger->error('Webhook secret not configured');
            return false;
        }


        try {
            if(str_contains('BEGIN PUBLIC KEY', $webhookKey)) {
                $publicKeyPem = $webhookKey;
            } else {
                $publicKeyPem = "-----BEGIN PUBLIC KEY-----\r\n" .
                    chunk_split($webhookKey, 64, "\r\n") .
                    "-----END PUBLIC KEY-----\r\n";
            }


            $rsa = PublicKeyLoader::load($publicKeyPem);
            $rsa = $rsa->withPadding(RSA::SIGNATURE_PKCS1)->withHash('sha256');
            $verified = $rsa->verify($body, base64_decode($signature));
        } catch (\Exception $e) {
            $this->logger->error('PSQ Webhook: Invalid public key or verification error', ['exception' => $e->getMessage()]);
            return false;
        }

        return $verified;
    }

}