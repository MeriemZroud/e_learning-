<?php

namespace App\Controller\Admin;

use App\Entity\ForumReview;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ForumReviewCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ForumReview::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Forum rating')
            ->setEntityLabelInPlural('Forum ratings')
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('forumPost')->setLabel('Post')->hideOnForm();
        yield AssociationField::new('user')->setLabel('Student')->hideOnForm();
        yield IntegerField::new('rating')->setLabel('Rating');
        yield TextareaField::new('reviewText')->setLabel('Review text')->hideOnIndex();
        yield DateTimeField::new('createdAt')->setLabel('Created at')->hideOnForm();
        yield TextField::new('syncUuid')->setLabel('Sync UUID')->hideOnIndex()->hideOnForm();
    }
}
