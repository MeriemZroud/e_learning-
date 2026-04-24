<?php

namespace App\Controller\Admin;

use App\Entity\CourseQuizSubmission;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class CourseQuizSubmissionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CourseQuizSubmission::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Quiz submission')
            ->setEntityLabelInPlural('Quiz submissions')
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('quiz')->setLabel('Quiz');
        yield AssociationField::new('student')->setLabel('Student');
        yield NumberField::new('score')->setLabel('Score');
        yield DateTimeField::new('submittedAt')->setLabel('Submitted at');
        yield TextField::new('status')->setLabel('Status');
        yield TextareaField::new('answersJson')->setLabel('Answers JSON')->hideOnIndex();
    }
}
