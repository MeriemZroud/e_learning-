<?php

namespace App\Controller\Admin;

use App\Entity\ForumComment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ForumCommentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ForumComment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Forum comment')
            ->setEntityLabelInPlural('Forum comments')
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
        yield AssociationField::new('forumComment')->setLabel('Parent comment')->hideOnForm();
        yield TextareaField::new('content')->setLabel('Content');
        yield TextField::new('emoji')->setLabel('Emoji')->hideOnIndex();
        yield DateTimeField::new('createdAt')->setLabel('Created at')->hideOnForm();
        yield TextField::new('syncUuid')->setLabel('Sync UUID')->hideOnIndex()->hideOnForm();
    }
}
