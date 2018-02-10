<?php

namespace App\Services\Slack;

/**
 * Trait CheckFolderTrait
 */
trait CheckFolderTrait
{
    /**
     * @var string
     */
    protected $appRootDir;

    /**
     * Prefix with root
     *
     * @param string $folder - Folder to check
     *
     * @return string - Updated folder
     */
    protected function addRootDirIfNeeded($folder): string
    {
        // Check folder
        if (strpos($folder, $this->appRootDir) !== false) {
            // Return folder
            return $folder;
        }

        // Return updated folder
        return $this->appRootDir.'/'.$folder;
    }
}
