<?php

namespace App\Services\Slack;

use Doctrine\ORM\OptimisticLockException;
use App\Entity\SlackLink;
use App\Entity\SlackUser;
use App\Repository\SlackLinkRepository;
use App\Repository\SlackUserRepository;
use App\Services\Utils\StringUtils;

/**
 * LinksImportHelper
 */
class LinksImportHelper extends ImportHelper
{
    /**
     * @var string
     */
    private $checkLinkRegExp = '/<(https?:\\/\\/[^>]*)>/im';

    /**
     * @var string
     */
    private $channel;

    /**
     * @var array
     */
    private $slackUsersList;

    /**
     * @var array
     */
    private $slackUsersIds;

    /**
     * @var array
     */
    private $existingLinks;

    /**
     * Import links list from a given JSON file (containing a list of Slack messages)
     *
     * @param string $file - App root relative path to file
     * @param string|null $channel - Message's channel
     *
     * @throws OptimisticLockException
     *
     * @return bool - True if import was made, false if an error occurred
     */
    public function importSlackLinksFromMessagesFile($file, $channel = null) : bool
    {
        // Initialize
        $this->initializeHelperVariables($file, $channel);

        try {
            // Get and import links from messages list
            $json = $this->getJsonFileContent($file);
            return $this->importLinksFromMessagesList($json);
        } catch (\InvalidArgumentException $exception) {
            return false;
        }
    }

    /**
     * Import links from all the JSON files in given folder
     *
     * @param string $folder - Folder to parse
     * @param array $options - Import options
     *
     * @throws OptimisticLockException
     *
     * @return bool
     */
    public function importSlackLinksFromFolder($folder, array $options = []) : bool
    {
        // Initialize
        $this->initializeHelperVariables($folder);
        $folder = $this->addRootDirIfNeeded($folder);

        // Get files in folder
        $files = glob($folder.'/*.json');
        if (empty($files)) {
            return false;
        }

        // Loop on folder files
        foreach ($files as $file) {
            try {
                // Get and import links from messages list
                $json = $this->getJsonFileContent($file);
                $this->importLinksFromMessagesList($json, $options);
            } catch (\InvalidArgumentException $exception) {
                continue;
            }
        }

        // Return status
        return true;
    }

    /**
     * Import Slack links
     *
     * @param array $links - List of links
     * @param string|null $channel - Channel's name
     * @param array $options - Options
     *
     * @return boolean - True on import success
     */
    public function importSlackLinks(array $links, $channel = null, array $options = []) : bool
    {
        // Initialize
        $this->initializeHelperVariables('', $channel);

        try {
            // Convert messages to objects as we use objects here
            foreach ($links as $linkKey => $link) {
                $links[$linkKey] = (object) $link;
            }

            // Import links from messages list
            $this->importLinksFromMessagesList($links, $options);
        } catch (OptimisticLockException $e) {
            return false;
        }

        // Return status
        return true;
    }

    /**
     * Initialize helper variables
     *
     * @param string $file - App root relative path to file
     * @param string|null $channel - Message's channel
     */
    private function initializeHelperVariables($file, $channel = null)
    {
        // Initialize existing data
        /* @var $slackUserRepository SlackUserRepository */
        $slackUserRepository  = $this->em->getRepository('App:SlackUser');
        $this->slackUsersIds  = $slackUserRepository->getUsersSlackIds();
        $this->slackUsersList = $slackUserRepository->getSlackUsersList();
        /* @var $slackLinkRepository SlackLinkRepository */
        $slackLinkRepository = $this->em->getRepository('App:SlackLink');
        $this->existingLinks = $slackLinkRepository->getAllLinksUrls();

        // Initialize channel
        $this->channel = $this->getChannelFromFileNameIfNotSet($file, $channel);
    }

    /**
     * Get channel's name from file name if not set
     *
     * @param string $file - Filename to parse
     * @param string|null $channel - Existing channel
     *
     * @return string - Channel's name
     */
    protected function getChannelFromFileNameIfNotSet($file, $channel = null) : string
    {
        // Check channel
        if ($channel) {
            return $channel;
        }

        // Try to guess channel from folder name
        $filePathInfo = pathinfo($file);
        if ($filePathInfo['basename'] === $filePathInfo['filename']) {
            return $filePathInfo['basename'];
        }

        // Try to guess channel from file name
        $pathInfo = explode('/', $filePathInfo['dirname']);
        if (count($pathInfo) <= 1) {
            throw new \InvalidArgumentException("File path doesn't contains a '/'");
        }

        // Return last directory as channel
        return $pathInfo[count($pathInfo) - 1];
    }

    /**
     * Import links contained in a given messages list
     *
     * @throws \InvalidArgumentException
     * @throws OptimisticLockException
     *
     * @param array $messagesList - Messages list to import
     * @param array $options - Import options
     *
     * @return bool - True on import success
     */
    private function importLinksFromMessagesList($messagesList, array $options = []) : bool
    {
        // Check JSON content
        if (!is_array($messagesList) || empty($messagesList)) {
            throw new \InvalidArgumentException("JSON doesn't contain a list of messages");
        }

        // Loop on messages to parse
        foreach ($messagesList as $message) {
            // Check user message
            if (!in_array($message->user, $this->slackUsersIds, true)) {
                throw new \InvalidArgumentException("Message user doesn't exist!");
            }

            // Get link user
            $slackLinkUserId = array_search($message->user, $this->slackUsersIds);
            if (!array_key_exists($slackLinkUserId, $this->slackUsersList)) {
                throw new \InvalidArgumentException("Message user doesn't exist!");
            }

            // Check if we need to import user's message
            /* @var $user SlackUser */
            $user = $this->slackUsersList[$slackLinkUserId];
            if (isset($options['only_user']) && $options['only_user'] !== $user->getUsername()) {
                // Don't add link from "not-selected" user
                continue;
            }

            // Check if message is valid or if it contains a link
            if (!$this->isMessageValid($message) || !$this->doesMessageContainsLink($message)) {
                // Don't parse message, and don't add link
                continue;
            }

            // Initialize
            $linkUrl = StringUtils::cleanUrlIfNeeded($this->getLinkUrlFromMessage($message));

            // Check link
            if (!$linkUrl || empty($linkUrl) || !filter_var($linkUrl, FILTER_VALIDATE_URL)) {
                // Don't add link
                continue;
            }

            // Check if link already exists
            if (in_array($linkUrl, $this->existingLinks)) {
                // Don't persist an existing link
                continue;
            }

            // Create link from url
            $slackLink = $this->initializeLinkFromMessage($message);
            $this->em->persist($slackLink);
            $this->em->flush();

            // Add link to existing urls
            $this->existingLinks[] = $linkUrl;
        }

        // Return success
        return true;
    }

    /**
     * Check if a given message object is valid
     *
     * @param mixed $message - JSON message as object
     *
     * @return bool - True if message is valid, false otherwise
     */
    private function isMessageValid($message) : bool
    {
        // Check message
        if (!isset($message->type) || $message->type !== 'message') {
            return false;
        }

        // Message is a valid message
        return true;
    }

    /**
     * Check if a given message contains a link
     *
     * @param mixed $message - JSON message as object
     *
     * @return bool - True if message contains a link, false otherwise
     */
    private function doesMessageContainsLink($message) : bool
    {
        // Check if message has attachments
        if (isset($message->attachments)) {
            return true;
        }

        // Check message content
        if (!preg_match($this->checkLinkRegExp, $message->text)) {
            return false;
        }

        // Message text contains a valid/Slack link
        return true;
    }

    /**
     * Get link url from a given message
     *
     * @param mixed $message - JSON message as object
     *
     * @return string - Link contained in message or and empty string on error
     */
    private function getLinkUrlFromMessage($message) : string
    {
        // Check message attachment
        if (isset($message->attachments)) {
            // Initialize
            $linkUrl = $this->getLinkUrlFromMessageAttachment($message);

            // Check link url
            if (!empty($linkUrl)) {
                return $linkUrl;
            }
        }

        // Initialize
        preg_match($this->checkLinkRegExp, $message->text, $links);

        // Check links
        if (isset($links[1]) && filter_var($links[1], FILTER_VALIDATE_URL)) {
            // Return first (and only) link
            return $links[1];
        }

        // Return error
        return '';
    }

    /**
     * Get link url from a given message (with attachments)
     *
     * @param mixed $message - JSON message as object
     *
     * @return string - Link contained in message or and empty string on error
     */
    private function getLinkUrlFromMessageAttachment($message) : string
    {
        // Check message attachment
        if (!isset($message->attachments)) {
            // Message has no attachment
            return '';
        }

        // Initialize
        $attachment = $message->attachments[0];

        // Check service
        if (isset($attachment->service_name) && isset($attachment->text)) {
            // Parse text to find url
            preg_match($this->checkLinkRegExp, $attachment->text, $attachmentLinks);
            if (isset($attachmentLinks[1]) && filter_var($attachmentLinks[1], FILTER_VALIDATE_URL)) {
                return $attachmentLinks[1];
            }
        }

        // Get first attachment link
        if (isset($attachment->from_url)) {
            return $attachment->from_url;
        }

        // Return empty url
        return '';
    }

    /**
     * Create a SlackLink from a given message
     *
     * @param $message - JSON message as object
     *
     * @throws \InvalidArgumentException
     *
     * @return SlackLink - Initialized Slack link
     */
    private function initializeLinkFromMessage($message) : SlackLink
    {
        // Initialize
        $slackLink = new SlackLink();

        // Update link
        $slackLink->setChannel($this->channel);
        $url = $this->getLinkUrlFromMessage($message);
        $slackLink->setUrl(StringUtils::cleanUrlIfNeeded($url));

        // Set link's user
        $slackLinkUserId = array_search($message->user, $this->slackUsersIds);
        $slackLink->setUser($this->slackUsersList[$slackLinkUserId]);

        // Update link dates
        $slackLink->setCreatedAt(new \DateTime());
        $slackLink->setPostedAt(new \DateTime(date('Y-m-d H:i:s', $message->ts)));

        // Get message attachment
        $attachment = ($message->attachments ?? [false])[0];
        if ($attachment) {
            // Update link with attachment info
            $slackLink->setTitle(LinkAttachmentsHelper::getTitleFromAttachment($attachment));

            // Set tags if needed
            $tags = LinkAttachmentsHelper::getTagsFromAttachment($attachment);
            if (!empty($tags)) {
                $slackLink->setTags(implode(', ', $tags));
            }

            // Add thumb if exists
            if (isset($attachment->thumb_url)) {
                $slackLink->setImage($attachment->thumb_url);
            }
        }

        // Return initialized link
        return $slackLink;
    }
}
