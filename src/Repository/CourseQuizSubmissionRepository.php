<?php

namespace App\Repository;

use App\Entity\CourseQuiz;
use App\Entity\CourseQuizSubmission;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CourseQuizSubmission>
 */
class CourseQuizSubmissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseQuizSubmission::class);
    }

    public function findOneByQuizAndStudent(CourseQuiz $quiz, User $student): ?CourseQuizSubmission
    {
        return $this->findOneBy([
            'quiz' => $quiz,
            'student' => $student,
        ]);
    }

    /**
     * @return CourseQuizSubmission[]
     */
    public function findByQuizWithStudents(CourseQuiz $quiz): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.student', 'student')
            ->addSelect('student')
            ->andWhere('s.quiz = :quiz')
            ->setParameter('quiz', $quiz)
            ->orderBy('s.submitted_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return CourseQuizSubmission[]
     */
    public function findAllByTeacher(User $teacher): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.student', 'student')
            ->addSelect('student')
            ->leftJoin('s.quiz', 'quiz')
            ->addSelect('quiz')
            ->leftJoin('quiz.course', 'course')
            ->addSelect('course')
            ->leftJoin('quiz.teacher', 'teacher')
            ->andWhere('teacher = :teacher')
            ->setParameter('teacher', $teacher)
            ->orderBy('s.submitted_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
