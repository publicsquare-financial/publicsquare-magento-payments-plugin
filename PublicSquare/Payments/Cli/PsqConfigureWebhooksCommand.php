<?php

namespace PublicSquare\Payments\Cli;

use PublicSquare\Payments\Logger\Logger;
use PublicSquare\Payments\Services\WebhookAutoConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PsqConfigureWebhooksCommand extends Command
{
    private WebhookAutoConfig $webhookAutoConfig;

    public function __construct(
        WebhookAutoConfig $webhookAutoConfig,

    )
    {
        parent::__construct();
        $this->webhookAutoConfig = $webhookAutoConfig;
    }

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
        $output->writeln('PublicSquare: Ensure webhooks configured...');
        $this->webhookAutoConfig->ensureWebhookInstalled($output);
        $output->writeln('PublicSquare: Webhooks configured');
        return Command::SUCCESS;
    }

}