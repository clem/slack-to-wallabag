<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CrawlSlackCommand extends ContainerAwareCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'stw:crawl:slack';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Crawl users and messages from Slack API')
            ->setHelp("This command doesn't take any parameters as they're taken from app parameters.")
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Initialize
        $io = new SymfonyStyle($input, $output);

        // Set options
        $options = [];
        $onlyUser = $this->getContainer()->getParameter('app.import_only_user_links');
        if (!empty($onlyUser)) {
            $options['only_user'] = $onlyUser;
        }

        // Process to import: users then messages
        $apiImportHelper = $this->getContainer()->get('stw.import_helper.api_import');
        $apiImportHelper->crawlSlackUsers();
        $importStatus = $apiImportHelper->crawlSlackMessages($options);

        // Check status
        if (!$importStatus) {
            $io->error('Something failed!');
            return;
        }

        // End of command
        $io->success('Everything is imported!');
    }
}
