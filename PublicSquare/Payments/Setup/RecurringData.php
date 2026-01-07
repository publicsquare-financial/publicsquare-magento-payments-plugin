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
            $this->logger->info('PublicSquare: Recurring install script running on ' . $context->getVersion());
            $setup->startSetup();
            $this->webhookAutoConfig->ensureWebhookInstalled();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        } finally {
            $setup->endSetup();
        }
    }

}
