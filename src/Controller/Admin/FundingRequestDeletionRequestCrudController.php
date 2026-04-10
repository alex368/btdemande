<?php

namespace App\Controller\Admin;

use App\Entity\FundingRequestDeletionRequest;
use App\Entity\User;
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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class FundingRequestDeletionRequestCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $em
    )
    {
    }

    public static function getEntityFqcn(): string
    {
        return FundingRequestDeletionRequest::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Demande de suppression')
            ->setEntityLabelInPlural('Demandes de suppression')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $approve = Action::new('approveDelete', 'Valider', 'fa fa-check')
            ->linkToRoute('admin_funding_request_deletion_request_approve', static fn(FundingRequestDeletionRequest $request): array => ['id' => $request->getId()])
            ->displayIf(static fn(FundingRequestDeletionRequest $request): bool => $request->getStatus() === FundingRequestDeletionRequest::STATUS_PENDING);

        $reject = Action::new('rejectDelete', 'Refuser', 'fa fa-ban')
            ->linkToRoute('admin_funding_request_deletion_request_reject', static fn(FundingRequestDeletionRequest $request): array => ['id' => $request->getId()])
            ->displayIf(static fn(FundingRequestDeletionRequest $request): bool => $request->getStatus() === FundingRequestDeletionRequest::STATUS_PENDING);

        return $actions
            ->add(Crud::PAGE_INDEX, $approve)
            ->add(Crud::PAGE_DETAIL, $approve)
            ->add(Crud::PAGE_INDEX, $reject)
            ->add(Crud::PAGE_DETAIL, $reject)
            ->disable(Action::NEW, Action::EDIT, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('fundingRequest', 'Dossier'),
            AssociationField::new('requestedBy', 'Demandé par'),
            ChoiceField::new('status', 'Statut')
                ->setChoices([
                    'En attente' => FundingRequestDeletionRequest::STATUS_PENDING,
                    'Validée' => FundingRequestDeletionRequest::STATUS_APPROVED,
                    'Refusée' => FundingRequestDeletionRequest::STATUS_REJECTED,
                ]),
            TextareaField::new('reason', 'Motif'),
            DateTimeField::new('createdAt', 'Demandée le'),
            DateTimeField::new('decidedAt', 'Décidée le'),
            AssociationField::new('decidedBy', 'Décidée par'),
        ];
    }

    #[Route('/admin/funding-request-delete-request/{id}/approve', name: 'admin_funding_request_deletion_request_approve', methods: ['GET'])]
    public function approve(FundingRequestDeletionRequest $request): RedirectResponse
    {
        if ($request->getStatus() !== FundingRequestDeletionRequest::STATUS_PENDING) {
            $this->addFlash('warning', 'Cette demande est déjà traitée.');

            return $this->redirectToRoute('admin');
        }

        $fundingRequest = $request->getFundingRequest();
        if ($fundingRequest !== null) {
            foreach ($fundingRequest->getDocuments() as $document) {
                $this->em->remove($document);
            }

            $this->em->remove($fundingRequest);
            $request->setFundingRequest(null);
        }

        $user = $this->security->getUser();
        $request
            ->setStatus(FundingRequestDeletionRequest::STATUS_APPROVED)
            ->setDecidedAt(new \DateTimeImmutable())
            ->setDecidedBy($user instanceof User ? $user : null);

        $this->em->flush();
        $this->addFlash('success', 'Suppression validée et dossier supprimé.');

        return $this->redirectToRoute('admin');
    }

    #[Route('/admin/funding-request-delete-request/{id}/reject', name: 'admin_funding_request_deletion_request_reject', methods: ['GET'])]
    public function reject(FundingRequestDeletionRequest $request): RedirectResponse
    {
        if ($request->getStatus() !== FundingRequestDeletionRequest::STATUS_PENDING) {
            $this->addFlash('warning', 'Cette demande est déjà traitée.');

            return $this->redirectToRoute('admin');
        }

        $user = $this->security->getUser();
        $request
            ->setStatus(FundingRequestDeletionRequest::STATUS_REJECTED)
            ->setDecidedAt(new \DateTimeImmutable())
            ->setDecidedBy($user instanceof User ? $user : null);

        $this->em->flush();
        $this->addFlash('info', 'La demande de suppression a été refusée.');

        return $this->redirectToRoute('admin');
    }
}
