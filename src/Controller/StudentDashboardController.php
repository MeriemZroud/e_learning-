<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\User;
use App\Repository\CourseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StudentDashboardController extends AbstractController
{
    #[Route('/student', name: 'app_student_dashboard', methods: ['GET'])]
    public function index(CourseRepository $courseRepository): Response
    {
        $studentName = $this->getStudentName();
        $featuredCourse = $courseRepository->findOneBy(['is_published' => true], ['created_at' => 'DESC']);

        return $this->render('dashboard/student_home.html.twig', [
            'student_name' => $studentName,
            'featured_course' => $featuredCourse instanceof Course ? $featuredCourse : null,
        ]);
    }

    #[Route('/student/about', name: 'app_student_about', methods: ['GET'])]
    public function about(): Response
    {
        return $this->render('dashboard/student_about.html.twig', [
            'student_name' => $this->getStudentName(),
        ]);
    }

    private function getStudentName(): string
    {
        $user = $this->getUser();
        $studentName = 'Student';

        if ($user instanceof User) {
            $studentName = trim(sprintf('%s %s', (string) $user->getFirstName(), (string) $user->getLastName()));

            if ($studentName === '') {
                $studentName = (string) $user->getEmail();
            }
        }

        return $studentName;
    }
}
