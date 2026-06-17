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
            ->where('LOWER(s.filename) LIKE LOWER(:q)')
            ->orWhere('LOWER(s.title) LIKE LOWER(:q)')
            ->orWhere('LOWER(s.artist) LIKE LOWER(:q)')
            ->orWhere('LOWER(s.genre) LIKE LOWER(:q)')
            ->orWhere('LOWER(s.album) LIKE LOWER(:q)')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
