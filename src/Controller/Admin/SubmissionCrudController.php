<?php

namespace App\Controller\Admin;

use App\Entity\Submission;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SubmissionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Submission::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Submission')
            ->setEntityLabelInPlural('Submissions')
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
        yield AssociationField::new('assignment')->setLabel('Assignment')->hideOnForm();
        yield AssociationField::new('user')->setLabel('Student')->hideOnForm();
        yield TextareaField::new('submissionText')->setLabel('Submission text')->hideOnIndex();
        yield DateTimeField::new('submittedAt')->setLabel('Submitted at');
        yield BooleanField::new('isLate')->setLabel('Late');
        yield TextareaField::new('feedback')->setLabel('Feedback')->hideOnIndex();
        yield TextField::new('status')->setLabel('Status');
        yield NumberField::new('grade')->setLabel('Grade');
        yield DateTimeField::new('createdAt')->setLabel('Created at')->hideOnForm();
        yield DateTimeField::new('updatedAt')->setLabel('Updated at')->hideOnForm();
    }
}
