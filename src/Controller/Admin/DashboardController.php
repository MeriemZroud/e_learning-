<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Course;
use App\Entity\Reclamation;
use App\Entity\CourseQuizSubmission;
use App\Entity\ForumPost;
use App\Entity\ForumComment;
use App\Entity\ForumReview;
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
use Doctrine\DBAL\Types\Types;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

#[AdminDashboard(routePath: '/admin', routeName: 'app_admin_dashboard')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ReclamationRepository $reclamationRepository,
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

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('LearnWay Admin');
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('styles/admin-theme.css')
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
