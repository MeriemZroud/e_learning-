<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/teacher', name: 'app_teacher_dashboard', methods: ['GET'])]
    public function teacher(): Response
    {
        return $this->redirectToRoute('app_teacher_course_index');
    }
}