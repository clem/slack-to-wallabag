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
                'only-channels',
                'c',
                InputOption::VALUE_REQUIRED,
                'Import only these channels and not app import only channels'
            )
            ->addOption(
                'exclude-app-channels-configuration',
                'X',
                InputOption::VALUE_NONE,
                "Don't use app channels configuration"
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
        $doRemoveFolderAfterImport = false;

        // Check folder
        if (empty($folder)) {
            // Generate folder name from archive name
            $zipPathInfo = pathinfo($archive);
            $folder = $zipPathInfo['dirname'].'/'.$zipPathInfo['filename'];

            // And remove folder after import
            $doRemoveFolderAfterImport = true;
        }

        // Do unzip archive
        $unzipStatus = ZipUtils::extractZipToFolder($archive, $folder);
        if (!$unzipStatus) {
            $io->error("Oops, can't extract ZIP file");
            return;
        }

        // Initialize
        $container = $this->getContainer();
        $excludedChannels = $container->getParameter('app.excluded_channels');
        $onlyChannels = $container->getParameter('app.import_only_channels');

        // Get channels configuration from input
        if ($input->getOption('exclude-app-channels-configuration')) {
            $excludedChannels = $input->getOption('excluded-channels') ?? '';
            $onlyChannels = $input->getOption('only-channels') ?? '';
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
            'only_channels' => $onlyChannels
        ]);

        // Check status
        if (!$importStatus) {
            $io->error("Oops, there's a bug somewhere in the import process");
            return;
        }

        // Remove extract folder
        if ($doRemoveFolderAfterImport && !FileUtils::removeDirectory($folder)) {
            $io->error("Can't remove folder");
            return;
        }

        // Everything was fine
        $io->success("Slack archive has been imported!");
    }
}
