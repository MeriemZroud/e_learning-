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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

#[AdminDashboard(routePath: '/admin', routeName: 'app_admin_dashboard')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly EntityManagerInterface $entityManager,
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
            'unreadNotifications' => $unreadNotifications,
            'stats' => [
                'users' => $this->entityManager->getRepository(User::class)->count([]),
                'courses' => $this->entityManager->getRepository(Course::class)->count([]),
                'submissions' => $this->entityManager->getRepository(CourseQuizSubmission::class)->count([]),
                'forumPosts' => $this->entityManager->getRepository(ForumPost::class)->count([]),
                'forumComments' => $this->entityManager->getRepository(ForumComment::class)->count([]),
                'forumRatings' => $this->entityManager->getRepository(ForumReview::class)->count([]),
                'reclamations' => $this->entityManager->getRepository(Reclamation::class)->count([]),
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
