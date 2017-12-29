<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportSlackUsersCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'stw:import:slack-users';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Import a given JSON (of Slack Users)')
            ->addArgument('file', InputArgument::REQUIRED, 'Slack Users JSON file')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Initialize
        $io = new SymfonyStyle($input, $output);
        $file = $input->getArgument('file');

        // Do import users
        $usersImporter = $this->getContainer()->get('stw.import_helper.slack_users');
        $importStatus = $usersImporter->importSlackUsersJsonFile($file);

        // Check status
        if (!$importStatus) {
            $io->error("Oops, there's a bug somewhere");
            return;
        }

        // Everything was fine
        $io->success("JSON file has been imported!");
    }
}
