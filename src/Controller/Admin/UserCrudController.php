<?php

namespace App\Controller\Admin;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly MailerInterface $mailer,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('User')
            ->setEntityLabelInPlural('Users')
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(10);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(
                Crud::PAGE_EDIT,
                Action::new('backToUsers', 'Users')
                    ->linkToCrudAction(Action::INDEX)
            );
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield EmailField::new('email')
            ->setFormTypeOption('required', false);
        yield TextField::new('firstName', 'First name')
            ->setFormTypeOption('required', false);
        yield TextField::new('lastName', 'Last name')
            ->setFormTypeOption('required', false);
        yield TextField::new('roleLabel', 'Role')
            ->hideOnForm();
        yield AssociationField::new('role')
            ->setFormTypeOption('choice_label', 'name')
            ->setFormTypeOption('required', false)
            ->onlyOnForms();
        yield BooleanField::new('isActive', 'Active');
        yield TextField::new('createdAtString', 'Created at')
            ->hideOnForm();

        if (Crud::PAGE_EDIT === $pageName) {
            yield TextField::new('dateOfBirth', 'Date of birth')
                ->setFormType(DateType::class)
                ->setFormTypeOption('widget', 'single_text')
                ->setFormTypeOption('required', false)
                ->onlyOnForms();
            yield ImageField::new('profileImage', 'Profile image')
                ->setBasePath('uploads/profile')
                ->setUploadDir('public/uploads/profile')
                ->setUploadedFileNamePattern('user-[timestamp]-[randomhash].[extension]')
                ->setFormTypeOption('required', false)
                ->onlyOnForms();
        }

        yield TextField::new('plainPassword', 'Password')
            ->setFormType(PasswordType::class)
            ->setFormTypeOption('required', false)
            ->onlyOnForms();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            parent::persistEntity($entityManager, $entityInstance);

            return;
        }

        $passwordToSend = trim((string) $entityInstance->getPlainPassword());
        if ($passwordToSend === '') {
            $passwordToSend = $this->generateTemporaryPassword();
        }

        $entityInstance->setPasswordHash($this->passwordHasher->hashPassword($entityInstance, $passwordToSend));
        $entityInstance->setPlainPassword(null);

        if ($entityInstance->isActive() === null) {
            $entityInstance->setIsActive(true);
        }

        if ($entityInstance->getCreatedAt() === null) {
            $entityInstance->setCreatedAt(new \DateTime());
        }

        parent::persistEntity($entityManager, $entityInstance);

        try {
            $message = (new Email())
                ->from(new Address('meriemzroud7@gmail.com', 'LearnWay'))
                ->to((string) $entityInstance->getEmail())
                ->subject('Your LearnWay account credentials')
                ->html($this->renderView('emails/new_user_credentials.html.twig', [
                    'user' => $entityInstance,
                    'plain_password' => $passwordToSend,
                ]))
                ->text(sprintf(
                    "Hello %s %s,\n\nYour LearnWay account has been created.\nEmail: %s\nPassword: %s\n\nPlease login and change your password as soon as possible.",
                    (string) $entityInstance->getFirstName(),
                    (string) $entityInstance->getLastName(),
                    (string) $entityInstance->getEmail(),
                    $passwordToSend
                ));

            $this->mailer->send($message);
            $this->addFlash('success', 'User created and credentials email sent.');
        } catch (\Throwable) {
            $this->addFlash('warning', 'User created but credentials email could not be sent.');
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            parent::updateEntity($entityManager, $entityInstance);

            return;
        }

        $unitOfWork = $entityManager->getUnitOfWork();
        $unitOfWork->recomputeSingleEntityChangeSet($entityManager->getClassMetadata(User::class), $entityInstance);
        $rawChangeSet = $unitOfWork->getEntityChangeSet($entityInstance);
        $changes = $this->normalizeChangeSet($rawChangeSet);

        parent::updateEntity($entityManager, $entityInstance);

        if ($changes === []) {
            return;
        }

        try {
            $message = (new Email())
                ->from(new Address('meriemzroud7@gmail.com', 'LearnWay'))
                ->to((string) $entityInstance->getEmail())
                ->subject('Your LearnWay account was updated')
                ->html($this->renderView('emails/user_updated_notice.html.twig', [
                    'user' => $entityInstance,
                    'changes' => $changes,
                ]))
                ->text($this->buildUpdateTextBody($entityInstance, $changes));

            $this->mailer->send($message);
            $this->addFlash('success', 'User updated and notification email sent.');
        } catch (\Throwable) {
            $this->addFlash('warning', 'User updated but notification email could not be sent.');
        }
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            parent::deleteEntity($entityManager, $entityInstance);

            return;
        }

        $email = (string) $entityInstance->getEmail();
        $firstName = (string) $entityInstance->getFirstName();
        $lastName = (string) $entityInstance->getLastName();

        parent::deleteEntity($entityManager, $entityInstance);

        try {
            $message = (new Email())
                ->from(new Address('meriemzroud7@gmail.com', 'LearnWay'))
                ->to($email)
                ->subject('Your LearnWay account was deleted')
                ->html($this->renderView('emails/user_deleted_notice.html.twig', [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                ]))
                ->text(sprintf(
                    "Hello %s %s,\n\nYour LearnWay account linked to %s has been deleted by an administrator.\nIf this action was not expected, please contact support.",
                    $firstName,
                    $lastName,
                    $email
                ));

            $this->mailer->send($message);
            $this->addFlash('success', 'User deleted and deletion email sent.');
        } catch (\Throwable) {
            $this->addFlash('warning', 'User deleted but deletion email could not be sent.');
        }
    }

    private function generateTemporaryPassword(): string
    {
        return bin2hex(random_bytes(5)) . 'A1!';
    }

    /**
     * @param array<string, array{0:mixed, 1:mixed}> $changeSet
     *
     * @return array<int, array{field:string, old:string, new:string}>
     */
    private function normalizeChangeSet(array $changeSet): array
    {
        $ignoredFields = ['password_hash', 'plainPassword', 'updatedAt'];
        $changes = [];

        foreach ($changeSet as $field => [$oldValue, $newValue]) {
            if (in_array($field, $ignoredFields, true)) {
                continue;
            }

            if ($oldValue === $newValue) {
                continue;
            }

            $changes[] = [
                'field' => $this->fieldLabel($field),
                'old' => $this->stringifyValue($oldValue),
                'new' => $this->stringifyValue($newValue),
            ];
        }

        return $changes;
    }

    private function fieldLabel(string $field): string
    {
        return match ($field) {
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'date_of_birth' => 'Date of birth',
            'role' => 'Role',
            'is_active' => 'Active',
            'phone' => 'Phone',
            'employee_id' => 'Employee ID',
            'student_id' => 'Student ID',
            default => ucfirst(str_replace('_', ' ', $field)),
        };
    }

    private function stringifyValue(mixed $value): string
    {
        if ($value instanceof Role) {
            return (string) ($value->getName() ?: $value->getRoleCategory());
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value === null || $value === '') {
            return '-';
        }

        return (string) $value;
    }

    /**
     * @param array<int, array{field:string, old:string, new:string}> $changes
     */
    private function buildUpdateTextBody(User $user, array $changes): string
    {
        $lines = [
            sprintf('Hello %s %s,', (string) $user->getFirstName(), (string) $user->getLastName()),
            '',
            'Your LearnWay account has been updated by an administrator.',
            'Changes:',
        ];

        foreach ($changes as $change) {
            $lines[] = sprintf('- %s: %s -> %s', $change['field'], $change['old'], $change['new']);
        }

        $lines[] = '';
        $lines[] = 'If this was not expected, please contact support.';

        return implode("\n", $lines);
    }
}
