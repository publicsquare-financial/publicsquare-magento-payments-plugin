<?php

namespace PublicSquare\Payments\Services;

use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\UrlInterface;
use PublicSquare\Payments\Api\Authenticated\WebhookClient;
use PublicSquare\Payments\Api\Constants;
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
        if ($existingWebhookId) {
            $this->logger->info('Found webhook id [' . $existingWebhookId . '] in config. Attempting to lookup key.');
            // fetch existing webhook key
            $webhook = $this->client->getWebhook($existingWebhookId);
            $webhookId = $existingWebhookId;
            $webhookKey = $webhook['key'] ?? null;
        } else {
            $webhookUrl = rtrim($this->urlBuilder->getUrl('publicsquare-payments/webhook/index'), '/');
            // prevent creating duplicate webhooks in PSQ account
            $existing = $this->findExistingForUrl($webhookUrl);
            if ($existing) {
                $webhookId = $existing['id'];
                $webhookKey = $existing['key'];
            } else {
                $this->logger->info('Creating new webhook url ' . $webhookUrl);

                $webhook = $this->client->createWebhook($webhookUrl);
                $this->logger->info('Created webhook.', ['webhookId' => $webhook['id'], 'webhookUrl' => $webhookUrl]);

                $webhookId = $webhook['id'] ?? null;
                $webhookKey = $webhook['key'] ?? null;
            }


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

    private function findExistingForUrl(string $webhookUrl, int $page = 1): array|null
    {
        try {
            $response = $this->client->search($page, 100);
            $pagination = $response['pagination'];
            $items = $response['data'];
            foreach ($items as $item) {
                // Forcing the 2 events to match since in this flow the Magento config got
                // disconnected, has no webhook id, and it's safer to create a new webhook
                // than modify an existing one. We might want to change that behavior at
                // some later date to further prevent duplicate webhooks.
                $containsRefundEvent = in_array(Constants::WEBHOOK_EVENT_REFUND_UPDATED, $item['event_types'], true);
                $containsStlmntEvent = in_array(Constants::WEBHOOK_EVENT_SETTLEMENT_UPDATED, $item['event_types'], true);
                $urlsMatch = $webhookUrl === ($item['url'] || '');
                if ($urlsMatch && $containsStlmntEvent && $containsRefundEvent) {
                    $this->logger->info('Found existing webhook for url:[' . $webhookUrl . '] with id:[' . $item['id'] . '].');
                    return $item;
                }
            }
            if ($page < 10 && $page < $pagination['total_pages']) {
                return $this->findExistingForUrl($webhookUrl, $page + 1);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error searching webhooks', ['exception' => $e]);
            return null;
        }
        return null;
    }
}