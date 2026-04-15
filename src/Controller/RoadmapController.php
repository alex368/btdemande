<?php

namespace App\Controller;

use App\Entity\Campany;
use App\Entity\FundingRequest;
use App\Entity\Roadmap;
use App\Form\RoadmapType;
use App\Service\PdfGenerator;
use App\Service\QuarterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class RoadmapController extends AbstractController
{
    #[Route('/roadmap/{id}/{user}', name: 'app_roadmap', requirements: ['id' => Requirement::DIGITS, 'user' => Requirement::DIGITS])]
    public function index(EntityManagerInterface $em, QuarterService $quarterService, int $id, int $user): Response
    {
        $campany = $em->getRepository(Campany::class)->findOneById($id);

        if (!$campany) {
            throw $this->createNotFoundException("Entreprise #{$id} introuvable.");
        }

        $this->synchronizeValidatedFundingRequestsToRoadmap($em, $campany);

        $roadmapForm = $this->createForm(RoadmapType::class, new Roadmap(), [
            'action' => $this->generateUrl('app_new_roadmap', ['id' => $campany->getId(), 'user' => $user]),
        ]);

        return $this->render('roadmap/index.html.twig', [
            'campanies' => $campany,
            'roadmaps' => $this->buildRoadmapsWithQuarter($this->getOrderedRoadmaps($em, $campany), $quarterService),
            'user' => $user,
            'roadmapForm' => $roadmapForm->createView(),
        ]);
    }

    #[Route('/roadmap/new/{id}/{user}', name: 'app_new_roadmap', requirements: ['id' => Requirement::DIGITS, 'user' => Requirement::DIGITS], methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $em, QuarterService $quarterService, int $id, int $user): Response
    {
        $campany = $em->getRepository(Campany::class)->find($id);

        if (!$campany) {
            throw $this->createNotFoundException("Entreprise #{$id} introuvable.");
        }

        $this->denyAccessUnlessGrantedRoadmapManager();
        $this->synchronizeValidatedFundingRequestsToRoadmap($em, $campany);

        $roadmap = new Roadmap();
        $form = $this->createForm(RoadmapType::class, $roadmap, [
            'action' => $this->generateUrl('app_new_roadmap', ['id' => $campany->getId(), 'user' => $user]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $roadmap
                ->setCampany($campany)
                ->setPosition($this->getNextPosition($em, $campany));

            $em->persist($roadmap);
            $em->flush();

            if ($this->isAjaxRequest($request)) {
                return $this->json([
                    'success' => true,
                    'message' => 'La roadmap a été ajoutée avec succès.',
                    'roadmapId' => $roadmap->getId(),
                    'itemHtml' => $this->renderRoadmapItem($roadmap, $quarterService, $em, $user),
                ]);
            }

            $this->addFlash('success', 'La roadmap a été ajoutée avec succès.');

            return $this->redirectToRoute('app_roadmap', ['id' => $campany->getId(), 'user' => $user]);
        }

        if ($this->isAjaxRequest($request)) {
            return $this->json([
                'success' => false,
                'formHtml' => $this->renderView('roadmap/_form_modal_body.html.twig', [
                    'form' => $form->createView(),
                    'title' => 'Ajouter une étape à la roadmap',
                    'submitLabel' => 'Ajouter',
                ]),
            ], $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK);
        }

        return $this->redirectToRoute('app_roadmap', ['id' => $campany->getId(), 'user' => $user]);
    }

    #[Route('/roadmap/edit/{id}', name: 'app_edit_roadmap', requirements: ['id' => Requirement::DIGITS], methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em, QuarterService $quarterService): Response
    {
        $roadmap = $em->getRepository(Roadmap::class)->find($id);

        if (!$roadmap) {
            throw $this->createNotFoundException('Roadmap introuvable');
        }

        $this->denyAccessUnlessGrantedRoadmapManager();

        $currentUser = $this->getUser();
        $userId = method_exists($currentUser, 'getId') ? (int) $currentUser->getId() : 0;

        $form = $this->createForm(RoadmapType::class, $roadmap, [
            'action' => $this->generateUrl('app_edit_roadmap', ['id' => $roadmap->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            if ($this->isAjaxRequest($request)) {
                return $this->json([
                    'success' => true,
                    'message' => 'La roadmap a été mise à jour.',
                    'roadmapId' => $roadmap->getId(),
                    'itemHtml' => $this->renderRoadmapItem($roadmap, $quarterService, $em, $userId),
                ]);
            }

            $this->addFlash('success', 'Roadmap modifiée avec succès.');

            return $this->redirectToRoute('app_roadmap', [
                'id' => $roadmap->getCampany()?->getId(),
                'user' => $userId,
            ]);
        }

        if ($this->isAjaxRequest($request)) {
            return $this->json([
                'success' => false,
                'formHtml' => $this->renderView('roadmap/_form_modal_body.html.twig', [
                    'form' => $form->createView(),
                    'title' => 'Modifier cette étape',
                    'submitLabel' => 'Enregistrer',
                ]),
            ], $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK);
        }

        return $this->redirectToRoute('app_roadmap', [
            'id' => $roadmap->getCampany()?->getId(),
            'user' => $userId,
        ]);
    }

    #[Route('/roadmap/delete/{id}', name: 'app_delete_roadmap', methods: ['POST'], requirements: ['id' => Requirement::DIGITS])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $roadmap = $em->getRepository(Roadmap::class)->find($id);

        if (!$roadmap) {
            throw $this->createNotFoundException('Roadmap introuvable.');
        }

        $this->denyAccessUnlessGrantedRoadmapManager();

        if (!$this->isCsrfTokenValid('delete_roadmap_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $campany = $roadmap->getCampany();
        $campanyId = $campany?->getId() ?? 0;

        $em->remove($roadmap);
        $em->flush();

        if ($campany instanceof Campany) {
            $this->normalizeRoadmapPositions($em, $campany);
        }

        if ($this->isAjaxRequest($request)) {
            return $this->json([
                'success' => true,
                'message' => 'La roadmap a été supprimée.',
                'roadmapId' => $id,
            ]);
        }

        $this->addFlash('success', 'Roadmap supprimée avec succès.');

        $currentUser = $this->getUser();
        $userId = method_exists($currentUser, 'getId') ? (int) $currentUser->getId() : 0;

        return $this->redirectToRoute('app_roadmap', [
            'id' => $campanyId,
            'user' => $userId,
        ]);
    }

    #[Route('/roadmap/reorder/{id}/{user}', name: 'app_reorder_roadmap', requirements: ['id' => Requirement::DIGITS, 'user' => Requirement::DIGITS], methods: ['POST'])]
    public function reorder(Request $request, EntityManagerInterface $em, int $id, int $user): Response
    {
        $campany = $em->getRepository(Campany::class)->find($id);

        if (!$campany) {
            throw $this->createNotFoundException("Entreprise #{$id} introuvable.");
        }

        $this->denyAccessUnlessGrantedRoadmapManager();

        $payload = json_decode($request->getContent(), true);
        $orderedIds = $payload['orderedIds'] ?? null;

        if (!is_array($orderedIds) || $orderedIds === []) {
            return $this->json([
                'success' => false,
                'message' => 'Ordre de roadmap invalide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $roadmaps = $this->getOrderedRoadmaps($em, $campany);
        $roadmapsById = [];

        foreach ($roadmaps as $roadmap) {
            $roadmapsById[$roadmap->getId()] = $roadmap;
        }

        $position = 1;
        foreach ($orderedIds as $roadmapId) {
            $roadmapId = (int) $roadmapId;
            if (!isset($roadmapsById[$roadmapId])) {
                continue;
            }

            $roadmapsById[$roadmapId]->setPosition($position);
            ++$position;
        }

        foreach ($roadmaps as $roadmap) {
            if ($roadmap->getPosition() === null) {
                $roadmap->setPosition($position);
                ++$position;
            }
        }

        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'L’ordre de la roadmap a été mis à jour.',
            'user' => $user,
        ]);
    }

    #[Route('/roadmap/export/{id}', name: 'app_roadmap_export', requirements: ['id' => Requirement::DIGITS])]
    public function exportRoadmap(EntityManagerInterface $em, QuarterService $quarterService, PdfGenerator $pdfGenerator, int $id): Response
    {
        $campany = $em->getRepository(Campany::class)->find($id);

        if (!$campany) {
            throw $this->createNotFoundException("Entreprise #{$id} introuvable.");
        }

        $this->synchronizeValidatedFundingRequestsToRoadmap($em, $campany);

        return new Response(
            $pdfGenerator->generatePdf('roadmap/export.html.twig', [
                'campany' => $campany,
                'roadmaps' => $this->buildRoadmapsWithQuarter($this->getOrderedRoadmaps($em, $campany), $quarterService),
            ]),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('inline; filename="roadmap-%d.pdf"', $campany->getId()),
            ]
        );
    }

    private function getOrderedRoadmaps(EntityManagerInterface $em, Campany $campany): array
    {
        $this->normalizeRoadmapPositions($em, $campany);

        return $em->getRepository(Roadmap::class)
            ->createQueryBuilder('r')
            ->andWhere('r.campany = :campany')
            ->setParameter('campany', $campany)
            ->orderBy('r.position', 'ASC')
            ->addOrderBy('r.date', 'ASC')
            ->addOrderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function getNextPosition(EntityManagerInterface $em, Campany $campany): int
    {
        $maxPosition = $em->getRepository(Roadmap::class)
            ->createQueryBuilder('r')
            ->select('MAX(r.position)')
            ->andWhere('r.campany = :campany')
            ->setParameter('campany', $campany)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $maxPosition) + 1;
    }

    private function normalizeRoadmapPositions(EntityManagerInterface $em, Campany $campany): void
    {
        $roadmaps = $em->getRepository(Roadmap::class)
            ->createQueryBuilder('r')
            ->andWhere('r.campany = :campany')
            ->setParameter('campany', $campany)
            ->orderBy('r.position', 'ASC')
            ->addOrderBy('r.date', 'ASC')
            ->addOrderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult();

        $hasChanges = false;
        foreach ($roadmaps as $index => $roadmap) {
            $expectedPosition = $index + 1;
            if ($roadmap instanceof Roadmap && $roadmap->getPosition() !== $expectedPosition) {
                $roadmap->setPosition($expectedPosition);
                $hasChanges = true;
            }
        }

        if ($hasChanges) {
            $em->flush();
        }
    }

    private function buildRoadmapsWithQuarter(array $roadmaps, QuarterService $quarterService): array
    {
        $result = [];
        foreach ($roadmaps as $roadmap) {
            $result[] = [
                'entity' => $roadmap,
                'quarter' => $quarterService->getQuarter($roadmap->getDate()),
            ];
        }

        return $result;
    }

    private function renderRoadmapItem(Roadmap $roadmap, QuarterService $quarterService, EntityManagerInterface $em, int $user): string
    {
        $roadmaps = $this->getOrderedRoadmaps($em, $roadmap->getCampany());

        $index = 0;
        foreach ($roadmaps as $position => $existingRoadmap) {
            if ($existingRoadmap->getId() === $roadmap->getId()) {
                $index = $position;
                break;
            }
        }

        return $this->renderView('roadmap/_roadmap_item.html.twig', [
            'roadmap' => $roadmap,
            'quarter' => $quarterService->getQuarter($roadmap->getDate()),
            'index' => $index,
            'user' => $user,
        ]);
    }

    private function synchronizeValidatedFundingRequestsToRoadmap(EntityManagerInterface $em, Campany $campany): void
    {
        $validatedRequests = $em->getRepository(FundingRequest::class)
            ->createQueryBuilder('fr')
            ->andWhere('fr.campany = :campany')
            ->andWhere('(fr.status = :statusValidated OR (fr.status = :statusClosed AND fr.decision = :decisionValidated))')
            ->andWhere('fr.product IS NOT NULL')
            ->setParameter('campany', $campany)
            ->setParameter('statusValidated', FundingRequest::STATUS_VALIDATED)
            ->setParameter('statusClosed', FundingRequest::STATUS_CLOSED)
            ->setParameter('decisionValidated', FundingRequest::DECISION_VALIDATED)
            ->orderBy('fr.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        if ($validatedRequests === []) {
            return;
        }

        $existingRoadmaps = $em->getRepository(Roadmap::class)
            ->createQueryBuilder('r')
            ->andWhere('r.campany = :campany')
            ->andWhere('r.fundingRequest IS NOT NULL')
            ->setParameter('campany', $campany)
            ->getQuery()
            ->getResult();

        $existingByFundingRequest = [];
        foreach ($existingRoadmaps as $roadmap) {
            if (!$roadmap instanceof Roadmap) {
                continue;
            }

            $request = $roadmap->getFundingRequest();
            if ($request === null || $request->getId() === null) {
                continue;
            }

            $existingByFundingRequest[$request->getId()] = true;
        }

        $hasChanges = false;
        $nextPosition = $this->getNextPosition($em, $campany);

        foreach ($validatedRequests as $validatedRequest) {
            if (!$validatedRequest instanceof FundingRequest || $validatedRequest->getId() === null) {
                continue;
            }

            if (isset($existingByFundingRequest[$validatedRequest->getId()])) {
                continue;
            }

            $date = $validatedRequest->getCreatedAt() !== null
                ? \DateTime::createFromImmutable($validatedRequest->getCreatedAt())
                : new \DateTime();

            $roadmap = new Roadmap();
            $roadmap
                ->setCampany($campany)
                ->setProduct($validatedRequest->getProduct())
                ->setDate($date)
                ->setEstimatedAmount($validatedRequest->getAmount())
                ->setFundingRequest($validatedRequest)
                ->setPosition($nextPosition);

            ++$nextPosition;
            $em->persist($roadmap);
            $hasChanges = true;
        }

        if ($hasChanges) {
            $em->flush();
            $this->normalizeRoadmapPositions($em, $campany);
        }
    }

    private function isAjaxRequest(Request $request): bool
    {
        return $request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept'), 'application/json');
    }

    private function denyAccessUnlessGrantedRoadmapManager(): void
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_COLLABORATOR') && !$this->isGranted('ROLE_COLLABORATEUR')) {
            throw $this->createAccessDeniedException();
        }
    }
}
