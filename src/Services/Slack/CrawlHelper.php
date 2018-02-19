<?php

namespace App\Services\Slack;

use wrapi\slack\slack;

/**
 * Class CrawlHelper
 */
class CrawlHelper
{
    /**
     * @var slack
     */
    private $slack;

    /**
     * @var array
     */
    private $excludedChannels;

    /**
     * @var array
     */
    private $importOnlyChannels;

    /**
     * Main constructor
     *
     * @param string $slackOauthToken - Slack OAuth Token
     * @param string $excludedChannels - List of excluded channels (separated with comma)
     * @param string $importOnlyChannels - List of channels (separated with comma) to import only
     *                                     This option won't override excluded channels
     */
    public function __construct($slackOauthToken, $excludedChannels, $importOnlyChannels)
    {
        // Initialize
        $this->slack = new slack($slackOauthToken);
        $this->excludedChannels = explode(',', $excludedChannels);
        $this->importOnlyChannels = explode(',', $importOnlyChannels);
    }

    /**
     * Get users list
     *
     * @return array - Users list
     */
    public function getUsersList() : array
    {
        // Initialize
        return $this->slack->users->list();
    }

    /**
     * Get channels list
     *
     * @return array - Channels list
     */
    public function getChannelsList() : array
    {
        // Initialize
        $channelsList = [];

        // Get channels list
        $channelsListResponse = $this->slack->channels->list();

        // Check response
        if ($channelsListResponse['ok'] !== true) {
            // Return empty list
            return $channelsList;
        }

        // Check for channels filter
        if (!empty($this->excludedChannels) || !empty($this->importOnlyChannels)) {
            // Loop on channels to excluded unwanted channels
            foreach ($channelsListResponse['channels'] as $channel) {
                // Check if we need to import channel
                if (!in_array($channel['name'], $this->excludedChannels)
                || in_array($channel['name'], $this->importOnlyChannels)) {
                    $channelsList[] = $channel;
                }
            }
        }

        // Return channels list
        return $channelsList;
    }

    /**
     * Get channel last messages
     *
     * @param array $channel - Channel to get messages from
     *
     * @return array - List of channels messages
     */
    public function getChannelMessages(array $channel) : array
    {
        return $this->slack->channels->history([
            'channel' => $channel['id'],
            'count' => 1000,
        ]);
    }
}
