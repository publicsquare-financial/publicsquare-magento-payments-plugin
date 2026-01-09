<?php

namespace PublicSquare\Payments\Test\Unit\Controller\Webhook;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Encryption\Encryptor;
use PHPUnit\Framework\TestCase;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use PublicSquare\Payments\Controller\Webhook\Index;
use PublicSquare\Payments\Helper\Config;
use PublicSquare\Payments\Logger\Logger;
use PublicSquare\Payments\Services\Events\RefundEventHandler;
use PublicSquare\Payments\Services\Events\SettlementUpdateEventHandler;

class IndexTest extends TestCase
{
    private Config $config;
    private Logger $logger;
    private RequestInterface $request;
    private JsonFactory $jsonFactory;
    private Encryptor $encryptor;
    private SettlementUpdateEventHandler $settlementUpdateEventHandler;
    private RefundEventHandler $refundEventHandler;
    private Index $controller;
    private $privateKey;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->logger = $this->createMock(Logger::class);
        $this->logger->method('withName')->willReturn($this->logger);
        $this->request = $this->createMock(RequestInterface::class);
        $this->jsonFactory = $this->createMock(JsonFactory::class);
        $this->encryptor = $this->createMock(\Magento\Framework\Encryption\Encryptor::class);
        $this->settlementUpdateEventHandler = $this->createMock(SettlementUpdateEventHandler::class);
        $this->refundEventHandler = $this->createMock(RefundEventHandler::class);

        $this->controller = new Index(
            $this->config,
            $this->logger,
            $this->request,
            $this->jsonFactory,
            $this->encryptor,
            $this->settlementUpdateEventHandler,
            $this->refundEventHandler
        );

        // Generate a private key for signing
        $this->privateKey = RSA::createKey(2048);
        $publicKey = $this->privateKey->getPublicKey();
        $publicKeyPem = $publicKey->toString('PKCS8');
        $this->webhookKey = base64_encode($publicKeyPem);
    }

    private function generateSignature(string $body): string
    {
        $rsa = $this->privateKey->withPadding(RSA::SIGNATURE_PKCS1)->withHash('sha256');
        $signature = $rsa->sign($body);
        return base64_encode($signature);
    }

    public function testExecuteSettlementUpdate()
    {
        $body = json_encode([
            'id' => 'event_123',
            'event_type' => 'settlement:update',
            'entity' => ['id' => 'stl_1']
        ]);
        $signature = $this->generateSignature($body);

        $this->request->method('getContent')->willReturn($body);
        $this->request->method('getHeader')->with('X-Signature')->willReturn($signature);

        $this->config->method('getWebhookKey')->willReturn('encrypted_key');
        $this->encryptor->method('decrypt')->with('encrypted_key')->willReturn($this->webhookKey);

        $result = $this->createMock(Json::class);
        $this->jsonFactory->method('create')->willReturn($result);
        $result->expects($this->once())->method('setStatusHeader')->with(200);
        $result->expects($this->once())->method('setData')->with(['success' => true]);

        $this->settlementUpdateEventHandler->expects($this->once())->method('handleEvent')
            ->with(json_decode($body, true));

        $response = $this->controller->execute();
        $this->assertSame($result, $response);
    }

    public function testExecuteRefundUpdate()
    {
        $body = json_encode([
            'id' => 'event_456',
            'event_type' => 'refund:update',
            'entity' => ['id' => 'rfd_1']
        ]);
        $signature = $this->generateSignature($body);

        $this->request->method('getContent')->willReturn($body);
        $this->request->method('getHeader')->with('X-Signature')->willReturn($signature);

        $this->config->method('getWebhookKey')->willReturn('encrypted_key');
        $this->encryptor->method('decrypt')->with('encrypted_key')->willReturn($this->webhookKey);

        $result = $this->createMock(Json::class);
        $this->jsonFactory->method('create')->willReturn($result);
        $result->expects($this->once())->method('setStatusHeader')->with(200);
        $result->expects($this->once())->method('setData')->with(['success' => true]);

        $this->refundEventHandler->expects($this->once())->method('handleEvent')
            ->with(json_decode($body, true));

        $response = $this->controller->execute();
        $this->assertSame($result, $response);
    }

    public function testExecuteInvalidSignature()
    {
        $body = 'invalid';
        $signature = 'invalid_signature';

        $this->request->method('getContent')->willReturn($body);
        $this->request->method('getHeader')->with('X-Signature')->willReturn($signature);

        $this->config->method('getWebhookKey')->willReturn('');

        $result = $this->createMock(Json::class);
        $this->jsonFactory->method('create')->willReturn($result);
        $result->expects($this->once())->method('setStatusHeader')->with(400);
        $result->expects($this->once())->method('setData')->with(['error' => 'Invalid signature']);

        $response = $this->controller->execute();
        $this->assertSame($result, $response);
    }

    public function testExecuteInvalidJson()
    {
        $body = 'invalid json';
        $signature = $this->generateSignature($body);

        $this->request->method('getContent')->willReturn($body);
        $this->request->method('getHeader')->with('X-Signature')->willReturn($signature);

        $this->config->method('getWebhookKey')->willReturn('encrypted_key');
        $this->encryptor->method('decrypt')->with('encrypted_key')->willReturn($this->webhookKey);

        $result = $this->createMock(Json::class);
        $this->jsonFactory->method('create')->willReturn($result);
        $result->expects($this->once())->method('setStatusHeader')->with(400);
        $result->expects($this->once())->method('setData')->with(['error' => 'Invalid JSON']);

        $response = $this->controller->execute();
        $this->assertSame($result, $response);
    }

    public function testExecuteUnhandledEventType()
    {
        $body = json_encode([
            'id' => 'event_789',
            'event_type' => 'unknown:event',
            'entity' => ['id' => 'ent_1']
        ]);
        $signature = $this->generateSignature($body);

        $this->request->method('getContent')->willReturn($body);
        $this->request->method('getHeader')->with('X-Signature')->willReturn($signature);

        $this->config->method('getWebhookKey')->willReturn('encrypted_key');
        $this->encryptor->method('decrypt')->with('encrypted_key')->willReturn($this->webhookKey);

        $result = $this->createMock(Json::class);
        $this->jsonFactory->method('create')->willReturn($result);
        $result->expects($this->once())->method('setStatusHeader')->with(200);
        $result->expects($this->once())->method('setData')->with(['success' => true]);

        $this->logger->expects($this->once())->method('info')
            ->with('PSQ Webhook: Unhandled event type', ['event_type' => 'unknown:event']);

        $response = $this->controller->execute();
        $this->assertSame($result, $response);
    }

    public function testExecuteExceptionHandling()
    {
        $body = json_encode([
            'id' => 'event_999',
            'event_type' => 'settlement:update',
            'entity' => ['id' => 'stl_1']
        ]);
        $signature = $this->generateSignature($body);

        $this->request->method('getContent')->willReturn($body);
        $this->request->method('getHeader')->with('X-Signature')->willReturn($signature);

        $this->config->method('getWebhookKey')->willReturn('encrypted_key');
        $this->encryptor->method('decrypt')->with('encrypted_key')->willReturn($this->webhookKey);

        $this->settlementUpdateEventHandler->method('handleEvent')
            ->willThrowException(new \Exception('Handler error'));

        $result = $this->createMock(Json::class);
        $this->jsonFactory->method('create')->willReturn($result);
        $result->expects($this->once())->method('setStatusHeader')->with(500);
        $result->expects($this->once())->method('setData')->with(['error' => 'Handler error']);

        $this->logger->expects($this->once())->method('error')
            ->with('Handler error', ['exception' => new \Exception('Handler error')]);

        $response = $this->controller->execute();
        $this->assertSame($result, $response);
    }
}