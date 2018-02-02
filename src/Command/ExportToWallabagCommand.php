<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class ExportToWallabagCommand
 */
class ExportToWallabagCommand extends ContainerAwareCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'stw:export:wallabag';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setDescription('Export all "not-exported" links to Wallabag');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Initialize
        $io = new SymfonyStyle($input, $output);
        $exportHelper = $this->getContainer()->get('stw.export_helper.wallabag');

        // Export all links to Wallabag
        $exportStatus = $exportHelper->exportAllUnexportedLinks([
            'list_exported_links' => true,
            'show_access_token' => true,
        ]);

        // Check export status
        if (!$exportStatus) {
            $io->warning('Export was not made!');
            return;
        }

        // Export done
        $io->success('Export made with success.');
    }
}
