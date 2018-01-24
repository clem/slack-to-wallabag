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
     * Main constructor
     *
     * @param string $slackOauthToken - Slack OAuth Token
     * @param string $excludedChannels - List of excluded channels (separated with comma)
     */
    public function __construct($slackOauthToken, $excludedChannels)
    {
        // Initialize
        $this->slack = new slack($slackOauthToken);
        $this->excludedChannels = explode(',', $excludedChannels);
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

        // Check for excluded channels
        if (!empty($this->excludedChannels)) {
            // Loop on channels to excluded unwanted channels
            foreach ($channelsListResponse['channels'] as $channel) {
                if (!in_array($channel['name'], $this->excludedChannels)) {
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
