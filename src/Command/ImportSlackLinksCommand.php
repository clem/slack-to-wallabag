<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use App\Services\Slack\LinksImportHelper;

class ImportSlackLinksCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected static $defaultName = 'stw:import:slack-links';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Import a given JSON (of Slack messages)')
            ->addArgument('file', InputArgument::REQUIRED, 'Slack Messages JSON file')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Initialize
        $io   = new SymfonyStyle($input, $output);
        $file = $input->getArgument('file');

        // Do import links
        /* @var $linksImporter LinksImportHelper */
        $linksImporter = $this->getContainer()->get('stw.import_helper.slack_links');
        $importStatus  = $linksImporter->importSlackLinksFromMessagesFile($file);

        // Check status
        if (!$importStatus) {
            $io->error("Oops, there's a bug somewhere");
            return;
        }

        // Everything was fine
        $io->success("JSON file has been imported!");
    }
}
