<?php

namespace App\Services\Twitter;

use App\Entity\SlackLink;
use App\Services\Utils\StringUtils;
use Doctrine\ORM\EntityManager;

/**
 * Class LinksUpdateHelper
 */
class LinksUpdateHelper
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var \TwitterAPIExchange
     */
    private $twitter;

    /**
     * Main constructor
     *
     * @param EntityManager $em - Entity manager
     * @param array $twitterApiSettings - Twitter API settings
     */
    public function __construct(EntityManager $em, array $twitterApiSettings)
    {
        // Initialize
        $this->em = $em;
        $this->twitter = new \TwitterAPIExchange([
            'oauth_access_token'        => $twitterApiSettings['oauth_access_token'],
            'oauth_access_token_secret' => $twitterApiSettings['oauth_access_token_secret'],
            'consumer_key'              => $twitterApiSettings['consumer_key'],
            'consumer_secret'           => $twitterApiSettings['consumer_secret'],
        ]);
    }

    /**
     * Update Twitter links
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function updateTwitterLinks() : bool
    {
        // Initialize:
        $slackLinkRepository = $this->em->getRepository('App:SlackLink');

        // Get Twitter links
        $twitterLinks = $slackLinkRepository->getTwitterLinksWithoutTitle();
        $showTweetApiUrl = 'https://api.twitter.com/1.1/statuses/show.json';

        // Check Twitter links
        if (!count($twitterLinks)) {
            // Don't process to import
            return false;
        }

        // Loop on Twitter links
        /* @var SlackLink $link */
        foreach ($twitterLinks as $link) {
            // Initialize
            $tweetId = $this->getTweetIdFromLink($link);
            $getField = '?include_entities=true&id='.$tweetId;

            // Get Twitter content
            $jsonResponse = $this->twitter->setGetfield($getField)
                                ->buildOauth($showTweetApiUrl, 'GET')
                                ->performRequest();
            $response = json_decode($jsonResponse);
            if ($response === null) {
                // Don't do anything
                continue;
            }

            // Check main url
            if (!empty($response->entities->urls)) {
                // Take only the first url
                $mainUrl = $response->entities->urls[0];

                // Set real url
                $link->setRealUrl($mainUrl->expanded_url);
            }

            // Update link with tweet information
            $link->setTitle($this->getTitleFromTweet($response));
            $link->setImage($this->getTweetImageUrl($response));
            $link->setTags($this->getTweetHashtags($response));

            // Persist link and flush
            $this->em->persist($link);
            $this->em->flush();
        }

        // Return status
        return true;
    }

    /**
     * Get tweet id from a given Twitter link
     *
     * @param SlackLink $link - Link to get tweet's id from
     *
     * @return mixed - Tweet id or false on error
     */
    public function getTweetIdFromLink(SlackLink $link)
    {
        // Initialize
        $tweetUrl = $link->getUrl();

        // Parse url
        if (!preg_match('/https\:\/\/twitter\.com\/([^\/]*)\/status\/(\d+)/', $tweetUrl, $urlInfo)) {
            // Tweet url is invalid
            return false;
        }

        // Return found id
        return $urlInfo[2];
    }

    /**
     * Get link's title from tweet
     *
     * @param \StdClass $tweetResponse - Tweet response
     *
     * @return mixed
     */
    private function getTitleFromTweet($tweetResponse)
    {
        // Check text
        if (!isset($tweetResponse->text)) {
            // Reset title
            return null;
        }

        // Initialize
        $title = StringUtils::cleanStringForDatabase($tweetResponse->text);

        // Check title
        if (empty($title)) {
            // Reset title
            return null;
        }

        // Return title
        return $title;
    }

    /**
     * Get tweet hashtags
     *
     * @param \StdClass $tweetResponse - Tweet response
     * @param boolean $returnAsString - Return response as string?
     * @param string $listSeparator - String list separator
     *
     * @return mixed - List of hashtags as array or as string
     */
    private function getTweetHashtags($tweetResponse, $returnAsString = true, $listSeparator = ', ')
    {
        // Initialize
        $tags = [];

        // Check for hashtags
        if (empty($tweetResponse->entities->hashtags)) {
            return null;
        }

        // Loop on hashtags
        foreach ($tweetResponse->entities->hashtags as $tag) {
            $tags[] = '#'.$tag->text;
        }

        // Check for return
        if (!$returnAsString) {
            // Return tags as array
            return $tags;
        }

        // Return list as string
        return implode($listSeparator, $tags);
    }

    /**
     * @param \StdClass $tweetResponse - Tweet response
     *
     * @return mixed - Image url or null if no image was found
     */
    private function getTweetImageUrl($tweetResponse)
    {
        // Check image media
        if (empty($tweetResponse->entities->media)) {
            return null;
        }

        // Loop on media
        foreach ($tweetResponse->entities->media as $media) {
            // Check media type
            if ($media->type !== 'photo') {
                // Don't do anything for this media
                continue;
            }

            // Check for https url
            if (filter_var($media->media_url_https, FILTER_VALIDATE_URL)) {
                // Return media https url
                return $media->media_url_https;
            }

            // Check for http url
            if (filter_var($media->media_url, FILTER_VALIDATE_URL)) {
                // Return media url (without https)
                return $media->media_url;
            }
        }

        // Media doesn't contains any image
        return null;
    }
}
