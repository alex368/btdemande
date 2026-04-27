<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\ContactStageHistory;
use App\Entity\Opportunity;
use App\Entity\User;
use App\Repository\ContactStageHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProspectController extends AbstractController
{
    #[Route('/prospects/kanban', name: 'app_prospect_kanban')]
    public function kanban(EntityManagerInterface $em): Response
    {
        $stages = array_flip(ContactStageHistory::getStageChoices());
        $contacts = $this->findVisibleContacts($em, false);
        $contactIds = array_map(static fn(Contact $contact): int => (int) $contact->getId(), $contacts);

        $latestStageByContact = $this->getLatestStageByContact($em, $contactIds);
        $latestOpportunityByContact = $this->getLatestOpportunityByContact($em, $contactIds);

        $columns = [];
        foreach ($stages as $stageKey => $_label) {
            $columns[$stageKey] = [];
        }

        $stats = ['total' => 0, 'won' => 0, 'lost' => 0, 'open' => 0];

        foreach ($contacts as $contact) {
            $contactId = (int) $contact->getId();
            $stageHistory = $latestStageByContact[$contactId] ?? null;
            $opportunity = $latestOpportunityByContact[$contactId] ?? null;
            $stage = $stageHistory instanceof ContactStageHistory ? $stageHistory->getStage() : ContactStageHistory::STAGE_PROSPECT;

            if (!isset($columns[$stage])) {
                $stage = ContactStageHistory::STAGE_PROSPECT;
            }

            $columns[$stage][] = [
                'contact' => $contact,
                'stageHistory' => $stageHistory,
                'opportunity' => $opportunity,
            ];

            $stats['total']++;
            if ($stage === ContactStageHistory::STAGE_WON) {
                $stats['won']++;
            } elseif ($stage === ContactStageHistory::STAGE_LOST) {
                $stats['lost']++;
            } else {
                $stats['open']++;
            }
        }

        $stats['archived'] = count($this->findVisibleContacts($em, true));
        $stats['by_stage'] = [];
        foreach ($columns as $stageKey => $items) {
            $stats['by_stage'][$stageKey] = count($items);
        }

        return $this->render('prospect/kanban.html.twig', [
            'stages' => $stages,
            'columns' => $columns,
            'stats' => $stats,
        ]);
    }

    #[Route('/prospects/contacts-archives', name: 'app_prospect_archives')]
    public function archives(EntityManagerInterface $em): Response
    {
        return $this->render('prospect/archives.html.twig', [
            'activeContacts' => $this->findVisibleContacts($em, false),
            'archivedContacts' => $this->findVisibleContacts($em, true),
        ]);
    }

    #[Route('/prospects/contact/{id}/archive', name: 'app_prospect_contact_archive', methods: ['POST'])]
    public function archiveContact(Contact $contact, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('archive-contact-' . $contact->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton invalide.');
            return $this->redirectToRoute('app_prospect_kanban');
        }

        if (!$this->canAccessContact($em, $contact)) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $contact
            ->setIsArchived(true)
            ->setArchivedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));

        $em->flush();
        $this->addFlash('success', 'Contact archivé.');

        return $this->redirectToRoute('app_prospect_kanban');
    }

    #[Route('/prospects/contact/{id}/unarchive', name: 'app_prospect_contact_unarchive', methods: ['POST'])]
    public function unarchiveContact(Contact $contact, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('unarchive-contact-' . $contact->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton invalide.');
            return $this->redirectToRoute('app_prospect_archives');
        }

        if (!$this->canAccessContact($em, $contact)) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $contact
            ->setIsArchived(false)
            ->setArchivedAt(null);

        $em->flush();
        $this->addFlash('success', 'Contact désarchivé.');

        return $this->redirectToRoute('app_prospect_archives');
    }

    #[Route('/prospects/kanban/update-stage', name: 'app_prospect_kanban_update_stage', methods: ['POST'])]
    public function updateStage(
        Request $request,
        EntityManagerInterface $em,
        ContactStageHistoryRepository $stageHistoryRepository,
        LoggerInterface $logger
    ): JsonResponse {
        try {
            $data = json_decode((string) $request->getContent(), true);
            $allowedStages = array_keys(array_flip(ContactStageHistory::getStageChoices()));

            if (!isset($data['contactId'], $data['stage']) || !in_array($data['stage'], $allowedStages, true)) {
                return new JsonResponse(['success' => false, 'message' => 'Données invalides'], 400);
            }

            $contact = $em->getRepository(Contact::class)->find((int) $data['contactId']);
            if (!$contact instanceof Contact) {
                return new JsonResponse(['success' => false, 'message' => 'Contact introuvable'], 404);
            }

            if ($contact->isArchived()) {
                return new JsonResponse(['success' => false, 'message' => 'Contact archivé'], 400);
            }

            if (!$this->canAccessContact($em, $contact)) {
                return new JsonResponse(['success' => false, 'message' => 'Accès refusé'], 403);
            }

            $stageValue = (string) $data['stage'];
            $history = $stageHistoryRepository->findOneByContactAndStage($contact, $stageValue);

            if (!$history instanceof ContactStageHistory) {
                $history = (new ContactStageHistory())
                    ->setContact($contact)
                    ->setStage($stageValue);
                $em->persist($history);
            }

            $history
                ->setOccurredAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))
                ->setUpdatedBy($this->getUser() instanceof User ? $this->getUser() : null);

            $em->flush();

            return new JsonResponse(['success' => true]);
        } catch (\Throwable $throwable) {
            $logger->error('Erreur update stage kanban', [
                'message' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur serveur lors de la mise à jour de l’étape.',
            ], 500);
        }
    }

    /**
     * @return Contact[]
     */
    private function findVisibleContacts(EntityManagerInterface $em, bool $archived): array
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return [];
        }

        $qb = $em->getRepository(Contact::class)->createQueryBuilder('c')
            ->leftJoin('c.opportunity', 'o')
            ->addSelect('o')
            ->andWhere('c.isArchived = :archived')
            ->setParameter('archived', $archived)
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC')
            ->distinct();

        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $qb
                ->andWhere('c.account = :user OR o.user = :user OR o.commercialReferent = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }

    private function canAccessContact(EntityManagerInterface $em, Contact $contact): bool
    {
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $count = (int) $em->getRepository(Opportunity::class)
            ->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.contact = :contact')
            ->andWhere('o.user = :user OR o.commercialReferent = :user')
            ->setParameter('contact', $contact)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return $contact->getAccount() === $user || $count > 0;
    }

    /**
     * @param int[] $contactIds
     * @return array<int, ContactStageHistory>
     */
    private function getLatestStageByContact(EntityManagerInterface $em, array $contactIds): array
    {
        if ($contactIds === []) {
            return [];
        }

        $histories = $em->getRepository(ContactStageHistory::class)
            ->createQueryBuilder('s')
            ->leftJoin('s.contact', 'c')
            ->addSelect('c')
            ->andWhere('c.id IN (:contactIds)')
            ->setParameter('contactIds', $contactIds)
            ->orderBy('s.occurredAt', 'DESC')
            ->addOrderBy('s.id', 'DESC')
            ->getQuery()
            ->getResult();

        $latest = [];
        foreach ($histories as $history) {
            if (!$history instanceof ContactStageHistory || $history->getContact() === null) {
                continue;
            }

            $contactId = (int) $history->getContact()->getId();
            if (!isset($latest[$contactId])) {
                $latest[$contactId] = $history;
            }
        }

        return $latest;
    }

    /**
     * @param int[] $contactIds
     * @return array<int, Opportunity>
     */
    private function getLatestOpportunityByContact(EntityManagerInterface $em, array $contactIds): array
    {
        if ($contactIds === []) {
            return [];
        }

        $opportunities = $em->getRepository(Opportunity::class)
            ->createQueryBuilder('o')
            ->leftJoin('o.contact', 'c')
            ->addSelect('c')
            ->andWhere('c.id IN (:contactIds)')
            ->setParameter('contactIds', $contactIds)
            ->orderBy('o.createdAt', 'DESC')
            ->addOrderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();

        $latest = [];
        foreach ($opportunities as $opportunity) {
            if (!$opportunity instanceof Opportunity || $opportunity->getContact() === null) {
                continue;
            }

            $contactId = (int) $opportunity->getContact()->getId();
            if (!isset($latest[$contactId])) {
                $latest[$contactId] = $opportunity;
            }
        }

        return $latest;
    }
}
