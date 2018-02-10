<?php

namespace App\Services\Slack;

/**
 * Class ApiImportHelper
 */
class ApiImportHelper
{
    /**
     * @var CrawlHelper
     */
    private $crawlHelper;

    /**
     * @var LinksImportHelper
     */
    private $linksImportHelper;

    /**
     * @var UsersImportHelper
     */
    private $usersImportHelper;

    /**
     * Main constructor
     *
     * @param CrawlHelper $crawlHelper - Slack API Crawl Helper
     * @param LinksImportHelper $linksImportHelper - Slack Links Import Helper
     * @param UsersImportHelper $usersImportHelper - Slack Users Import Helper
     */
    public function __construct(
        CrawlHelper $crawlHelper,
        LinksImportHelper $linksImportHelper,
        UsersImportHelper $usersImportHelper
    ) {
        // Initialize
        $this->crawlHelper = $crawlHelper;
        $this->linksImportHelper = $linksImportHelper;
        $this->usersImportHelper = $usersImportHelper;
    }

    /**
     * Crawl Slack users
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return boolean - True if import was made
     */
    public function crawlSlackUsers() : bool
    {
        // Initialize
        $getUsers = $this->crawlHelper->getUsersList();

        // Check users list
        if ($getUsers['ok'] !== true || !is_array($getUsers['members']) || empty($getUsers['members'])) {
            // Don't import list: something failed
            return false;
        }

        // Loop to convert users to objects (instead of arrays)
        $users = [];
        foreach ($getUsers['members'] as $user) {
            $users[] = (object) $user;
        }

        // Update users list
        return $this->usersImportHelper->importUsersList($users);
    }

    /**
     * Crawl Slack messages
     *
     * @param array $options - Options list
     *
     * @return bool - True on (all) import(s) success
     */
    public function crawlSlackMessages(array $options = []) : bool
    {
        // Initialize
        $returnStatus = true;

        // Get public channels list
        $channels = $this->crawlHelper->getChannelsList();

        // Loop on channels to get messages
        foreach ($channels as $channel) {
            // Initialize
            $getMessages = $this->crawlHelper->getChannelMessages($channel);

            // Check messages
            if ($getMessages['ok'] === false) {
                // Error retrieving messages
                continue;
            }

            // Import links from messages
            $channelImportStatus = $this->linksImportHelper
                                        ->importSlackLinks($getMessages['messages'], $channel['name'], $options);

            // Check import status
            if (!$channelImportStatus) {
                // Mark return status as invalid
                $returnStatus = false;
            }
        }

        // Return status
        return $returnStatus;
    }
}
