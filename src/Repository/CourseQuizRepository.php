<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\CourseQuiz;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CourseQuiz>
 */
class CourseQuizRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseQuiz::class);
    }

    public function findLatestByCourseAndTeacher(Course $course, User $teacher): ?CourseQuiz
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.course = :course')
            ->andWhere('q.teacher = :teacher')
            ->setParameter('course', $course)
            ->setParameter('teacher', $teacher)
            ->orderBy('q.created_at', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return CourseQuiz[]
     */
    public function findPublishedForStudents(?string $courseSearch = null): array
    {
        $qb = $this->createQueryBuilder('q')
            ->innerJoin('q.course', 'c')
            ->andWhere('q.status = :status')
            ->setParameter('status', 'published')
            ->orderBy('q.created_at', 'DESC')
        ;

        $courseSearch = trim((string) $courseSearch);
        if ($courseSearch !== '') {
            $qb->andWhere('LOWER(c.title) LIKE :courseSearch')
                ->setParameter('courseSearch', '%' . mb_strtolower($courseSearch) . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return CourseQuiz[]
     */
    public function findPublishedByCourse(Course $course): array
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.status = :status')
            ->andWhere('q.course = :course')
            ->setParameter('status', 'published')
            ->setParameter('course', $course)
            ->orderBy('q.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
