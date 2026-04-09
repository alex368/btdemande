<?php

namespace App\Controller\Admin;

use App\Entity\UserSessionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
class UserSessionEventCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UserSessionEvent::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Traçabilité utilisateurs')
            ->setEntityLabelInSingular('Événement utilisateur')
            ->setDefaultSort(['occurredAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $downloadReport = Action::new('downloadReport', 'Télécharger la synthèse', 'fa fa-download')
            ->linkToRoute('app_admin_tracking_report', static fn(UserSessionEvent $event): array => ['id' => $event->getId()]);

        return $actions
            ->add(Crud::PAGE_INDEX, $downloadReport)
            ->add(Crud::PAGE_DETAIL, $downloadReport)
            ->setPermission('downloadReport', 'ROLE_SUPER_ADMIN')
            ->disable(Action::NEW, Action::EDIT, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            DateTimeField::new('occurredAt', 'Date/heure'),
            AssociationField::new('user', 'Utilisateur'),
            TextField::new('eventType', 'Type'),
            TextField::new('actionName', 'Action'),
            TextField::new('routeName', 'Route')->hideOnIndex(),
            TextField::new('path', 'Page'),
            TextField::new('method', 'Méthode')->hideOnIndex(),
            TextField::new('sessionId', 'Session')->hideOnIndex(),
            TextField::new('roleSnapshotText', 'Rôles')->hideOnForm(),
            TextField::new('ipAddress', 'IP')->hideOnIndex(),
            TextareaField::new('referrer', 'Référent')->hideOnIndex(),
            TextareaField::new('userAgent', 'User-Agent')->hideOnIndex(),
            TextareaField::new('metadataText', 'Métadonnées')->hideOnIndex(),
        ];
    }
}
