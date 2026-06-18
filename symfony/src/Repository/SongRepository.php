<?php

namespace App\Repository;

use App\Entity\Song;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Song>
 */
class SongRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Song::class);
    }

    /**
     * Recherche multi-critères sur tous les champs métadonnées
     *
     * @param string $query Terme de recherche
     * @return Song[]
     */
    public function searchByCriteria(string $query): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.artistes', 'art')
            ->leftJoin('s.genres', 'g')
            ->leftJoin('s.albums', 'alb')
            ->where('LOWER(s.filename) LIKE LOWER(:q)')
            ->orWhere('LOWER(s.title) LIKE LOWER(:q)')
            ->orWhere('LOWER(art.nom) LIKE LOWER(:q)')
            ->orWhere('LOWER(g.nom) LIKE LOWER(:q)')
            ->orWhere('LOWER(alb.nom) LIKE LOWER(:q)')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Filtre les chansons par titre, artiste, album et genre
     */
    public function filterByCriteria(?string $title, ?int $artisteId, ?int $albumId, ?int $genreId): array
    {
        $qb = $this->createQueryBuilder('s');

        if ($title) {
            $qb->andWhere('LOWER(s.title) LIKE LOWER(:title) OR LOWER(s.filename) LIKE LOWER(:title)')
               ->setParameter('title', '%' . $title . '%');
        }

        if ($artisteId) {
            $qb->join('s.artistes', 'art')
               ->andWhere('art.id = :artisteId')
               ->setParameter('artisteId', $artisteId);
        }

        if ($albumId) {
            $qb->join('s.albums', 'alb')
               ->andWhere('alb.id = :albumId')
               ->setParameter('albumId', $albumId);
        }

        if ($genreId) {
            $qb->join('s.genres', 'g')
               ->andWhere('g.id = :genreId')
               ->setParameter('genreId', $genreId);
        }

        return $qb->orderBy('s.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}
