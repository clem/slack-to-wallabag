<?php

namespace App\Services\Slack;

use Doctrine\ORM\EntityManager;
use App\Services\Slack\Traits\CheckFolderTrait;

/**
 * ImportHelper
 */
class ImportHelper
{
    use CheckFolderTrait;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * UsersImportHelper constructor.
     *
     * @param EntityManager $em - Doctrine Entity Manager
     * @param string $appRootDir - Project root directory
     */
    public function __construct(EntityManager $em, $appRootDir)
    {
        // Initialize
        $this->em         = $em;
        $this->appRootDir = $appRootDir;
    }

    /**
     * Get a given JSON file content
     *
     * @param string $file - JSON file to get content from
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed - Parsed JSON content
     */
    protected function getJsonFileContent($file)
    {
        // Initialize
        $jsonFile = $this->addRootDirIfNeeded($file);

        // Check JSON file
        if (!file_exists($jsonFile) || !is_readable($jsonFile)) {
            throw new \InvalidArgumentException("File doesn't exists or isn't readable!");
        }

        // Get JSON file content
        $jsonContent = file_get_contents($jsonFile);
        if (!$jsonContent) {
            throw new \InvalidArgumentException("Can't retrieve file content!");
        }

        // Parse JSON file content
        $json = json_decode($jsonContent);
        if ($json === null) {
            throw new \InvalidArgumentException('File content is not a valid JSON content!');
        }

        // Return parsed JSON
        return $json;
    }
}
