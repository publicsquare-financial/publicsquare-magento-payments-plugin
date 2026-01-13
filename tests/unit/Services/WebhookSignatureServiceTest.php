<?php

namespace PublicSquare\Payments\Test\Unit\Services;

use phpseclib3\Crypt\RSA;
use PHPUnit\Framework\TestCase;
use PublicSquare\Payments\Helper\Config;
use PublicSquare\Payments\Logger\Logger;
use PublicSquare\Payments\Services\WebhookSignatureService;
use PublicSquare\Payments\Test\Unit\TestConstants;

class WebhookSignatureServiceTest extends TestCase
{
    private Config $config;

    private Logger $logger;
    private WebhookSignatureService $webhookSignatureService;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->logger = $this->createMock(Logger::class);
        $this->logger->method('withName')->willReturn($this->logger);
        $this->webhookSignatureService = new WebhookSignatureService(
            logger: $this->logger,
            config: $this->config,
        );
    }

    public function testValidateSignature()
    {
        $pk = RSA::createKey(2048);
        $pub = $pk->getPublicKey();
        $pubKeyStr = (string)$pub;
        $this->config->method('getWebhookKey')->willReturn($pubKeyStr);

        $body = json_encode([
            'id' => 'event_1',
            'event_type' => 'test',
        ], JSON_THROW_ON_ERROR);
        $pk = $pk->withPadding(RSA::SIGNATURE_PKCS1)->withHash('sha256');
        $signature = $pk->sign($body);


        self::assertTrue($this->webhookSignatureService->verify(body: $body,
            signature: base64_encode($signature)));
    }

    public function testInValidateSignature()
    {
        $pk = RSA::createKey(2048);
        $pub = $pk->getPublicKey();
        $pubKeyStr = (string)$pub;
        $b64pub = base64_encode($pubKeyStr);


        $this->config->method('getWebhookKey')->willReturn($b64pub);


        $body = json_encode([
            'id' => 'event_1',
            'event_type' => 'test',
        ], JSON_THROW_ON_ERROR);
        $pk2 = RSA::createKey(2048);
        $pk2 = $pk2->withPadding(RSA::SIGNATURE_PKCS1)->withHash('sha256');

        $signature = $pk2->sign($body);


        self::assertFalse($this->webhookSignatureService->verify(body: $body, signature: base64_encode($signature)));
    }

    public function testValidateSignature_withStaticKey()
    {
        $pk = RSA::loadPrivateKey(TestConstants::RSA_PRIVATE_KEY);
        $this->config->method('getWebhookKey')->willReturn(TestConstants::RSA_PUBLIC_KEY);

        $body = json_encode([
            'id' => 'event_1',
            'event_type' => 'test',
        ], JSON_THROW_ON_ERROR);
        $pk = $pk->withPadding(RSA::SIGNATURE_PKCS1)->withHash('sha256');
        $signature = base64_encode($pk->sign($body));
        echo "Signature: " . $signature . "\n";


        self::assertTrue($this->webhookSignatureService->verify(body: $body, signature: $signature));
    }
}
