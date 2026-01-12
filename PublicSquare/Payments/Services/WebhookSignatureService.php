<?php

namespace PublicSquare\Payments\Services;

use Magento\Framework\Encryption\Encryptor;
use PublicSquare\Payments\Helper\Config;
use Monolog\Logger;

class WebhookSignatureService
{

    private Encryptor $encryptor;
    private Config $config;

    private Logger $logger;

    public function __construct(
        Encryptor $encryptor,
        Config $config,
        Logger $logger,
    ) {
        $this->encryptor = $encryptor;
        $this->config = $config;
        $this->logger = $logger->withName('PSQ:WebhookSignatureService');
    }

    public function verify(string $signature, string $body): bool {
        $encryptedWebhookKey = $this->config->getWebhookKey();
        if (!$encryptedWebhookKey) {
            $this->logger->error('Webhook secret not configured');
            return false;
        }
        $webhookKey = $this->encryptor->decrypt($encryptedWebhookKey);

        $decodedSignature = base64_decode($signature);
        $decodedKey = base64_decode($webhookKey);

        $publicKeyPem = "-----BEGIN PUBLIC KEY-----\n" .
            chunk_split(base64_encode($decodedKey), 64, "\n") .
            "-----END PUBLIC KEY-----\n";

        try {
            $rsa = PublicKeyLoader::load($publicKeyPem);
            $rsa = $rsa->withPadding(RSA::SIGNATURE_PKCS1)->withHash('sha256');
            $verified = $rsa->verify($body, $decodedSignature);
        } catch (\Exception $e) {
            $this->logger->error('PSQ Webhook: Invalid public key or verification error', ['exception' => $e->getMessage()]);
            return false;
        }

        return $verified;
    }

}