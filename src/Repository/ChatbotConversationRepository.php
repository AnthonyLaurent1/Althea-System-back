<?php

namespace App\Repository;

use App\Entity\ChatbotConversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChatbotConversation>
 */
class ChatbotConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatbotConversation::class);
    }

    /**
     * @return Paginator<ChatbotConversation>
     */
    public function paginate(?string $status, int $page, int $limit): Paginator
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.updatedAt', 'DESC');

        if ($status !== null && $status !== '') {
            $qb->andWhere('c.status = :status')->setParameter('status', $status);
        }

        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return new Paginator($qb->getQuery());
    }
}
