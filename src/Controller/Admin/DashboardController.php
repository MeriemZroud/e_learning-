<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Course;
use App\Entity\Reclamation;
use App\Entity\CourseQuizSubmission;
use App\Entity\ForumPost;
use App\Entity\ForumComment;
use App\Entity\ForumReview;
use App\Entity\Reclamation as ReclamationEntity;
use App\Repository\NotificationRepository;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Nucleos\DompdfBundle\Wrapper\DompdfWrapperInterface;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[AdminDashboard(routePath: '/admin', routeName: 'app_admin_dashboard')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ReclamationRepository $reclamationRepository,
        private readonly DompdfWrapperInterface $dompdfWrapper,
    )
    {
    }

    public function index(): Response
    {
        /** @var AdminUrlGenerator $adminUrlGenerator */
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        $usersUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(UserCrudController::class)
            ->generateUrl();

        $coursesUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(CourseCrudController::class)
            ->generateUrl();

        $submissionsUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(CourseQuizSubmissionCrudController::class)
            ->generateUrl();

        $reclamationsUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(ReclamationCrudController::class)
            ->generateUrl();

        $notificationsUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(NotificationCrudController::class)
            ->generateUrl();

        $forumPostsUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(ForumPostCrudController::class)
            ->generateUrl();

        $forumCommentsUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(ForumCommentCrudController::class)
            ->generateUrl();

        $forumRatingsUrl = $adminUrlGenerator
            ->unsetAll()
            ->setController(ForumReviewCrudController::class)
            ->generateUrl();

        $aiRecommendations = $this->reclamationRepository->findTopPriorityRecommendations(5);

        $roleCountRows = $this->entityManager->createQueryBuilder()
            ->select('UPPER(COALESCE(r.role_category, r.name)) AS role_key, COUNT(u.id) AS total')
            ->from(User::class, 'u')
            ->leftJoin('u.role', 'r')
            ->groupBy('role_key')
            ->getQuery()
            ->getArrayResult();

        $adminCount = 0;
        $teacherCount = 0;
        $studentCount = 0;

        foreach ($roleCountRows as $row) {
            $roleKey = (string) ($row['role_key'] ?? '');
            $total = (int) ($row['total'] ?? 0);

            if (in_array($roleKey, ['ADMIN', 'ROLE_ADMIN'], true)) {
                $adminCount += $total;

                continue;
            }

            if (in_array($roleKey, ['TEACHER', 'ROLE_TEACHER'], true)) {
                $teacherCount += $total;

                continue;
            }

            if (in_array($roleKey, ['STUDENT', 'ROLE_STUDENT'], true)) {
                $studentCount += $total;
            }
        }

        $monthlyUserRows = $this->entityManager->getConnection()->fetchAllAssociative(
            'SELECT DATE_FORMAT(created_at, "%Y-%m") AS month_key, COUNT(id) AS total
             FROM users
             WHERE created_at IS NOT NULL AND created_at >= :since
             GROUP BY month_key
             ORDER BY month_key ASC',
            [
                'since' => new \DateTimeImmutable('-6 months'),
            ],
            [
                'since' => Types::DATETIME_IMMUTABLE,
            ]
        );

        $monthlyUserCounts = [];
        foreach ($monthlyUserRows as $row) {
            $monthlyUserCounts[(string) ($row['month_key'] ?? '')] = (int) ($row['total'] ?? 0);
        }

        $monthlyLabels = [];
        $historicalTotals = [];
        $currentTotalUsers = (int) $this->entityManager->getRepository(User::class)->count([]);

        $startMonth = new \DateTimeImmutable('first day of -5 months');
        for ($i = 0; $i < 6; $i++) {
            $month = $startMonth->modify(sprintf('+%d months', $i));
            $monthKey = $month->format('Y-m');
            $monthlyLabels[] = $month->format('M Y');
            $historicalTotals[] = (int) ($monthlyUserCounts[$monthKey] ?? 0);
        }

        $trendSamples = array_values(array_filter(array_slice($historicalTotals, -4), static fn (int $value): bool => $value > 0));
        $averageMonthlyGrowth = !empty($trendSamples)
            ? (int) round(array_sum($trendSamples) / count($trendSamples))
            : 1;
        $averageMonthlyGrowth = max(1, $averageMonthlyGrowth);

        $predictedTotals = [];
        $projectedTotal = $currentTotalUsers;
        for ($monthIndex = 1; $monthIndex <= 6; $monthIndex++) {
            $projectedTotal += $averageMonthlyGrowth;
            $predictedTotals[] = $projectedTotal;
        }

        $predictionLabels = [];
        $predictionCurrentMonth = new \DateTimeImmutable('first day of this month');
        for ($monthIndex = 1; $monthIndex <= 6; $monthIndex++) {
            $predictionLabels[] = $predictionCurrentMonth
                ->modify(sprintf('+%d months', $monthIndex))
                ->format('M Y');
        }

        $currentUser = $this->getUser();
        $adminName = 'Admin';
        $unreadNotifications = [];

        if ($currentUser instanceof User) {
            $adminName = trim(sprintf('%s %s', (string) $currentUser->getFirstName(), (string) $currentUser->getLastName()));
            if ($adminName === '') {
                $adminName = (string) $currentUser->getEmail();
            }

            $unreadNotifications = $this->notificationRepository->findUnreadByUser($currentUser);
        }

        return $this->render('admin/dashboard.html.twig', [
            'admin_name' => $adminName,
            'usersUrl' => $usersUrl,
            'coursesUrl' => $coursesUrl,
            'submissionsUrl' => $submissionsUrl,
            'reclamationsUrl' => $reclamationsUrl,
            'notificationsUrl' => $notificationsUrl,
            'forumPostsUrl' => $forumPostsUrl,
            'forumCommentsUrl' => $forumCommentsUrl,
            'forumRatingsUrl' => $forumRatingsUrl,
            'profileUrl' => $this->generateUrl('app_admin_profile'),
            'aiReportDownloadUrl' => $this->generateUrl('app_admin_ai_report_download'),
            'aiRecommendations' => $aiRecommendations,
            'userRoleChart' => [
                'admins' => $adminCount,
                'teachers' => $teacherCount,
                'students' => $studentCount,
            ],
            'userGrowthPrediction' => [
                'labels' => $predictionLabels,
                'historicalLabels' => $monthlyLabels,
                'historicalTotals' => $historicalTotals,
                'predictedTotals' => $predictedTotals,
                'currentTotal' => $currentTotalUsers,
                'averageMonthlyGrowth' => $averageMonthlyGrowth,
                'projectedTotalIn6Months' => $projectedTotal,
            ],
            'unreadNotifications' => $unreadNotifications,
            'stats' => [
                'users' => $this->entityManager->getRepository(User::class)->count([]),
                'courses' => $this->entityManager->getRepository(Course::class)->count([]),
                'submissions' => $this->entityManager->getRepository(CourseQuizSubmission::class)->count([]),
                'forumPosts' => $this->entityManager->getRepository(ForumPost::class)->count([]),
                'forumComments' => $this->entityManager->getRepository(ForumComment::class)->count([]),
                'forumRatings' => $this->entityManager->getRepository(ForumReview::class)->count([]),
                'reclamations' => $this->entityManager->getRepository(Reclamation::class)->count([]),
                'urgentReclamations' => $this->entityManager->getRepository(Reclamation::class)->count(['priority' => Reclamation::PRIORITY_URGENT]),
                'unreadNotifications' => count($unreadNotifications),
            ],
        ]);
    }

    #[Route('/admin/ai-report/download', name: 'app_admin_ai_report_download', methods: ['GET'])]
    public function downloadAiReport(): Response
    {
        $generatedAt = new \DateTimeImmutable();

        $stats = [
            'users' => (int) $this->entityManager->getRepository(User::class)->count([]),
            'courses' => (int) $this->entityManager->getRepository(Course::class)->count([]),
            'submissions' => (int) $this->entityManager->getRepository(CourseQuizSubmission::class)->count([]),
            'forumPosts' => (int) $this->entityManager->getRepository(ForumPost::class)->count([]),
            'forumComments' => (int) $this->entityManager->getRepository(ForumComment::class)->count([]),
            'forumRatings' => (int) $this->entityManager->getRepository(ForumReview::class)->count([]),
            'reclamations' => (int) $this->entityManager->getRepository(Reclamation::class)->count([]),
            'urgentReclamations' => (int) $this->entityManager->getRepository(Reclamation::class)->count(['priority' => Reclamation::PRIORITY_URGENT]),
        ];

        $topReclamations = $this->reclamationRepository->findTopPriorityRecommendations(5);
        $aiSummary = $this->generateAdminReportSummary($stats, $topReclamations);

        $html = $this->renderView('admin/ai_report.pdf.twig', [
            'generatedAt' => $generatedAt,
            'stats' => $stats,
            'topReclamations' => $topReclamations,
            'aiSummary' => $aiSummary,
        ]);

        $pdfContent = $this->dompdfWrapper->getPdf($html, [
            'defaultFont' => 'DejaVu Sans',
        ]);

        $filename = sprintf('admin-ai-report-%s.pdf', $generatedAt->format('Y-m-d-His'));
        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));

        return $response;
    }

    /**
     * @param array<string,int> $stats
     * @param array<int,array<string,mixed>> $topReclamations
     */
    private function generateAdminReportSummary(array $stats, array $topReclamations): string
    {
        $apiKey = trim((string) ($_ENV['HUGGING_FACE_API_KEY'] ?? ''));
        $model = trim((string) ($_ENV['HUGGING_FACE_MODEL_ADMIN_REPORT'] ?? ($_ENV['HUGGING_FACE_MODEL'] ?? 'Qwen/Qwen2.5-7B-Instruct')));

        if ($apiKey === '' || $model === '') {
            return $this->buildFallbackAdminReportSummary($stats);
        }

        $criticalMessages = array_map(
            static function (mixed $item): string {
                if ($item instanceof ReclamationEntity) {
                    $priorityLabel = match ($item->getPriority()) {
                        ReclamationEntity::PRIORITY_URGENT => 'Urgent',
                        ReclamationEntity::PRIORITY_NORMAL => 'Normal',
                        default => 'Low',
                    };

                    return sprintf(
                        '#%s (%s/%s): %s',
                        (string) ($item->getId() ?? '-'),
                        $priorityLabel,
                        (string) ((int) ($item->getPriorityScore() ?? 0)),
                        mb_substr(trim(strip_tags((string) $item->getMessage())), 0, 180)
                    );
                }

                if (is_array($item)) {
                    return sprintf(
                        '#%s (%s/%s): %s',
                        (string) ($item['id'] ?? '-'),
                        (string) ($item['priorityLabel'] ?? 'N/A'),
                        (string) ($item['priorityScore'] ?? '0'),
                        mb_substr(trim(strip_tags((string) ($item['message'] ?? ''))), 0, 180)
                    );
                }

                return 'Reclamation data unavailable';
            },
            $topReclamations
        );

        $prompt = sprintf(
            "R\u00e9dige un mini rapport ex\u00e9cutif en fran\u00e7ais (max 180 mots) pour un dashboard e-learning. " .
            "Format demand\u00e9: 1 paragraphe synth\u00e8se + 3 recommandations d'action num\u00e9rot\u00e9es. " .
            "Donn\u00e9es: Utilisateurs=%d, Cours=%d, Soumissions=%d, R\u00e9clamations=%d, R\u00e9clamations urgentes=%d. " .
            "Top r\u00e9clamations: %s",
            (int) ($stats['users'] ?? 0),
            (int) ($stats['courses'] ?? 0),
            (int) ($stats['submissions'] ?? 0),
            (int) ($stats['reclamations'] ?? 0),
            (int) ($stats['urgentReclamations'] ?? 0),
            !empty($criticalMessages) ? implode(' | ', $criticalMessages) : 'Aucune'
        );

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
                            'content' => 'Tu es un assistant analyste de performance pour un LMS. R\u00e9ponse concise en fran\u00e7ais.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 320,
                ],
                'timeout' => 30,
            ]);

            $payload = json_decode($response->getContent(false), true);
            $content = trim((string) ($payload['choices'][0]['message']['content'] ?? ''));
            if ($content === '') {
                return $this->buildFallbackAdminReportSummary($stats);
            }

            return mb_substr(str_replace(["```markdown", '```'], '', $content), 0, 1800);
        } catch (\Throwable) {
            return $this->buildFallbackAdminReportSummary($stats);
        }
    }

    /**
     * @param array<string,int> $stats
     */
    private function buildFallbackAdminReportSummary(array $stats): string
    {
        $users = (int) ($stats['users'] ?? 0);
        $courses = (int) ($stats['courses'] ?? 0);
        $submissions = (int) ($stats['submissions'] ?? 0);
        $reclamations = (int) ($stats['reclamations'] ?? 0);
        $urgent = (int) ($stats['urgentReclamations'] ?? 0);

        return sprintf(
            "Synth\u00e8se: la plateforme compte %d utilisateurs actifs pour %d cours et %d soumissions enregistr\u00e9es. " .
            "Le volume de r\u00e9clamations est de %d dont %d urgentes, ce qui n\u00e9cessite un suivi prioritaire des tickets critiques.\n\n" .
            "1. Traiter les r\u00e9clamations urgentes en moins de 24h avec un suivi quotidien.\n" .
            "2. Analyser les cours qui g\u00e9n\u00e8rent le plus de r\u00e9clamations pour corriger les causes racines.\n" .
            "3. Mettre en place un reporting hebdomadaire automatique pour suivre la qualit\u00e9 de service.",
            $users,
            $courses,
            $submissions,
            $reclamations,
            $urgent
        );
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('LearnWay Admin');
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('styles/admin-theme.css')
            ->addCssFile('styles/language-selector.css')
            ->addJsFile('js/language-selector.js')
            ->addJsFile('js/admin-navigation.js');
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        $userMenu = parent::configureUserMenu($user);

        if ($user instanceof User) {
            $profileImage = $user->getProfileImage();
            if ($profileImage) {
                $userMenu->setAvatarUrl('/uploads/profile/' . ltrim($profileImage, '/'));
            }

            return $userMenu
                ->setName($user->__toString())
                ->displayUserAvatar(true)
                ->displayUserName(true);
        }

        return $userMenu;
    }

    public function configureMenuItems(): iterable
    {
        /** @var AdminUrlGenerator $adminUrlGenerator */
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Users', 'fa fa-users', \App\Entity\User::class);
        yield MenuItem::linkToCrud('Courses', 'fa fa-book', \App\Entity\Course::class);
        yield MenuItem::linkToCrud('Submissions', 'fa fa-file-text-o', \App\Entity\CourseQuizSubmission::class);
        yield MenuItem::linkToCrud('Forum posts', 'fa fa-comments-o', \App\Entity\ForumPost::class);
        yield MenuItem::linkToCrud('Forum comments', 'fa fa-commenting-o', \App\Entity\ForumComment::class);
        yield MenuItem::linkToCrud('Forum ratings', 'fa fa-star', \App\Entity\ForumReview::class);

        $currentUser = $this->getUser();
        if ($currentUser instanceof User && null !== $currentUser->getId()) {
            $profileUrl = $adminUrlGenerator
                ->unsetAll()
                ->setController(UserCrudController::class)
                ->setAction(Action::EDIT)
                ->setEntityId((string) $currentUser->getId())
                ->generateUrl();

            $unreadCount = $this->notificationRepository->countUnreadByUser($currentUser);
            $notificationLabel = $unreadCount > 0
                ? sprintf('Notifications (%d)', $unreadCount)
                : 'Notifications';

            yield MenuItem::linkToUrl('Profile', 'fa fa-id-badge', $profileUrl);
            yield MenuItem::linkToCrud($notificationLabel, 'fa fa-bell', \App\Entity\Notification::class);
            yield MenuItem::linkToCrud('Reclamations', 'fa fa-exclamation-circle', \App\Entity\Reclamation::class);
        }
    }
}
