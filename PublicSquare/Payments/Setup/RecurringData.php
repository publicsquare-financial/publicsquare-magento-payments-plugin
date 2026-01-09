<?php

namespace PublicSquare\Payments\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use PublicSquare\Payments\Logger\Logger;
use PublicSquare\Payments\Services\WebhookAutoConfig;

class RecurringData implements InstallDataInterface
{
    private Logger $logger;
    private WebhookAutoConfig $webhookAutoConfig;

    public function __construct(
        Logger|null       $logger,
        WebhookAutoConfig $webhookAutoConfig,

    )
    {
        $this->logger = $logger ?? new Logger('PSQ:RecurringData');
        $this->webhookAutoConfig = $webhookAutoConfig;
    }


    /**
     * @throws \Exception
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context): void
    {
        try {
            $this->logger->info('Running on module version: ' . $context->getVersion());
            $version = $context->getVersion();
            $setup->startSetup();
            if($version) {
                $this->webhookAutoConfig->ensureWebhookInstalled(null);
            } else {
                // If magento is installing with the plugin we need to skip webhook configuration until after
                // the plugin is configured with an api key etc...
                //
                // Either running 'bin/magento setup:install' a 2nd time or running 'bin/magento psq configure-webhooks'
                // after the initial install will work.
                //
                // Alternatively the webhook id and/or key can be set within the admin ui for the plugin.
                $this->logger->warning('Skipping webhook configuration during initial install. Run bin/magento psq configure-webhooks after installation.');
            }
            $this->logger->info('Install successful');

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        } finally {
            $setup->endSetup();
        }
    }

}
