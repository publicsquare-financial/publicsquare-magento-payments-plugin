<?php

namespace PublicSquare\Payments\Services;

use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use PublicSquare\Payments\Api\Authenticated\WebhookClient;
use PublicSquare\Payments\Helper\Config;
use PublicSquare\Payments\Logger\Logger;
use Symfony\Component\Console\Output\OutputInterface;

class WebhookAutoConfig
{
    private Logger $logger;
    private ConfigInterface $resourceConfig;
    private EncryptorInterface $encryptor;
    private ScopeConfigInterface $scopeConfig;
    private UrlInterface $urlBuilder;
    private WebhookClient $client;

    public function __construct(
        Logger|null          $logger,
        ConfigInterface      $resourceConfig,
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface   $encryptor,
        UrlInterface         $urlBuilder,
        WebhookClient        $client,
    )
    {
        $this->logger = $logger ?? new Logger('PSQ:WebhookAutoConfig');
        $this->resourceConfig = $resourceConfig;
        $this->encryptor = $encryptor;
        $this->scopeConfig = $scopeConfig;
        $this->urlBuilder = $urlBuilder;
        $this->client = $client;

    }

    /**
     * @throws \Exception
     */
    public function ensureWebhookInstalled(OutputInterface|null $output): void
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

        $privateKey = $this->scopeConfig->getValue(
            Config::PUBLICSQUARE_API_SECRET_KEY,
            ScopeInterface::SCOPE_STORE,
            null,
        );
        if (!$privateKey) {
            $this->logger->warning('Private Key not set. Will not be able to connect webhooks until the private key is configured.');
            return;
        }

        if ($existingWebhookId) {
            $this->logger->info('Found webhook id [' . $existingWebhookId . '] in config. Attempting to lookup key.');
            // fetch existing webhook key
            $webhook = $this->client->getWebhook($existingWebhookId);
            $webhookId = $existingWebhookId;
            $webhookKey = $webhook['key'] ?? null;
        } else {
            $webhookUrl = rtrim($this->urlBuilder->getUrl('publicsquare-payments/webhook/index'), '/');
            $this->logger->info('Creating new webhook url ' . $webhookUrl);

            $webhook = $this->client->createWebhook($webhookUrl);
            $this->logger->info('Created webhook.', ['webhookId' => $webhook['id'], 'webhookUrl' => $webhookUrl]);

            $webhookId = $webhook['id'] ?? null;
            $webhookKey = $webhook['key'] ?? null;
        }

        // validate and save
        if (!$webhookId) {
            $this->logger->warning('Missing webhook id.');
            throw new \RuntimeException('Missing webhook id.');
        }
        if (!$webhookKey) {
            $this->logger->warning('Missing webhook key.');
            throw new \RuntimeException('Missing webhook key.');
        }

        $this->resourceConfig->saveConfig(
            Config::PUBLICSQUARE_WEBHOOK_KEY,
            $this->encryptor->encrypt($webhookKey),
        );
        $this->resourceConfig->saveConfig(
            Config::PUBLICSQUARE_WEBHOOK_ID,
            $webhookId,
        );
        $this->logger->info('Webhook configuration updated successfully.');
    }
}