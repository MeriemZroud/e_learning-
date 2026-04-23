<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Reclamation;
use App\Entity\User;
use App\Form\ReclamationType;
use App\Repository\NotificationRepository;
use App\Repository\ReclamationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reclamations')]
class ReclamationController extends AbstractController
{
    #[Route('/', name: 'app_reclamation_index', methods: ['GET'])]
    public function index(ReclamationRepository $reclamationRepository, NotificationRepository $notificationRepository): Response
    {
        $user = $this->getUser();
        $reclamations = [];

        if ($this->isGranted('ROLE_ADMIN')) {
            $reclamations = $reclamationRepository->findAll();
        } else {
            $reclamations = $reclamationRepository->findBy(['user' => $user]);
        }

        // Fetch unread notifications for the user
        $unreadNotifications = $user instanceof User
            ? $notificationRepository->findUnreadByUser($user)
            : [];

        return $this->render('reclamation/index.html.twig', [
            'reclamations' => $reclamations,
            'unreadNotifications' => $unreadNotifications,
        ]);
    }

    #[Route('/new', name: 'app_reclamation_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_STUDENT')]
    public function new(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository, NotificationRepository $notificationRepository): Response
    {
        $user = $this->getUser();
        $reclamation = new Reclamation();
        $reclamation->setUser($user);
        $reclamation->setCreatedAt(new \DateTime());
        $reclamation->setStatus(Reclamation::STATUS_IN_PROGRESS);

        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reclamation);

            $currentUser = $this->getUser();
            $senderName = $currentUser instanceof User ? $currentUser->getFirstName() : 'A user';

            $admins = $userRepository->createQueryBuilder('u')
                ->join('u.role', 'r')
                ->andWhere('r.role_category = :admin OR r.name = :adminName')
                ->setParameter('admin', 'ADMIN')
                ->setParameter('adminName', 'ROLE_ADMIN')
                ->getQuery()
                ->getResult();

            foreach ($admins as $admin) {
                $notification = new Notification();
                $notification->setUser($admin);
                $notification->setMessage(sprintf('User %s submitted a new reclamation.', $senderName));
                $notification->setType('reclamation');
                $notification->setIsRead(false);
                $entityManager->persist($notification);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reclamation/new.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
            'unreadNotifications' => $user instanceof User
                ? $notificationRepository->findUnreadByUser($user)
                : [],
]);
    }

    #[Route('/{id}', name: 'app_reclamation_show', methods: ['GET'])]
    public function show(Reclamation $reclamation, NotificationRepository $notificationRepository): Response
    {
        // Check if user can view this reclamation
        if (!$this->isGranted('ROLE_ADMIN') && $reclamation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();

        return $this->render('reclamation/show.html.twig', [
            'reclamation' => $reclamation,
            'unreadNotifications' => $user instanceof User
                ? $notificationRepository->findUnreadByUser($user)
                : [],
]);
    }

    #[Route('/{id}/edit', name: 'app_reclamation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager, NotificationRepository $notificationRepository): Response
    {
        // Check if user can edit this reclamation
        if (!$this->isGranted('ROLE_ADMIN') && $reclamation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();

        // Handle direct status update from index page
        if ($request->request->has('status') && $this->isCsrfTokenValid('update_status', $request->getPayload()->getString('_token'))) {
            $status = strtoupper((string) $request->request->get('status'));
            $allowedStatuses = [
                Reclamation::STATUS_IN_PROGRESS,
                Reclamation::STATUS_RESOLVED,
                Reclamation::STATUS_REJECTED,
            ];

            if (in_array($status, $allowedStatuses, true) && $this->isGranted('ROLE_ADMIN')) {
                $previousStatus = $reclamation->getStatus();
                if ($previousStatus !== $status) {
                    $reclamation->setStatus($status);

                    if ($reclamation->getUser() instanceof User) {
                        $adminNotification = new Notification();
                        $adminNotification->setUser($reclamation->getUser());
                        $adminNotification->setMessage(sprintf('Your reclamation #%d status changed to %s.', $reclamation->getId(), ucfirst(str_replace('_', ' ', $status))));
                        $adminNotification->setType('status');
                        $adminNotification->setIsRead(false);
                        $entityManager->persist($adminNotification);
                    }
                }

                $entityManager->flush();
                return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        // For admin, only allow status change
        if ($this->isGranted('ROLE_ADMIN')) {
            $form = $this->createForm(\App\Form\ReclamationStatusType::class, $reclamation);
        } else {
            $form = $this->createForm(\App\Form\ReclamationType::class, $reclamation);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reclamation/edit.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
            'unreadNotifications' => $user instanceof User
                ? $notificationRepository->findUnreadByUser($user)
                : [],
]);
    }

    #[Route('/{id}', name: 'app_reclamation_delete', methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        // Check if user can delete this reclamation
        if (!$this->isGranted('ROLE_ADMIN') && $reclamation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$reclamation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reclamation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
    }
}