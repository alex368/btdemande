<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->setPermission(Action::NEW, 'ROLE_SUPER_ADMIN')
            ->disable(Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        $isSuperAdmin = $this->isGranted('ROLE_SUPER_ADMIN');
        $roleChoices = [
            'Client' => 'ROLE_CUSTOMER',
            'Collaborateur' => 'ROLE_COLLABORATOR',
            'Admin' => 'ROLE_ADMIN',
        ];

        if ($isSuperAdmin) {
            $roleChoices['Super admin'] = 'ROLE_SUPER_ADMIN';
        }

        return [
            IdField::new('id')->hideOnForm(),
            EmailField::new('email', 'Email'),
            TextField::new('lastname', 'Nom'),
            TextField::new('name', 'Prénom'),
            TextField::new('number', 'Numéro'),
            ChoiceField::new('roles', 'Rôles')
                ->setChoices($roleChoices)
                ->allowMultipleChoices()
                ->renderExpanded(false),
            TextField::new('plainPassword', 'Mot de passe')
                ->onlyOnForms()
                ->setFormTypeOption('mapped', false)
                ->setHelp('Laisser vide en modification pour conserver le mot de passe actuel.'),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            parent::persistEntity($entityManager, $entityInstance);

            return;
        }

        $roles = $this->sanitizeRoles($entityInstance->getRoles());
        $this->guardRolePolicy($entityInstance, $roles);
        $entityInstance->setRoles($roles);

        $plainPassword = $this->getSubmittedPlainPassword();
        if ($plainPassword === null || $plainPassword === '') {
            throw new ForbiddenActionException('Le mot de passe est obligatoire à la création.');
        }

        $entityInstance->setPassword($this->passwordHasher->hashPassword($entityInstance, $plainPassword));

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            parent::updateEntity($entityManager, $entityInstance);

            return;
        }

        $roles = $this->sanitizeRoles($entityInstance->getRoles());
        $this->guardRolePolicy($entityInstance, $roles);
        $entityInstance->setRoles($roles);

        $plainPassword = $this->getSubmittedPlainPassword();
        if ($plainPassword !== null && $plainPassword !== '') {
            $entityInstance->setPassword($this->passwordHasher->hashPassword($entityInstance, $plainPassword));
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * @param list<string> $roles
     * @return list<string>
     */
    private function sanitizeRoles(array $roles): array
    {
        $roles = array_values(array_unique(array_filter($roles, static fn(string $role): bool => $role !== 'ROLE_USER')));

        return $roles;
    }

    /**
     * @param list<string> $roles
     */
    private function guardRolePolicy(User $editedUser, array $roles): void
    {
        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $roles, true);
        $existingSuperAdmins = array_filter(
            $this->userRepository->findByRole('ROLE_SUPER_ADMIN'),
            static fn(User $user): bool => $user->getId() !== $editedUser->getId()
        );

        if ($isSuperAdmin && !$this->isGranted('ROLE_SUPER_ADMIN') && count($existingSuperAdmins) > 0) {
            throw new ForbiddenActionException('Seul le super admin peut attribuer ce rôle.');
        }

        if ($isSuperAdmin && count($existingSuperAdmins) > 0) {
            throw new ForbiddenActionException('Un seul super admin est autorisé.');
        }

        if (!$isSuperAdmin && count($existingSuperAdmins) === 0 && $editedUser->getId() !== null) {
            throw new ForbiddenActionException('Il doit toujours y avoir un super admin actif.');
        }
    }

    private function getSubmittedPlainPassword(): ?string
    {
        $context = $this->getContext();
        if ($context === null) {
            return null;
        }

        $rawData = $context->getRequest()->request->all('User');
        if (!is_array($rawData)) {
            return null;
        }

        $plainPassword = $rawData['plainPassword'] ?? null;

        return is_string($plainPassword) ? trim($plainPassword) : null;
    }
}
