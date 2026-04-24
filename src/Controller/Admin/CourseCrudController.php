<?php

namespace App\Controller\Admin;

use App\Entity\Course;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CourseCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Course::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Course')
            ->setEntityLabelInPlural('Courses')
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('teacher')->setLabel('Teacher');
        yield TextField::new('title')->setLabel('Title');
        yield TextareaField::new('description')->setLabel('Description')->hideOnIndex();
        yield TextField::new('videoUrl')->setLabel('Video URL');
        yield TextField::new('thumbnailUrl')->setLabel('Thumbnail URL')->hideOnIndex();
        yield TextField::new('pdfFile')->setLabel('PDF file')->hideOnForm();
        yield BooleanField::new('isPublished')->setLabel('Published');
        yield DateTimeField::new('createdAt')->setLabel('Created at')->hideOnForm();
        yield DateTimeField::new('updatedAt')->setLabel('Updated at')->hideOnForm();
    }
}
