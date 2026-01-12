<?php

namespace PublicSquare\Payments\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use PublicSquare\Payments\Logger\Logger;
use PublicSquare\Payments\Services\WebhookAutoConfig;

class WebhookConfigPatch implements DataPatchInterface
{
    private Logger $logger;
    private WebhookAutoConfig $webhookAutoConfig;

    public function __construct(
        Logger|null       $logger,
        WebhookAutoConfig $webhookAutoConfig,

    )
    {
        $this->logger = $logger ?? new Logger('PSQ:' . $this::class);
        $this->webhookAutoConfig = $webhookAutoConfig;
    }


    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        try {
            $this->logger->info('Data patch running');
            $this->webhookAutoConfig->ensureWebhookInstalled(null);
            $this->logger->info('Install successful');
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());
            $this->logger->info('Please run "bin/magento psq:configure-webhooks" after configuring the PublicSquare payments plugin with an api key.');
        }
    }
}
