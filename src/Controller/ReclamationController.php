<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Reclamation;
use App\Entity\User;
use App\Form\ReclamationType;
use App\Repository\NotificationRepository;
use App\Repository\ReclamationRepository;
use App\Repository\UserRepository;
use App\Service\NotifierEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpClient\HttpClient;
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
            $reclamations = $reclamationRepository->findAllOrderedByPriority();
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
    public function new(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository, NotificationRepository $notificationRepository, NotifierEmailService $notifierEmailService): Response
    {
        $user = $this->getUser();
        $reclamation = new Reclamation();
        $reclamation->setUser($user);
        $reclamation->setCreatedAt(new \DateTime());
        $reclamation->setStatus(Reclamation::STATUS_IN_PROGRESS);

        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->applyPriorityAnalysis($reclamation);
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

                if ($admin instanceof User) {
                    $notifierEmailService->send(
                        $admin,
                        'New reclamation submitted',
                        sprintf('User %s submitted a new reclamation.', $senderName)
                    );
                }
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
    public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager, NotificationRepository $notificationRepository, NotifierEmailService $notifierEmailService): Response
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

                        $notifierEmailService->send(
                            $reclamation->getUser(),
                            sprintf('Reclamation #%d status updated', $reclamation->getId()),
                            sprintf('Your reclamation #%d status changed to %s.', $reclamation->getId(), ucfirst(str_replace('_', ' ', $status)))
                        );
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
            if (!$this->isGranted('ROLE_ADMIN')) {
                $this->applyPriorityAnalysis($reclamation);
            }

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

    #[Route('/{id}/analyze-ai', name: 'app_reclamation_analyze_ai', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function analyzeAi(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('analyze_ai_'.$reclamation->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid AI analysis token.');

            return $this->redirectToRoute('app_reclamation_index');
        }

        $this->applyPriorityAnalysis($reclamation);
        $entityManager->flush();

        $this->addFlash(
            'success',
            sprintf(
                'AI recommendation updated for reclamation #%d: %s (%d/100).',
                $reclamation->getId(),
                $reclamation->getPriorityLabel(),
                $reclamation->getPriorityScore()
            )
        );

        return $this->redirectToRoute('app_reclamation_index');
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

    private function applyPriorityAnalysis(Reclamation $reclamation): void
    {
        $analysis = $this->analyzePriority((string) $reclamation->getMessage());

        $reclamation->setPriorityScore((int) ($analysis['score'] ?? 50));
        $reclamation->setPriority($this->priorityFromScore((int) ($analysis['score'] ?? 50)));
    }

    /**
     * @return array{score:int, source:string, reason?:string}
     */
    private function analyzePriority(string $message): array
    {
        $apiKey = trim((string) ($_ENV['HUGGING_FACE_API_KEY'] ?? ''));
        $model = trim((string) ($_ENV['HUGGING_FACE_MODEL_RECLAMATION'] ?? 'mistralai/Mistral-Nemo-Instruct-2407'));

        if ($apiKey === '' || $model === '') {
            return $this->heuristicPriority($message);
        }

        try {
            $client = HttpClient::create();
            $response = $client->request('POST', 'https://router.huggingface.co/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                ],
                'json' => [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a support ticket triage assistant for an e-learning platform. Return only JSON.',
                        ],
                        [
                            'role' => 'user',
                            'content' => sprintf(
                                "Analyse cette réclamation et donne un score de priorité entre 0 et 100.\n" .
                                "Retourne seulement ce JSON: {\"score\":85,\"reason\":\"...\"}.\n" .
                                "Règles: 75-100 = urgent, 40-74 = normal, 0-39 = faible.\n\nRéclamation: %s",
                                $message
                            ),
                        ],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 220,
                ],
                'timeout' => 30,
            ]);

            $payload = json_decode($response->getContent(false), true);
            $content = trim((string) ($payload['choices'][0]['message']['content'] ?? ''));
            $json = $this->extractJsonPayload($content);
            $data = json_decode($json, true);

            if (is_array($data) && isset($data['score'])) {
                $score = max(0, min(100, (int) $data['score']));

                return [
                    'score' => $score,
                    'source' => 'huggingface',
                    'reason' => (string) ($data['reason'] ?? ''),
                ];
            }
        } catch (\Throwable) {
        }

        return $this->heuristicPriority($message);
    }

    /**
     * @return array{score:int, source:string, reason:string}
     */
    private function heuristicPriority(string $message): array
    {
        $text = mb_strtolower($message);
        $score = 35;

        $urgentKeywords = [
            'urgent', 'bloqué', 'bloquer', 'mot de passe', 'password', 'accès', 'impossible',
            'erreur 500', 'crash', 'serveur', 'site down', 'compte fermé', 'compte bloqué', 'hack', 'piraté', 'paiement',
        ];
        foreach ($urgentKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $score += 10;
            }
        }

        $normalKeywords = ['problème', 'bug', 'aide', 'corriger', 'modifier', 'question', 'erreur', 'retard'];
        foreach ($normalKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $score += 4;
            }
        }

        $lowKeywords = ['suggestion', 'amélioration', 'idée', 'proposition', 'faible'];
        foreach ($lowKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $score -= 12;
            }
        }

        if (mb_strlen($text) > 180) {
            $score += 8;
        }

        if (str_contains($text, '!')) {
            $score += 3;
        }

        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'source' => 'heuristic',
            'reason' => 'Fallback local analysis.',
        ];
    }

    private function priorityFromScore(int $score): string
    {
        return match (true) {
            $score >= 75 => Reclamation::PRIORITY_URGENT,
            $score >= 40 => Reclamation::PRIORITY_NORMAL,
            default => Reclamation::PRIORITY_LOW,
        };
    }

    private function extractJsonPayload(string $content): string
    {
        $content = trim($content);

        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*/', '', $content) ?? $content;
            $content = preg_replace('/\s*```$/', '', $content) ?? $content;
        }

        $start = strpos($content, '{');
        $end = strrpos($content, '}');

        if ($start !== false && $end !== false && $end >= $start) {
            return substr($content, $start, $end - $start + 1);
        }

        return $content;
    }
}