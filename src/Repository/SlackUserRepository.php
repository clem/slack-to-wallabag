<?php

namespace App\Repository;

use App\Entity\SlackUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class SlackUserRepository extends ServiceEntityRepository
{
    /**
     * @inheritdoc
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SlackUser::class);
    }

    /**
     * Get list of existing Slack Ids
     *
     * @return array - Associative array of Users Slack ids (with user id as key)
     */
    public function getUsersSlackIds()
    {
        // Initialize
        $users = $this->createQueryBuilder('u')
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        // Filter Slack Ids
        $slackIds = [];
        array_walk($users, function ($user) use (&$slackIds) {
            /* @var $user SlackUser */
            $slackIds[$user->getId()] = $user->getSlackId();
        });

        // Return Slack Ids only
        return $slackIds;
    }

    /**
     * Get list of all Slack users
     *
     * @return array - Associative array of Users (with user id as key)
     */
    public function getSlackUsersList()
    {
        // Initialize
        $users = $this->createQueryBuilder('u')
                      ->orderBy('u.id', 'ASC')
                      ->getQuery()
                      ->getResult();

        // Update users list
        $usersList = [];
        array_walk($users, function ($user) use (&$usersList) {
            /* @var $user SlackUser */
            $usersList[$user->getId()] = $user;
        });

        // Return users list
        return $usersList;
    }

    /**
     * Count all users
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return mixed
     */
    public function countAll()
    {
        return $this->createQueryBuilder('u')
                    ->select('count(u.id)')
                    ->getQuery()
                    ->getSingleScalarResult();
    }
}
