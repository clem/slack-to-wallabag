<?php

namespace App\Services\Slack;

use App\Services\Utils\StringUtils;

/**
 * Class LinkAttachmentsHelper
 */
class LinkAttachmentsHelper
{
    /**
     * Regular expression to find hashtags
     *
     * @var string
     */
    private static $getHashtagRegExp = '/(#[^\s]*)\s*/';

    /**
     * Get title from a given message attachment
     *
     * @param $attachment - Attachment
     *
     * @return string - Attachment's title
     */
    public static function getTitleFromAttachment($attachment) : string
    {
        // Check for title
        if (isset($attachment->title)) {
            return StringUtils::cleanStringForDatabase($attachment->title);
        }

        // Check for text
        if (!isset($attachment->text)) {
            return '';
        }

        // Initialize
        $titleToClean = $attachment->text;

        // Remove tags
        $tags = self::getTagsFromAttachment($attachment);
        $titleToClean = str_replace($tags, '', $titleToClean);

        // Return cleaned title
        return StringUtils::cleanStringForDatabase($titleToClean);
    }

    /**
     * Get tags from a given attachment
     *
     * @param $attachment - Attachment
     *
     * @return array - Tags list
     */
    public static function getTagsFromAttachment($attachment) : array
    {
        // Check text
        if (!isset($attachment->text)) {
            return [];
        }

        // Initialize
        preg_match_all(self::$getHashtagRegExp, $attachment->text, $tags);

        // Check tags
        if (empty($tags)) {
            return $tags;
        }

        // Return tags
        return isset($tags[1]) && !empty($tags[1]) ? $tags[1] : [];
    }
}
