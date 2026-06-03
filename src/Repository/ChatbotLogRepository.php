<?php

namespace App\Repository;

use App\Entity\ChatbotLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChatbotLog>
 */
class ChatbotLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatbotLog::class);
    }

    /**
     * @return Paginator<ChatbotLog>
     */
    public function paginate(?string $sessionId, ?bool $escalatedOnly, int $page, int $limit): Paginator
    {
        $qb = $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC');

        if ($sessionId !== null && $sessionId !== '') {
            $qb->andWhere('l.sessionId = :sessionId')
                ->setParameter('sessionId', $sessionId);
        }

        if ($escalatedOnly === true) {
            $qb->andWhere('l.escalated = true');
        }

        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return new Paginator($qb->getQuery());
    }

    /**
     * @return ChatbotLog[]
     */
    public function findBySession(string $sessionId): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.sessionId = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('l.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
