<?php

namespace App\Services\Slack;

/**
 * FullImportHelper
 */
class FullImportHelper extends ImportHelper
{
    /**
     * @var UsersImportHelper
     */
    private $slackUsersImportHelper;

    /**
     * @var LinksImportHelper
     */
    private $slackLinksImportHelper;

    /**
     * Set $slackUsersImportHelper
     *
     * @param UsersImportHelper $helper
     */
    public function setSlackUsersImportHelper(UsersImportHelper $helper)
    {
        $this->slackUsersImportHelper = $helper;
    }

    /**
     * Set $slackLinksImportHelper
     *
     * @param LinksImportHelper $helper
     */
    public function setSlackLinksImportHelper(LinksImportHelper $helper)
    {
        $this->slackLinksImportHelper = $helper;
    }

    /**
     * Import all (users and channels) from a given folder
     *
     * @param string $importFolder - Folder to import data from
     * @param array $options - Options list
     *
     * @return bool - True if import was made, false otherwise
     */
    public function importAllFromFolder($importFolder, array $options = []) : bool
    {
        // Initialize
        $folder = $importFolder;

        // Check folder
        if (strpos($importFolder, $this->appRootDir) === false) {
            // Initialize
            $folder = $this->appRootDir.'/'.$importFolder;
        }

        // Check folder
        if (!file_exists($folder) || !is_readable($folder)) {
            throw new \InvalidArgumentException("Folder $folder is not readable");
        }

        // Initialize: import users
        $importStatus = $this->slackUsersImportHelper->importSlackUsersJsonFile($folder.'/users.json');
        if (!$importStatus) {
            return false;
        }

        // And import all channels now
        return $this->importAllChannelsFromFolder($folder, $options);
    }

    /**
     * Import all channels from folder
     *
     * @param string $importFolder - Folder to import data from
     * @param array $options - Options list
     *
     * @return bool - True if import was made, false otherwise
     */
    public function importAllChannelsFromFolder($importFolder, array $options = []) : bool
    {
        // Initialize
        $folder = $importFolder;

        // Check folder
        if (strpos($importFolder, $this->appRootDir) === false) {
            // Initialize
            $folder = $this->appRootDir.'/'.$importFolder;
        }

        // Check folder
        if (!file_exists($folder) || !is_readable($folder)) {
            throw new \InvalidArgumentException("Folder $folder is not readable");
        }

        // Check folder's name
        if (substr($folder, strlen($folder) - 1, 1) !== '/') {
            $folder .= '/';
        }

        // Initialize: get channels list
        $channels = $this->getJsonFileContent($folder.'channels.json');

        // Get options
        $doImportArchivedChannels = $options['import_archived_channels'] ?? false;
        $excludedChannels = $options['excluded_channels'] ?? '';
        $channelsToExclude = explode(',', $excludedChannels);

        // Check for no excluded channels
        if (empty($channelsToExclude[0])) {
            $channelsToExclude = [];
        }

        // Loop on channels and import wanted contents
        foreach ($channels as $channel) {
            // Check for archived channel
            if (!$doImportArchivedChannels && $channel->is_archived) {
                // Don't import archived channel
                continue;
            }

            // Check for excluded channels
            if (in_array($channel->name, $channelsToExclude)) {
                // Don't import excluded channel
                continue;
            }

            // Import channel
            $importStatus = $this->slackLinksImportHelper
                                 ->importSlackLinksFromFolder(
                                     $folder.$channel->name.'/',
                                     $options
                                 );
            if (!$importStatus) {
                return false;
            }
        }

        // Import was made with success
        return true;
    }
}
