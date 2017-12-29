<?php

namespace App\Repository;

use App\Entity\SlackLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class SlackLinkRepository extends ServiceEntityRepository
{
    /**
     * @inheritdoc
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, SlackLink::class);
    }

    /**
     * Get existing links list
     *
     * @return array
     */
    public function getAllLinksUrls()
    {
        // Initialize
        $links = $this->createQueryBuilder('l')
                      ->orderBy('l.id', 'ASC')
                      ->getQuery()
                      ->getResult();

        // Filter to get only urls
        return array_map(function ($link) {
            /* @var $link SlackLink */
            return $link->getUrl();
        }, $links);
    }
}
