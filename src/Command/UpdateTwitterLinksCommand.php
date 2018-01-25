<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateTwitterLinksCommand extends ContainerAwareCommand
{
    /**
     * @var string
     */
    protected static $defaultName = 'stw:twitter:update-twitter-links';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setDescription('Update Twitter links')
            ->setHelp("This command doesn't take any parameters as they're taken from app parameters.");
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Initialize
        $io = new SymfonyStyle($input, $output);
        $container = $this->getContainer();

        // Get Twitter parameters
        $oauthAccessToken = $container->getParameter('app.twitter_api.oauth_access_token');
        $oauthAccessTokenSecret = $container->getParameter('app.twitter_api.oauth_access_token_secret');
        $consumerKey = $container->getParameter('app.twitter_api.consumer_key');
        $consumerSecret = $container->getParameter('app.twitter_api.consumer_secret');

        // Check Twitter parameters
        if (empty($oauthAccessToken)
        || empty($oauthAccessTokenSecret)
        || empty($consumerKey)
        || empty($consumerSecret)) {
            // Don't do Twitter update
            $io->error("Twitter parameters are not set. Please check them again");
            return;
        }

        // Get Twitter Helper
        $twitter = $container->get('stw.twitter.links_update_helper');
        $updateStatus = $twitter->updateTwitterLinks();

        // Check update status
        if (!$updateStatus) {
            $io->warning('Twitter links update was not made because no Twitter link needs updating!');
            return;
        }

        // End of command
        $io->success('Update Twitter links complete!');
    }
}
