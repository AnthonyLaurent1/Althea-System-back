<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
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

    /**
     * Liste paginée des produits pour le backoffice, filtrable et triable.
     *
     * @return Paginator<Product>
     */
    public function paginateAdminList(
        ?string $search,
        ?int $categoryId,
        ?bool $isPublished,
        string $sortBy,
        string $sortDirection,
        int $page,
        int $limit,
    ): Paginator {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c');

        if ($search !== null && $search !== '') {
            $qb->andWhere('p.title LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($categoryId !== null) {
            $qb->andWhere('c.id = :categoryId')
               ->setParameter('categoryId', $categoryId);
        }

        if ($isPublished !== null) {
            $qb->andWhere('p.isPublished = :isPublished')
               ->setParameter('isPublished', $isPublished);
        }

        $sortableFields = ['id', 'title', 'price', 'inStock', 'isPublished'];
        $field = in_array($sortBy, $sortableFields, true) ? $sortBy : 'id';
        $direction = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';
        $qb->orderBy('p.' . $field, $direction);

        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return new Paginator($qb->getQuery(), fetchJoinCollection: true);
    }
}
