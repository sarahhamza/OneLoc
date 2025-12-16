<?php
// src/Repository/PropertyRepository.php

namespace App\Repository;

use App\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PropertyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Property::class);
    }

    /**
     * Find available properties with optional search
     */
    public function findAvailableWithSearch(?string $search = null)
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.available = :available')
            ->setParameter('available', true)
            ->orderBy('p.id', 'DESC');

        if ($search) {
            $qb->andWhere('p.title LIKE :search OR p.address LIKE :search')
               ->setParameter('search', "%$search%");
        }

        return $qb->getQuery();
    }

    /**
     * Find latest properties (limit)
     */
    public function findLatest(int $limit = 6): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.available = :available')
            ->setParameter('available', true)
            ->orderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find by price ASC
     */
    public function findByPriceAsc()
    {
        return $this->createQueryBuilder('p')
            ->where('p.available = :available')
            ->setParameter('available', true)
            ->orderBy('p.price', 'ASC')
            ->getQuery();
    }

    /**
     * Find by price DESC
     */
    public function findByPriceDesc()
    {
        return $this->createQueryBuilder('p')
            ->where('p.available = :available')
            ->setParameter('available', true)
            ->orderBy('p.price', 'DESC')
            ->getQuery();
    }
}