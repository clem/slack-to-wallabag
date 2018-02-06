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

    /**
     * Count all links
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return mixed - Total links number
     */
    public function countAll()
    {
        return $this->createQueryBuilder('l')
            ->select('count(l.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get channels list with total links
     *
     * @return array - Channels list with total links
     */
    public function countByChannel()
    {
        return $this->createQueryBuilder('l')
            ->select('l.channel')
            ->addSelect('count(l.id) AS total')
            ->groupBy('l.channel')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count links by a given date field
     *
     * @param string $dateField - Date field
     * @param int $maxDays - Max returned days
     *
     * @return array - Days list with total related links
     */
    public function countByDate($dateField = 'createdAt', $maxDays = 0)
    {
        // Initialize
        $minDate = date('Y-m-d', 0);

        // Check max days
        if ($maxDays) {
            $minDate = date('Y-m-d', strtotime(($maxDays * -1).' days'));
        }

        // Return executed query
        return $this->createQueryBuilder('l')
                    ->select('DATE(l.'.$dateField.') AS day, COUNT(DISTINCT l.id) AS total')
                    ->where('l.'.$dateField.' > :min_date')
                    ->setParameter('min_date', $minDate)
                    ->groupBy('day')
                    ->getQuery()
                    ->getResult();
    }
}
