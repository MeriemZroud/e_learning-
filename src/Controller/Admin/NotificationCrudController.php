<?php

namespace App\Controller\Admin;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use Symfony\Component\Security\Core\User\UserInterface;

class NotificationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Notification::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Notification')
            ->setEntityLabelInPlural('Notifications')
            ->setDefaultSort(['created_at' => 'DESC'])
            ->setSearchFields(['message', 'type'])
            ->setPaginatorPageSize(20);
    }

    public function configureActions(Actions $actions): Actions
    {
        $markAsRead = Action::new('markAsRead', 'Mark as read', 'fa fa-check')
            ->linkToRoute('app_admin_notification_read', static fn (Notification $notification): array => ['id' => $notification->getId()])
            ->displayIf(static fn (Notification $notification): bool => !$notification->isRead())
            ->addCssClass('btn btn-sm btn-primary');

        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, $markAsRead);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('is_read', 'Read'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('type')
            ->formatValue(static fn ($value): string => ucwords(str_replace('_', ' ', (string) $value)));
        yield TextField::new('message')->setMaxLength(120);
        yield BooleanField::new('is_read', 'Read');
        yield DateTimeField::new('created_at', 'Created at');
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $user = $this->getUser();
        if ($user instanceof UserInterface && $user instanceof User) {
            $alias = $queryBuilder->getRootAliases()[0] ?? 'entity';
            $queryBuilder
                ->andWhere(sprintf('%s.user = :currentUser', $alias))
                ->setParameter('currentUser', $user);
        }

        return $queryBuilder;
    }
}
