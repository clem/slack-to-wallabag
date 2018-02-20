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

        // Filter channels list
        $channelsList = $this->removeExcludedChannels($channelsListResponse['channels']);
        $channelsList = $this->removeUnwantedChannels($channelsList);

        // Return channels list
        return $channelsList;
    }

    /**
     * Remove excluded channels of a given channels list
     *
     * @param array $channels - Channels list to filter
     *
     * @return array - Updated channels
     */
    private function removeExcludedChannels(array $channels) : array
    {
        // Check channels list
        if (empty($channels) || empty($this->excludedChannels)) {
            return $channels;
        }

        // Initialize
        $filteredChannels = [];

        // Loop on channels list
        /* @var array $channel */
        foreach ($channels as $channel) {
            if (!in_array($channel['name'], $this->excludedChannels)) {
                $filteredChannels[] = $channel;
            }
        }

        // Return filtered channels
        return $filteredChannels;
    }

    /**
     * Remove unwanted channels of a given channels list
     *
     * @param array $channels - Channels list to filter
     *
     * @return array - Updated channels
     */
    private function removeUnwantedChannels(array $channels) : array
    {
        // Check channels list
        if (empty($channels) || empty($this->importOnlyChannels)) {
            return $channels;
        }

        // Initialize
        $filteredChannels = [];

        // Loop on channels list
        /* @var array $channel */
        foreach ($channels as $channel) {
            if (in_array($channel['name'], $this->importOnlyChannels)) {
                $filteredChannels[] = $channel;
            }
        }

        // Return filtered channels
        return $filteredChannels;
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
