<?php

namespace PublicSquare\Payments\Services;

use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PublicSquare\Payments\Api\Authenticated\PSQCurlClient;
use PublicSquare\Payments\Helper\Config;
use PublicSquare\Payments\Logger\Logger;

class WebhookAutoConfig
{
    private Logger $logger;
    private ConfigInterface $resourceConfig;
    private EncryptorInterface $encryptor;
    private ScopeConfigInterface $scopeConfig;
    private StoreManagerInterface $storeManager;

    public function __construct(
        Logger|null           $logger,
        ConfigInterface       $resourceConfig,
        ScopeConfigInterface  $scopeConfig,
        EncryptorInterface    $encryptor,
        StoreManagerInterface $storeManager,
    )
    {
        $this->logger = $logger ?? new Logger('PSQ:WebhookAutoConfig');
        $this->resourceConfig = $resourceConfig;
        $this->encryptor = $encryptor;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;

    }
    /**
     * @throws \Exception
     */
    public function ensureWebhookInstalled(): void
    {
        $webhookKey = $this->scopeConfig->getValue(Config::PUBLICSQUARE_WEBHOOK_KEY);
        $webhookId = $this->scopeConfig->getValue(Config::PUBLICSQUARE_WEBHOOK_ID);
        if (!$webhookKey || !$webhookId) {
            $this->logger->info('PublicSquare: Installing webhooks...');
            $this->setupWebhooks($webhookId);
        }
        // Successfully installed OR already installed...
        $this->logger->info('PublicSquare: Webhooks installed.');
    }
    /**
     * @throws \Exception
     */
    public function setupWebhooks(string|null $existingWebhookId): void
    {
        $client = new PSQCurlClient();

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
            $baseUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
            $webhookUrl = $baseUrl . 'publicsquare-payments/webhook';

            $webhook = $client->createWebhook($privateKey, $webhookUrl);
            $this->logger->info('PublicSquare: Created webhook.', compact('webhook'));

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
    }
}