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
    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->logger = $this->createMock(Logger::class);
        $this->logger->method('withName')->willReturn($this->logger);
        $this->request = $this->createMock(RequestInterface::class);
        $this->jsonFactory = $this->createMock(JsonFactory::class);
        $this->encryptor = $this->createMock(Encryptor::class);
        $this->settlementUpdateEventHandler = $this->createMock(SettlementUpdateEventHandler::class);
        $this->refundEventHandler = $this->createMock(RefundEventHandler::class);

        $this->controller = $this->getMockBuilder(Index::class)
            ->setConstructorArgs([
                $this->config,
                $this->logger,
                $this->request,
                $this->jsonFactory,
                $this->encryptor,
                $this->settlementUpdateEventHandler,
                $this->refundEventHandler
            ])
            ->onlyMethods(['verifySignature'])
            ->getMock();
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
        $signature = 'dummy_signature';

        $this->request->method('getContent')->willReturn($body);
        $this->request->method('getHeader')->with('X-Signature')->willReturn($signature);

        $this->controller->method('verifySignature')->willReturn(true);

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
        $signature = 'dummy_signature';

        $this->request->method('getContent')->willReturn($body);
        $this->request->method('getHeader')->with('X-Signature')->willReturn($signature);

        $this->controller->method('verifySignature')->willReturn(true);

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
        $body = json_encode(['event_type' => 'test']);
        $signature = 'dummy_signature';

        $this->request->method('getContent')->willReturn($body);
        $this->request->method('getHeader')->with('X-Signature')->willReturn($signature);

        $this->controller->method('verifySignature')->willReturn(false);

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
        $signature = 'dummy_signature';

        $this->request->method('getContent')->willReturn($body);
        $this->request->method('getHeader')->with('X-Signature')->willReturn($signature);

        $this->config->method('getWebhookKey')->willReturn('encrypted_key');
        $this->encryptor->method('decrypt')->with('encrypted_key')->willReturn($this->webhookKey);

        $result = $this->createMock(Json::class);
        $this->jsonFactory->method('create')->willReturn($result);
        $result->expects($this->once())->method('setStatusHeader');
        $result->expects($this->once())->method('setData');

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
        $signature = 'dummy_signature';

        $this->request->method('getContent')->willReturn($body);
        $this->request->method('getHeader')->with('X-Signature')->willReturn($signature);

        $this->controller->method('verifySignature')->willReturn(true);

        $result = $this->createMock(Json::class);
        $this->jsonFactory->method('create')->willReturn($result);
        $result->expects($this->once())->method('setStatusHeader')->with(200);
        $result->expects($this->once())->method('setData')->with(['success' => true]);



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
        $signature = 'dummy_signature';

        $this->request->method('getContent')->willReturn($body);
        $this->request->method('getHeader')->with('X-Signature')->willReturn($signature);

        $this->config->method('getWebhookKey')->willReturn('encrypted_key');
        $this->encryptor->method('decrypt')->with('encrypted_key')->willReturn($this->webhookKey);

        $result = $this->createMock(Json::class);
        $this->jsonFactory->method('create')->willReturn($result);
        $result->expects($this->once())->method('setStatusHeader');
        $result->expects($this->once())->method('setData');

        $response = $this->controller->execute();
        $this->assertSame($result, $response);
    }
}