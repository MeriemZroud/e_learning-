<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'app_admin_dashboard', methods: ['GET'])]
    public function admin(): Response
    {
        return $this->render('dashboard/space.html.twig', [
            'title' => 'Admin space',
            'role_label' => 'Administrator',
            'description' => 'Manage users, roles, reports, and platform settings from this space.',
        ]);
    }

    #[Route('/teacher', name: 'app_teacher_dashboard', methods: ['GET'])]
    public function teacher(): Response
    {
        return $this->redirectToRoute('app_teacher_course_index');
    }
}