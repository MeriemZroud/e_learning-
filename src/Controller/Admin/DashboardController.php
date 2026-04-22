<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'app_admin_dashboard')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        /** @var AdminUrlGenerator $adminUrlGenerator */
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        $url = $adminUrlGenerator
            ->unsetAll()
            ->setController(UserCrudController::class)
            ->generateUrl();

        return $this->redirect($url);
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

    public function configureMenuItems(): iterable
    {
        /** @var AdminUrlGenerator $adminUrlGenerator */
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Users', 'fa fa-users', \App\Entity\User::class);

        $currentUser = $this->getUser();
        if ($currentUser instanceof \App\Entity\User && null !== $currentUser->getId()) {
            $profileUrl = $adminUrlGenerator
                ->unsetAll()
                ->setController(UserCrudController::class)
                ->setAction(Action::EDIT)
                ->setEntityId((string) $currentUser->getId())
                ->generateUrl();

            yield MenuItem::linkToUrl('Profile', 'fa fa-id-badge', $profileUrl);
        }
    }
}
