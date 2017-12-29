<?php

namespace App\Services\Utils;

/**
 * FileUtils
 */
class FileUtils
{
    /**
     * Remove a given directory and its contents
     *
     * @param string $directoryToRemove - Directory to remove
     *
     * @return bool - Remove directory status
     */
    public static function removeDirectory($directoryToRemove) : bool
    {
        // Initialize
        $dir = opendir($directoryToRemove);

        // Loop to remove files
        while (false !== ($file = readdir($dir))) {
            if (($file !== '.') && ($file !== '..')) {
                $full = $directoryToRemove.'/'.$file;
                if (is_dir($full)) {
                    self::removeDirectory($full);
                } else {
                    unlink($full);
                }
            }
        }

        // Close and remove directory
        closedir($dir);
        return rmdir($directoryToRemove);
    }
}
