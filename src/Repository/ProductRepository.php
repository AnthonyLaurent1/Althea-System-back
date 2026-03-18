<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function searchByTitle(string $searchTerm): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.title LIKE :term')
            ->setParameter('term', '%' . $searchTerm . '%')
            ->orderBy('p.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
