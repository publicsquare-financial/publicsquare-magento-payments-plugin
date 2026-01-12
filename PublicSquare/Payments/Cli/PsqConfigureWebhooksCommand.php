<?php

namespace PublicSquare\Payments\Cli;

use Magento\Framework\App\ObjectManager;
use PublicSquare\Payments\Services\WebhookAutoConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PsqConfigureWebhooksCommand extends Command
{
    protected function configure()
    {
        $this->setName('psq:configure-webhooks');
        $this->setDescription('Configure PSQ webhooks');
    }

    /**
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $om = ObjectManager::getInstance();
        $webhookAutoConfig = $om->get(WebhookAutoConfig::class);
        $output->writeln('PublicSquare: Ensure webhooks configured...');
        $webhookAutoConfig->ensureWebhookInstalled($output);
        $output->writeln('PublicSquare: Webhooks configured');
        return Command::SUCCESS;
    }

}