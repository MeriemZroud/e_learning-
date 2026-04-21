<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Course>
 */
class CourseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    /**
     * @return Course[]
     */
    public function findByTeacher(User $teacher): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.teacher = :teacher')
            ->setParameter('teacher', $teacher)
            ->orderBy('c.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
