<?php

namespace PublicSquare\Payments\Cli;

use Composer\Console\Input\InputArgument;
use PublicSquare\Payments\Logger\Logger;
use PublicSquare\Payments\Services\WebhookAutoConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PsqCli extends Command
{
    private Logger $logger;
    private WebhookAutoConfig $webhookAutoConfig;

    public function __construct(
        Logger|null       $logger,
        WebhookAutoConfig $webhookAutoConfig,

    )
    {
        parent::__construct();
        $this->logger = $logger ?? new Logger('PSQ:CLI');
        $this->webhookAutoConfig = $webhookAutoConfig;
    }
    protected function configure()
    {
        $this->setName('psq');
        $this->setDescription('PublicSquare commands');
        $this->addArgument(
            'configure-webhooks',
            InputArgument::OPTIONAL,
            'Auto-configure PublicSquare Webhooks? (requires PublicSquare Secret API key to be configured)',
            null,
            [true, false],
        );
    }
    /**
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $configureWebhooksArg = $input->getArgument('configure-webhooks');
        if ($configureWebhooksArg) {
            $output->writeln('PublicSquare: Ensure webhooks configured...');
            $this->webhookAutoConfig->ensureWebhookInstalled($output);
            $output->writeln('PublicSquare: Webhooks configured');
        }
        return Command::SUCCESS;
    }

}