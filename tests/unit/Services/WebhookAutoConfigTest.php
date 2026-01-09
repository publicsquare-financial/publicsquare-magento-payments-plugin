<?php

namespace PublicSquare\Payments\Test\Unit\Services;

use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;
use PublicSquare\Payments\Api\Authenticated\WebhookClient;
use PublicSquare\Payments\Api\Constants;
use PublicSquare\Payments\Helper\Config;
use PublicSquare\Payments\Logger\Logger;
use PublicSquare\Payments\Services\WebhookAutoConfig;
use Symfony\Component\Console\Output\OutputInterface;

class WebhookAutoConfigTest extends TestCase
{
    private Logger $logger;
    private ConfigInterface $resourceConfig;
    private EncryptorInterface $encryptor;
    private ScopeConfigInterface $scopeConfig;
    private UrlInterface $urlBuilder;
    private WebhookClient $webhookClient;
    private WebhookAutoConfig $webhookAutoConfig;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->resourceConfig = $this->getMockBuilder(ConfigInterface::class)->getMock();
        $this->encryptor = $this->createMock(EncryptorInterface::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->urlBuilder = $this->createMock(UrlInterface::class);
        $this->webhookClient = $this->createMock(WebhookClient::class);

        $this->webhookAutoConfig = new WebhookAutoConfig(
            $this->logger,
            $this->resourceConfig,
            $this->scopeConfig,
            $this->encryptor,
            $this->urlBuilder,
            $this->webhookClient,
        );
    }

    public function testEnsureWebhookInstalledWhenAlreadyConfigured()
    {
        $output = $this->createMock(OutputInterface::class);

        $this->scopeConfig->method('getValue')
            ->willReturnMap([
                [Config::PUBLICSQUARE_WEBHOOK_KEY, null, null, 'encrypted_key'],
                [Config::PUBLICSQUARE_WEBHOOK_ID, null, null, 'webhook_id'],
            ]);

        $this->logger->expects($this->once())->method('debug')->with('Checking if webhook is configured.');
        $this->logger->expects($this->once())->method('info')->with('Webhook with id [webhook_id] already installed.');

        $output->expects($this->exactly(2))->method('writeln');

        $this->webhookAutoConfig->ensureWebhookInstalled($output);
        $this->assertTrue(true); // Add assertion to avoid risky
    }

    public function testEnsureWebhookInstalledWhenNotConfigured()
    {
        $webhookUrl = 'http://abc.test';
        $output = $this->createMock(OutputInterface::class);

        $this->scopeConfig->method('getValue')
            ->willReturnMap([
                [Config::PUBLICSQUARE_WEBHOOK_KEY, null, null, null],
                [Config::PUBLICSQUARE_WEBHOOK_ID, null, null, null],
            ]);

        /*$this->logger->expects($this->once())->method('debug')->with('Checking if webhook is configured.');
        $this->logger->expects($this->once())->method('info')->with('Installing webhooks...');*/
        $this->logger->expects($this->any())->method('info')->with($this->anything());

        $output->expects($this->exactly(2))->method('writeln');
        $this->webhookClient->expects($this->exactly(2))->method('search')->willReturn([
            'pagination' => [
                'total_pages' => 2,
            ],
            'data' => [
                [
                    'url' => '',
                    'event_types' => ['x'],
                ],
                [
                    'url' => '',
                    'event_types' => ['y'],
                ],
            ],
        ]);
        $this->webhookClient->expects($this->once())->method('createWebhook')->willReturn([
            'id' => 'whk_1',
            'url' => $webhookUrl,
            'event_types' => [Constants::WEBHOOK_EVENT_SETTLEMENT_UPDATED, Constants::WEBHOOK_EVENT_REFUND_UPDATED],
            'key' => 'base64',
        ]);
        $this->encryptor->expects($this->once())->method('encrypt')->with('base64')->willReturn('--encrypted--base64');

        $this->webhookAutoConfig->ensureWebhookInstalled($output);
        $this->assertTrue(true);
    }


    public function testSetupWebhooksWithNoPrivateKey()
    {
        $this->scopeConfig->method('getValue')
            ->with(Config::PUBLICSQUARE_API_SECRET_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null)
            ->willReturn(null);


        $this->expectException(\RuntimeException::class);
        $this->webhookAutoConfig->setupWebhooks(null);
    }
}