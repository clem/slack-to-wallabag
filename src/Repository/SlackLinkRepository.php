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
     * @return array - Links urls list
     */
    public function getAllLinksUrls() : array
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

    /**
     * Get all Twitter links with an empty title
     *
     * @param int $limit - Max returned results
     *                   - 900 as default as it's Twitter API rate limit (for 15 minutes)
     *
     * @return array - Twitter links
     */
    public function getTwitterLinksWithoutTitle($limit = 900) : array
    {
        return $this->createQueryBuilder('l')
                      ->where('l.url LIKE :twitter')
                      ->setParameter('twitter', '%twitter.com%')
                      ->andWhere('l.title IS NULL OR l.title = :empty_string')
                      ->setParameter('empty_string', '')
                      ->setMaxResults($limit)
                      ->getQuery()
                      ->getResult();
    }
}
