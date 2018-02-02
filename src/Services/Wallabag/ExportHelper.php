<?php

namespace App\Services\Wallabag;

use App\Entity\SlackLink;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use GuzzleHttp\Client;

class ExportHelper
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var string
     */
    private $wallabagOptions;

    /**
     * @var string
     */
    private $accessToken = null;

    /**
     * Main constructor
     *
     * @param EntityManager $em - Entity Manager
     * @param array $wallabagOptions - Wallabag API base Url
     */
    public function __construct(EntityManager $em, array $wallabagOptions)
    {
        // Initialize
        $this->wallabagOptions = $wallabagOptions;
        $this->em = $em;

        // Initialize Guzzle Client
        $this->client = new Client([
            'base_uri' => $wallabagOptions['api_base_url'],
        ]);
    }

    /**
     * Generate Wallabag Access-Token
     *
     * @param array $options - Export options
     *
     * @throws \RuntimeException
     */
    private function generateAccessToken(array $options = [])
    {
        // Initialize
        $postOptions = [
            'grant_type' => 'password',
            'client_id' => $this->wallabagOptions['client_id'],
            'client_secret' => $this->wallabagOptions['client_secret'],
            'username' => $this->wallabagOptions['user_username'],
            'password' => $this->wallabagOptions['user_password']
        ];

        // Try to generate token
        $response = $this->client->request('POST', '/oauth/v2/token', [
            'form_params' => $postOptions
        ]);

        // Check response
        if ($response->getStatusCode() !== 200 || $response->getReasonPhrase() !== 'OK') {
            throw new \RuntimeException("Can't generate access token!");
        }

        // Token has been generated
        $jsonResponse = json_decode((string) $response->getBody());

        // Store only token
        $this->accessToken = $jsonResponse->access_token;

        // Check show option
        if ($options['show_access_token']) {
            echo 'Access token has been generated: '.$this->accessToken."\n";
        }
    }

    /**
     * Export all 'un-exported' links to Wallabag
     *
     * @param array $options - Export options
     *
     * @throws OptimisticLockException
     *
     * @return boolean - Export status
     */
    public function exportAllUnexportedLinks(array $options = []) : bool
    {
        // Initialize: retrieve all links to export
        $linksToExport = $this->em->getRepository('App:SlackLink')->findBy(
            ['exportedAt' => null]
        );

        // Check if no link needs to be exported
        if (!count($linksToExport)) {
            return false;
        }

        // Try to generate access token (to access API)
        try {
            $this->generateAccessToken($options);
        } catch (\RuntimeException $e) {
            // Can't get access token, don't export link
            return false;
        }

        // Loop to export links
        foreach ($linksToExport as $l => $link) {
            // Export link
            $exportStatus = $this->exportLink($link);

            // Check export status
            if ($exportStatus) {
                // Display exported link
                if ($options['list_exported_links']) {
                    echo vsprintf('Exported link: #%1$d - %2$s', [$link->getId(), $link->getUrl()])."\n";
                }

                // Update link
                $link->setExportedAt(new \DateTime());
                $this->em->persist($link);
                $this->em->flush();
            }
        }

        // Return status
        return true;
    }

    /**
     * Export a given link to Wallabag
     *
     * @param SlackLink $link - Link to export
     *
     * @return boolean - True if export was made, false otherwise
     */
    private function exportLink(SlackLink $link) : bool
    {
        // Initialize
        $linkInfo = $this->getLinkExportInfo($link);

        // Check access token
        if ($this->accessToken === null) {
            // Don't export link as we don't have access
            return false;
        }

        // Do export request
        $response = $this->client->request('POST', '/api/entries.json', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->accessToken,
                'Accept'        => 'application/json',
            ],
            'form_params' => $linkInfo
        ]);

        // Check export response
        if ($response->getStatusCode() !== 200 || $response->getReasonPhrase() !== 'OK') {
            // There was a bug in export
            return false;
        }

        // Export was made with success
        return true;
    }

    /**
     * Get Slack link export info
     *
     * @param SlackLink $link - Slack link to export
     *
     * @return array - Link export info
     */
    private function getLinkExportInfo(SlackLink $link) : array
    {
        // Initialize
        $url  = $link->getUrl();
        $tags = $this->getLinkTags($link);

        // Check real url
        if ($link->getRealUrl()) {
            $url = $link->getRealUrl();
        }

        // Create link information (to send to Wallabag)
        $linkInfo = [
            'url'          => $url,
            'title'        => $link->getTitle(),
            'tags'         => $tags,
            'published_at' => $link->getPostedAt()->getTimestamp()
        ];

        // Check real url
        if ($link->getRealUrl()) {
            // Set url as origin
            $linkInfo['origin_url'] = $link->getUrl();
        }

        // Return export info
        return $linkInfo;
    }

    /**
     * Get tags as string (for Wallabag export)
     *
     * @param SlackLink $link
     *
     * @return string - Tags list
     */
    private function getLinkTags(SlackLink $link) : string
    {
        // Initialize
        $tags = explode(', ', $link->getTags());

        // Clean tags
        $tags = array_map(function ($tag) {
            return trim(str_replace('#', '', $tag));
        }, $tags);

        // Add channel as first tag
        array_unshift($tags, $link->getChannel());

        // Do unique to prevent duplicated tags
        $tags = array_unique($tags);

        // Return formatted tags
        return implode(',', $tags);
    }
}
