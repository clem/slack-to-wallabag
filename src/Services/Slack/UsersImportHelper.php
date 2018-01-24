<?php

namespace App\Services\Slack;

use App\Entity\SlackUser;

/**
 * UsersImportHelper
 */
class UsersImportHelper extends ImportHelper
{
    /**
     * Import a given JSON file containing a list of Slack Users
     *
     * @param string $file - App root relative path to file
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return bool - True if import was made, false if an error occurred
     */
    public function importSlackUsersJsonFile($file) : bool
    {
        try {
            // Import users list in database
            $json = $this->getJsonFileContent($file);
            return $this->importUsersList($json);
        } catch (\InvalidArgumentException $exception) {
            return false;
        }
    }

    /**
     * Import a given users list
     *
     * @param array $usersList - Users list to import
     *
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return bool - True on import success
     */
    public function importUsersList(array $usersList) : bool
    {
        // Check JSON content
        if (!is_array($usersList) || empty($usersList)) {
            throw new \InvalidArgumentException("JSON doesn't contain a list of Slack Users");
        }
        // Initialize
        $currentUsersIds = $this->em->getRepository('App:SlackUser')->getUsersSlackIds();

        // Loop on list of users
        foreach ($usersList as $rawUser) {
            // Check if user already exists
            if (in_array($rawUser->id, $currentUsersIds, true)) {
                continue;
            }

            // Initialize
            $user = new SlackUser();
            $user->setSlackId($rawUser->id);
            $user->setUsername($rawUser->name);
            $user->setRealName($rawUser->real_name);

            // Check for profile and avatar
            if (isset($rawUser->profile->image_original)) {
                $user->setAvatar($rawUser->profile->image_original);
            }

            // Persist user
            $this->em->persist($user);
            $this->em->flush();
        }

        // Return success
        return true;
    }
}
