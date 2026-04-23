<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/notifications')]
class AdminNotificationController extends AbstractController
{
    #[Route('', name: 'app_admin_notifications', methods: ['GET'])]
    public function index(AdminUrlGenerator $adminUrlGenerator): Response
    {
        $url = $adminUrlGenerator
            ->unsetAll()
            ->setController(NotificationCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    #[Route('/read-all', name: 'app_admin_notifications_read_all', methods: ['POST'])]
    public function readAll(
        Request $request,
        NotificationRepository $notificationRepository,
        EntityManagerInterface $entityManager,
        AdminUrlGenerator $adminUrlGenerator,
    ): RedirectResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('admin_notifications_read_all', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token.');

            return $this->redirectToRoute('app_admin_notifications');
        }

        $updated = $notificationRepository->markAllAsRead($user);
        if ($updated > 0) {
            $entityManager->flush();
        }

        $this->addFlash('success', sprintf('%d notification(s) marked as read.', $updated));

        $url = $adminUrlGenerator
            ->unsetAll()
            ->setController(NotificationCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    #[Route('/{id}/read', name: 'app_admin_notification_read', methods: ['GET', 'POST'])]
    public function readOne(
        int $id,
        Request $request,
        NotificationRepository $notificationRepository,
        EntityManagerInterface $entityManager,
        AdminUrlGenerator $adminUrlGenerator,
    ): RedirectResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST') && !$this->isCsrfTokenValid('admin_notification_read_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token.');

            $url = $adminUrlGenerator
                ->unsetAll()
                ->setController(NotificationCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl();

            return $this->redirect($url);
        }

        $notification = $notificationRepository->findOneBy([
            'id' => $id,
            'user' => $user,
        ]);

        if ($notification instanceof Notification && !$notification->isRead()) {
            $notification->setIsRead(true);
            $entityManager->flush();
        }

        $url = $adminUrlGenerator
            ->unsetAll()
            ->setController(NotificationCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }
}
