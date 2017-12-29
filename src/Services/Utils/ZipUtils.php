<?php

namespace App\Services\Utils;

/**
 * ZipUtils
 */
class ZipUtils
{
    /**
     * Extract a given ZIP file to a given folder (or the file basename as default folder)
     *
     * @param string $zipFile - ZIP file to extract
     * @param string $folder - Destination folder
     *
     * @return bool - Extract status
     */
    public static function extractZipToFolder($zipFile, $folder) : bool
    {
        // Initialize
        $zipPathInfo = pathinfo($zipFile);

        // Check ZIP file
        if (strtolower($zipPathInfo['extension']) !== 'zip') {
            throw new \InvalidArgumentException("Given ZIP file is invalid!");
        }

        // Open archive
        $zip = new \ZipArchive();
        if ($zip->open($zipFile) === false) {
            return false;
        }

        // Extract files to target folder
        $zip->extractTo($folder);
        $zip->close();

        // Success
        return true;
    }
}
