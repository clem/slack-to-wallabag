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
        $channelsList = $this->filterUnwantedChannels(
            $channelsListResponse['channels'],
            $this->excludedChannels,
            false
        );
        $channelsList = $this->filterUnwantedChannels(
            $channelsList,
            $this->importOnlyChannels,
            true
        );

        // Return channels list
        return $channelsList;
    }

    /**
     * Filter unwanted channels from a given channels list
     *
     * @param array $channels - Channels list to filter
     * @param array $filter - Filter list
     * @param bool $inArrayMustReturn - To filter, in_array must return this value
     *
     * @return array - Filtered channels list
     */
    private function filterUnwantedChannels(array $channels, array $filter, $inArrayMustReturn = false) : array
    {
        // Check channels list
        if (empty($channels) || empty($filter)) {
            return $channels;
        }

        // Initialize
        $filteredChannels = [];

        // Loop on channels list
        /* @var array $channel */
        foreach ($channels as $channel) {
            if (in_array($channel['name'], $filter) === $inArrayMustReturn) {
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
