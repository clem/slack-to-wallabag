<?php

namespace App\Services\Utils;

/**
 * StringUtils
 */
class StringUtils
{
    /**
     * Remove Emojis from a given text
     *
     * @param string $text - Text to remove Emojis from
     *
     * @return string - "Cleaned" text
     */
    public static function removeEmojiFromText($text) : string
    {
        // Initialize clean regular expressions
        $regExps = [
            // First clean
            '/([0-9|#][\x{20E3}])'.
            '|[\x{00ae}|\x{00a9}|\x{203C}|\x{2047}|\x{2048}|'.
            '\x{2049}|\x{3030}|\x{303D}|\x{2139}|\x{2122}'.
            '|\x{3297}|\x{3299}][\x{FE00}-\x{FEFF}]?'.
            '|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?'.
            '|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?'.
            '|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?'.
            '|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?'.
            '|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?'.
            '|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?'.
            '|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?'.
            '|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u',

            '/[\x{1F600}-\x{1F64F}]/u', // Match Emoticons
            '/[\x{1F300}-\x{1F5FF}]/u', // Match Miscellaneous Symbols and Pictographs
            '/[\x{1F680}-\x{1F6FF}]/u', // Match Transport And Map Symbols
            '/[\x{2600}-\x{26FF}]/u', // Match Miscellaneous Symbols
            '/[\x{2700}-\x{27BF}]/u', // Match Dingbats
            '/[\x{1F1E6}-\x{1F1FF}]/u', // Match Flags
            '/[\x{1F910}-\x{1F95E}]/u',
            '/[\x{1F980}-\x{1F991}]/u',
            '/[\x{1F9C0}]/u',
            '/[\x{1F9F9}]/u',
        ];

        // Loop on clean regular expressions
        foreach ($regExps as $regExp) {
            $text = preg_replace($regExp, '', $text);
        }

        // Return cleaned text
        return $text;
    }

    /**
     * Clean a given string: remove links, emojis and line-returns
     *
     * @param string $string - String to clean
     *
     * @return string - Cleaned string
     */
    public static function cleanStringForDatabase($string) : string
    {
        // Remove link(s)
        $stringToClean = preg_replace('/https?:\\/\\/[^\\s]*/im', '', $string);

        // Remove Emojis
        $stringToClean = self::removeEmojiFromText($stringToClean);

        // Clean to have a 255 max-length string without line-returns
        $stringToClean = str_replace("\n", '', $stringToClean);
        if (strlen($stringToClean) >= 255) {
            // Trim title
            $stringToClean = substr($stringToClean, 0, 250).'...';
        }

        // Return cleaned title
        return trim($stringToClean);
    }

    /**
     * Clean a given url if needed
     *
     * @param string $urlToClean - Url to clean
     *
     * @return string - Cleaned url
     */
    public static function cleanUrlIfNeeded($urlToClean) : string
    {
        // Initialize: clean url
        $urlToClean = str_replace('&amp;', '&', $urlToClean);

        // Check url
        if (strlen($urlToClean) < 255) {
            // Url is short enough
            return $urlToClean;
        }

        // Try to clean url
        $parsedUrl = parse_url($urlToClean);
        $url = $parsedUrl['scheme'].'://'.$parsedUrl['host'].$parsedUrl['path'];

        // Check for query
        if (!isset($parsedUrl['query']) || empty($parsedUrl['query'])) {
            // Url has no query
            return $url;
        }


        // Try to add a maximum of query params
        $parsedQuery = explode('&', $parsedUrl['query']);
        foreach ($parsedQuery as $queryIndex => $query) {
            // Initialize
            $urlFragment = ($queryIndex === 0 ? '?' : '&').$query;

            // Check url length
            if (strlen($url.$urlFragment) >= 254) {
                break;
            }

            // Add fragment to url
            $url .= $urlFragment;
        }

        // Return cleaned url
        return $url;
    }
}
