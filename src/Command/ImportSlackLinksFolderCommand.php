<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use App\Services\Slack\LinksImportHelper;

class ImportSlackLinksFolderCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected static $defaultName = 'stw:import:slack-links-folder';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Import all JSONs (of Slack Messages) from a given folder')
            ->addArgument('folder', InputArgument::REQUIRED, 'JSONs folder')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Initialize
        $io   = new SymfonyStyle($input, $output);
        $folder = $input->getArgument('folder');

        // Do import links
        /* @var $linksImporter LinksImportHelper */
        $linksImporter = $this->getContainer()->get('stw.import_helper.slack_links');
        $importStatus  = $linksImporter->importSlackLinksFromFolder($folder);

        // Check status
        if (!$importStatus) {
            $io->error("Oops, there's a bug somewhere");
            return;
        }

        // Everything was fine
        $io->success("JSON folder has been imported!");
    }
}
