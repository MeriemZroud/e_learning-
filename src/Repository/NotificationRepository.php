<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * @return Notification[]
     */
    public function findUnreadByUser(User $user): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->andWhere('n.is_read = :isRead')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->orderBy('n.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countUnreadByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.user = :user')
            ->andWhere('n.is_read = :isRead')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Notification[]
     */
    public function findByUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.created_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function markAllAsRead(User $user): int
    {
        return $this->createQueryBuilder('n')
            ->update()
            ->set('n.is_read', ':isRead')
            ->where('n.user = :user')
            ->andWhere('n.is_read = :currentReadState')
            ->setParameter('isRead', true)
            ->setParameter('currentReadState', false)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}