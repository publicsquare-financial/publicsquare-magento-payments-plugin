<?php

namespace PublicSquare\Payments\Test\Unit\Services;

use Magento\AdminNotification\Model\ResourceModel\Inbox\Collection;
use Magento\AdminNotification\Model\ResourceModel\Inbox\CollectionFactory;
use Magento\Framework\Notification\NotifierInterface;
use phpseclib3\Crypt\RSA;
use PHPUnit\Framework\TestCase;
use PublicSquare\Payments\Exception\NotConfiguredException;
use PublicSquare\Payments\Helper\Config;
use PublicSquare\Payments\Logger\Logger;
use PublicSquare\Payments\Services\WebhookSignatureService;
use PublicSquare\Payments\Test\Unit\TestConstants;

class WebhookSignatureServiceTest extends TestCase
{
    private Config $config;

    private Logger $logger;
    private NotifierInterface $notifier;
    private CollectionFactory $collectionFactory;
    private WebhookSignatureService $webhookSignatureService;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->logger = $this->createMock(Logger::class);
        $this->logger->method('withName')->willReturn($this->logger);
        $this->notifier = $this->createMock(NotifierInterface::class);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->webhookSignatureService = new WebhookSignatureService(
            $this->config,
            $this->logger,
            $this->notifier,
            $this->collectionFactory,
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

    public function testVerifyAddsNotificationWhenWebhookKeyMissingAndNoExistingNotification()
    {
        $this->config->method('getWebhookKey')->willReturn('');
        $this->logger->expects($this->once())->method('error')->with('Webhook key not configured');

        $collection = $this->createMock(Collection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('addRemoveFilter')->willReturnSelf();
        $collection->method('getSize')->willReturn(0);
        $this->collectionFactory->method('create')->willReturn($collection);

        $this->notifier->expects($this->once())->method('addNotice')
            ->with('PublicSquare Webhook Key Missing', 'The webhook key for PublicSquare payments is not configured. Please set it in Stores > Configuration > Sales > Payment Methods > PublicSquare.');

        $this->expectException(NotConfiguredException::class);
        $this->expectExceptionMessage('Configuration key [payment/publicsquare_payments/webhook_key] "Webhook Key" is not set!');
        $this->webhookSignatureService->verify('signature', 'body');

    }

    public function testVerifyDoesNotAddNotificationWhenWebhookKeyMissingButNotificationExists()
    {
        $this->config->method('getWebhookKey')->willReturn('');
        $this->logger->expects($this->once())->method('error')->with('Webhook key not configured');

        $collection = $this->createMock(Collection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('addRemoveFilter')->willReturnSelf();
        $collection->method('getSize')->willReturn(1);
        $this->collectionFactory->method('create')->willReturn($collection);

        $this->notifier->expects($this->never())->method('addNotice');
        $this->expectException(NotConfiguredException::class);
        $this->expectExceptionMessage('Configuration key [payment/publicsquare_payments/webhook_key] "Webhook Key" is not set!');

        $result = $this->webhookSignatureService->verify('signature', 'body');
        self::assertFalse($result);
    }
}
