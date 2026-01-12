<?php

namespace Magento\Services;

use Magento\Framework\Encryption\Encryptor;
use phpseclib3\Crypt\RSA;
use PublicSquare\Payments\Helper\Config;
use PublicSquare\Payments\Logger\Logger;
use PublicSquare\Payments\Services\WebhookSignatureService;
use PHPUnit\Framework\TestCase;

class WebhookSignatureServiceTest extends TestCase
{
    private Encryptor $encryptor;
    private Config $config;

    private Logger $logger;
    private WebhookSignatureService $webhookSignatureService;

    protected function setUp(): void
    {
        $this->encryptor = $this->createMock(Encryptor::class);
        $this->config = $this->createMock(Config::class);
        $this->logger = $this->createMock(Logger::class);
        $this->logger->method('withName')->willReturn($this->logger);
        $this->webhookSignatureService = new WebhookSignatureService(
            logger: $this->logger,
            encryptor: $this->encryptor,
            config: $this->config,
        );
    }

    public function testValidateSignature() {
        $pk = RSA::createKey(2048);
        $pub = $pk->getPublicKey();
        $pubKeyStr = (string)$pub;
        $b64pub = base64_encode($pubKeyStr);
        $fakeEncryptedKey = 'fakeEncryptedKey';
        $this->config->method('getWebhookKey')->willReturn($fakeEncryptedKey);
        $this->encryptor->expects($this->once())->method('decrypt')->willReturn($b64pub);

        $body = json_encode([
            'id' => 'event_1',
            'event_type' => 'test'
        ], JSON_THROW_ON_ERROR);
        $signature = $pk->sign($body);


        self::assertTrue($this->webhookSignatureService->verify(body: $body, signature: $signature));
    }

    public function testInValidateSignature() {
        $pk = RSA::createKey(2048);
        $pub = $pk->getPublicKey();
        $pubKeyStr = (string)$pub;
        $b64pub = base64_encode($pubKeyStr);
        $fakeEncryptedKey = 'fakeEncryptedKey';


        $this->config->method('getWebhookKey')->willReturn($fakeEncryptedKey);
        $this->encryptor->expects($this->once())->method('decrypt')->willReturn($b64pub);


        $body = json_encode([
            'id' => 'event_1',
            'event_type' => 'test'
        ], JSON_THROW_ON_ERROR);
        $pk2 = RSA::createKey(2048);
        $signature = $pk2->sign($body);


        self::assertFalse($this->webhookSignatureService->verify(body: $body, signature: $signature));
    }
}
