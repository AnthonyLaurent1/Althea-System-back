<?php

namespace App\Repository;

use App\Entity\Orders;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Orders>
 */
class OrdersRepository extends ServiceEntityRepository
{
    private const SORTABLE_FIELDS = ['id', 'paymentDate', 'totalPrice', 'status'];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Orders::class);
    }

    /**
     * Liste paginée des commandes pour le backoffice, filtrable et triable.
     *
     * @return Paginator<Orders>
     */
    public function paginateAdminList(
        ?string $status,
        ?int $userId,
        ?string $from,
        ?string $to,
        string $sortBy,
        string $sortDirection,
        int $page,
        int $limit,
    ): Paginator {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.user', 'u')
            ->addSelect('u');

        if ($status !== null && $status !== '') {
            $qb->andWhere('o.status = :status')->setParameter('status', $status);
        }

        if ($userId !== null) {
            $qb->andWhere('u.id = :userId')->setParameter('userId', $userId);
        }

        if ($from !== null && $from !== '') {
            $qb->andWhere('o.paymentDate >= :from')->setParameter('from', new \DateTimeImmutable($from));
        }

        if ($to !== null && $to !== '') {
            $qb->andWhere('o.paymentDate <= :to')->setParameter('to', new \DateTimeImmutable($to));
        }

        $field = in_array($sortBy, self::SORTABLE_FIELDS, true) ? $sortBy : 'id';
        $direction = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';
        $qb->orderBy('o.'.$field, $direction);

        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return new Paginator($qb->getQuery(), fetchJoinCollection: true);
    }

    /**
     * Histogramme du chiffre d'affaires des commandes payées.
     *
     * @return array<int, array{period_label: string, total_revenue: float, orders_count: int}>
     */
    public function fetchSalesHistogram(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate, string $granularity): array
    {
        $format = $granularity === 'week' ? '%x-W%v' : '%Y-%m-%d';

        $sql = <<<SQL
            SELECT DATE_FORMAT(o.payment_date, :format) AS period_label,
                   SUM(o.total_price) AS total_revenue,
                   COUNT(o.id) AS orders_count
            FROM orders o
            WHERE o.status = 'Payé'
              AND o.payment_date BETWEEN :start AND :end
            GROUP BY period_label
            ORDER BY period_label ASC
        SQL;

        return $this->getEntityManager()->getConnection()->fetchAllAssociative($sql, [
            'format' => $format,
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d'),
        ]);
    }

    /**
     * Chiffre d'affaires par catégorie et par période (pour histogramme multi-couches).
     *
     * @return array<int, array{category_id: int, category_title: string, period_label: string, revenue: float}>
     */
    public function fetchSalesByCategorySeries(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate, string $granularity): array
    {
        $format = $granularity === 'week' ? '%x-W%v' : '%Y-%m-%d';

        $sql = <<<SQL
            SELECT c.id AS category_id,
                   c.title AS category_title,
                   DATE_FORMAT(o.payment_date, :format) AS period_label,
                   SUM(i.price * i.quantity) AS revenue
            FROM orders o
            INNER JOIN items i ON i.orders_id = o.id
            INNER JOIN product p ON p.id = i.product_id
            INNER JOIN category c ON c.id = p.category_id
            WHERE o.status = 'Payé'
              AND o.payment_date BETWEEN :start AND :end
            GROUP BY c.id, c.title, period_label
            ORDER BY c.title ASC, period_label ASC
        SQL;

        return $this->getEntityManager()->getConnection()->fetchAllAssociative($sql, [
            'format' => $format,
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d'),
        ]);
    }

    /**
     * Répartition du chiffre d'affaires par catégorie (camembert).
     *
     * @return array<int, array{category_id: int, category_title: string, revenue: float}>
     */
    public function fetchCategoryShare(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $sql = <<<SQL
            SELECT c.id AS category_id,
                   c.title AS category_title,
                   SUM(i.price * i.quantity) AS revenue
            FROM orders o
            INNER JOIN items i ON i.orders_id = o.id
            INNER JOIN product p ON p.id = i.product_id
            INNER JOIN category c ON c.id = p.category_id
            WHERE o.status = 'Payé'
              AND o.payment_date BETWEEN :start AND :end
            GROUP BY c.id, c.title
            ORDER BY revenue DESC
        SQL;

        return $this->getEntityManager()->getConnection()->fetchAllAssociative($sql, [
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d'),
        ]);
    }
}
