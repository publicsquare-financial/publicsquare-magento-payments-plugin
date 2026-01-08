<?php

namespace PublicSquare\Payments\Services;

use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PublicSquare\Payments\Api\Authenticated\PSQCurlClient;
use PublicSquare\Payments\Helper\Config;
use PublicSquare\Payments\Logger\Logger;
use Symfony\Component\Console\Output\OutputInterface;

class WebhookAutoConfig
{
    private Logger $logger;
    private ConfigInterface $resourceConfig;
    private EncryptorInterface $encryptor;
    private ScopeConfigInterface $scopeConfig;
    private StoreManagerInterface $storeManager;
    private UrlInterface $urlBuilder;

    public function __construct(
        Logger|null           $logger,
        ConfigInterface       $resourceConfig,
        ScopeConfigInterface  $scopeConfig,
        EncryptorInterface    $encryptor,
        StoreManagerInterface $storeManager,
        UrlInterface          $urlBuilder,
    )
    {
        $this->logger = $logger ?? new Logger('PSQ:WebhookAutoConfig');
        $this->resourceConfig = $resourceConfig;
        $this->encryptor = $encryptor;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->urlBuilder = $urlBuilder;

    }

    /**
     * @throws \Exception
     */
    public function ensureWebhookInstalled(OutputInterface|null $output = null): void
    {
        $this->logger->debug('Checking if webhook is configured.');
        $output?->writeln('Checking if webhook is configured.');
        $webhookKey = $this->scopeConfig->getValue(Config::PUBLICSQUARE_WEBHOOK_KEY);
        $webhookId = $this->scopeConfig->getValue(Config::PUBLICSQUARE_WEBHOOK_ID);
        if (!$webhookKey || !$webhookId) {
            $this->logger->info('Installing webhooks...');
            $output?->writeln('Installing webhooks...');
            $this->setupWebhooks($webhookId);
        } else {
            $this->logger->info('Webhook with id [' . $webhookId . '] already installed.');
            $output?->writeln('Webhook with id [' . $webhookId . '] already installed.');
        }

    }

    /**
     * @throws \Exception
     */
    public function setupWebhooks(string|null $existingWebhookId): void
    {
        $client = new PSQCurlClient($this->logger);

        $privateKey = $this->scopeConfig->getValue(
            Config::PUBLICSQUARE_API_SECRET_KEY,
            ScopeInterface::SCOPE_STORE,
            null,
        );
        if (!$privateKey) {
            $this->logger->warning('PublicSquare: Private Key not set. Will not be able to connect webhooks until the private key is configured.');
            return;
        }

        if ($existingWebhookId) {
            $this->logger->info('PublicSquare: Found webhook id' . $existingWebhookId . ' in config. Attempting to lookup key.');
            // fetch existing webhook key
            $webhook = $client->getWebhook($privateKey, $existingWebhookId);
            $webhookId = $existingWebhookId;
            $webhookKey = $webhook['key'];
        } else {
            $webhookUrl = $this->urlBuilder->getUrl('publicSquare-payments/webhook/index');
            $this->logger->info('PublicSquare: Creating new webhook url ' . $webhookUrl);

            $webhook = $client->createWebhook($privateKey, $webhookUrl);
            $this->logger->info('PublicSquare: Created webhook.', ['webhookId' => $webhook['id'], 'webhookUrl' => $webhookUrl]);

            $webhookId = $webhook['id'];
            $webhookKey = $webhook['key'];
        }

        // validate and save
        if (!$webhookId) {
            $this->logger->warning('PublicSquare: Missing webhook id.');
            throw new \RuntimeException('PublicSquare: Missing webhook id.');
        }
        if (!$webhookKey) {
            $this->logger->warning('PublicSquare: Missing webhook key.');
            throw new \RuntimeException('PublicSquare: Missing webhook key.');
        }

        $this->resourceConfig->saveConfig(
            Config::PUBLICSQUARE_WEBHOOK_KEY,
            $this->encryptor->encrypt($webhookKey),
        );
        $this->resourceConfig->saveConfig(
            Config::PUBLICSQUARE_WEBHOOK_ID,
            $webhookId,
        );
        $this->logger->info('PublicSquare: Webhook configuration updated successfully.');
    }
}