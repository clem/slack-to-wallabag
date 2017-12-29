<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use App\Services\Slack\FullImportHelper;
use App\Services\Utils\FileUtils;
use App\Services\Utils\ZipUtils;

class ImportSlackFullExportCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected static $defaultName = 'stw:import:full-slack-export';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Import a Full Slack export (in ZIP format) in the database')
            ->addArgument(
                'archive',
                InputArgument::REQUIRED,
                'Slack ZIP archive'
            )
            ->addOption(
                'folder',
                'f',
                InputOption::VALUE_REQUIRED,
                'Extract ZIP archive to this folder'
            )
            ->addOption(
                'excluded-channels',
                'x',
                InputOption::VALUE_REQUIRED,
                'List of channels to exclude from import'
            )
            ->addOption(
                'only-user',
                'u',
                InputOption::VALUE_REQUIRED,
                "Only import given user's links"
            )
            ->addOption(
                'exclude-app-channels',
                'X',
                InputOption::VALUE_NONE,
                "Don't import app excluded channels"
            )
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Initialize
        $io = new SymfonyStyle($input, $output);
        $archive = $input->getArgument('archive');
        $folder = $input->getOption('folder') ?? '';

        // Check folder
        if (empty($folder)) {
            // Generate folder name from archive name
            $zipPathInfo = pathinfo($archive);
            $folder = $zipPathInfo['dirname'].'/'.$zipPathInfo['filename'];
        }

        // Do unzip archive
        $unzipStatus = ZipUtils::extractZipToFolder($archive, $folder);
        if (!$unzipStatus) {
            $io->error("Oops, can't extract ZIP file");
            return;
        }

        // Initialize
        $container = $this->getContainer();
        $defaultExcludedChannels = $container->getParameter('app.excluded_channels');

        // Get excluded channels
        $excludedChannels = $input->getOption('excluded-channels') ?? '';
        if ($input->getOption('exclude-app-channels')) {
            $excludedChannels = $defaultExcludedChannels;
        }

        // Check for "only user links"
        $onlyUser = $input->getOption('only-user') ?? false;

        // Do import links
        /* @var $fullImportHelper FullImportHelper */
        $fullImportHelper = $container->get('stw.import_helper.full_import');

        // And return status
        $importStatus = $fullImportHelper->importAllFromFolder($folder, [
            'excluded_channels' => $excludedChannels,
            'only_user' => $onlyUser,
        ]);

        // Check status
        if (!$importStatus) {
            $io->error("Oops, there's a bug somewhere in the import process");
            return;
        }

        // Remove extract folder
        if (!FileUtils::removeDirectory($folder)) {
            $io->error("Can't remove folder");
            return;
        }

        // Everything was fine
        $io->success("Slack archive has been imported!");
    }
}
