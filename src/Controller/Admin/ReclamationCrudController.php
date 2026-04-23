<?php

namespace App\Controller\Admin;

use App\Entity\Notification;
use App\Entity\Reclamation;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class ReclamationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Reclamation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Reclamation')
            ->setEntityLabelInPlural('Reclamations')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('user')->setLabel('User')->hideOnForm();
        yield TextareaField::new('message')->setLabel('Message')->setFormTypeOption('disabled', true);
        yield DateTimeField::new('createdAt')->setLabel('Created at')->hideOnForm();

        yield ChoiceField::new('status')
            ->setLabel('Status')
            ->setChoices([
                'In Progress' => Reclamation::STATUS_IN_PROGRESS,
                'Resolved' => Reclamation::STATUS_RESOLVED,
                'Rejected' => Reclamation::STATUS_REJECTED,
            ])
            ->renderExpanded(false)
            ->renderAsNativeWidget();
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Reclamation) {
            parent::updateEntity($entityManager, $entityInstance);

            return;
        }

        $originalData = $entityManager->getUnitOfWork()->getOriginalEntityData($entityInstance);
        $previousStatus = $originalData['status'] ?? $entityInstance->getStatus();

        parent::updateEntity($entityManager, $entityInstance);

        $newStatus = $entityInstance->getStatus();
        if ($previousStatus === $newStatus || !$entityInstance->getUser()) {
            return;
        }

        $notification = new Notification();
        $notification->setUser($entityInstance->getUser());
        $notification->setMessage(sprintf(
            'Your reclamation #%d status changed to %s.',
            $entityInstance->getId(),
            ucfirst(strtolower(str_replace('_', ' ', (string) $newStatus)))
        ));
        $notification->setType('status');
        $notification->setIsRead(false);

        $entityManager->persist($notification);
        $entityManager->flush();
    }
}
