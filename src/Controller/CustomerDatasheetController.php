<?php

namespace App\Controller;

use App\Entity\Campany;
use App\Entity\FundingRequest;
use App\Entity\FundingRequestDeletionRequest;
use App\Entity\User;
use App\Form\CampanyType;
use App\Repository\FundingRequestDeletionRequestRepository;
use App\Repository\UserRepository;
use App\Service\InseeApiService;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CustomerDatasheetController extends AbstractController
{
    #[Route('/customer/datasheet/{id}', name: 'app_customer_datasheet')]
    public function index(EntityManagerInterface $em, $id): Response
    {

        $user = $em->getRepository(User::class)->find($id);
        // Force la récupération des campanies liées
        $campanies = $user->getCampanies();

        return $this->render('customer_datasheet/index.html.twig', [
            'users' => $user,
            'campanies' => $campanies,
        ]);
    }

    #[Route('/customer/campany/create/{id}', name: 'app_campany_create')]
    public function createCampany(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        InseeApiService $inseeApiService
    ): Response {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        $campany = new Campany();
        $campany->addCustomer($user);

        // ⚠️ Harmonisation avec "siret"
        $siret = $request->query->get('siret');

        if ($siret) {
            $inseeData = $inseeApiService->fetchCompanyBySiret($siret);

            if ($inseeData && isset($inseeData['etablissement'])) {
                $etab = $inseeData['etablissement'];
                $adresse = $etab['adresseEtablissement'];

                $campany->setLegalName($etab['uniteLegale']['denominationUniteLegale'] ?? null);
                $campany->setSiren($etab['siren']);
                $campany->setCreationDate(new \DateTime($etab['dateCreationEtablissement'] ?? 'now'));
                $campany->setAdress(trim(($adresse['typeVoieEtablissement'] ?? '') . ' ' . ($adresse['libelleVoieEtablissement'] ?? '') . ', ' . ($adresse['codePostalEtablissement'] ?? '') . ' ' . ($adresse['libelleCommuneEtablissement'] ?? '')));
                $campany->setSector($etab['activitePrincipaleRegistreMetiersEtablissement'] ?? 'Unknown');
                $campany->setStage('N/A');
            }
        }

        $form = $this->createForm(CampanyType::class, $campany);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $logoFile */
            $logoFile = $form->get('logo')->getData();

            if ($logoFile) {
                $newFilename = uniqid('logo_', true) . '.' . $logoFile->guessExtension();

                try {
                    $logoFile->move(
                        $this->getParameter('logos_directory'), // à définir dans config/services.yaml
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Gestion de l'erreur de déplacement
                    throw new \RuntimeException('Erreur lors du téléchargement du logo.');
                }

                $campany->setLogo($newFilename);
            }
            $entityManager->persist($campany);
            $entityManager->flush();

            $this->addFlash('success', 'Company created and linked to user!');
            return $this->redirectToRoute('app_campany', ['id' => $campany->getId(), 'user' => $user->getId()]);
        }

        return $this->render('customer_datasheet/create.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }




    #[Route('/customer/campany/edit/{id}', name: 'app_campany_edit')]
    public function editCampany(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $campany = $entityManager->getRepository(Campany::class)->find($id);

        if (!$campany) {
            throw $this->createNotFoundException('Entreprise introuvable.');
        }

        $form = $this->createForm(CampanyType::class, $campany);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $currentLogo = $campany->getLogo();
            $logoFile = $form->get('logo')->getData();

            if ($logoFile) {
                $newFilename = uniqid('logo_', true) . '.' . $logoFile->guessExtension();
                try {
                    $logoFile->move($this->getParameter('logos_directory'), $newFilename);
                    $campany->setLogo($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Le logo n\'a pas pu être envoyé.');
                    return $this->redirectToRoute('app_campany_edit', ['id' => $campany->getId()]);
                }
            } else {
                $campany->setLogo($currentLogo);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Entreprise mise a jour !');
            $customer = $campany->getCustomer()->first();
            if ($customer instanceof User) {
                return $this->redirectToRoute('app_campany', [
                    'id' => $campany->getId(),
                    'user' => $customer->getId(),
                ]);
            }

            return $this->redirectToRoute('app_customer_portal');
        }

        return $this->render('customer_datasheet/edit.html.twig', [
            'form' => $form->createView(),
            'campany' => $campany,
        ]);
    }




    #[Route('/api/company/insee', name: 'api_insee_lookup', methods: ['GET'])]
    public function fetchInseeSiret(Request $request, InseeApiService $inseeApiService): JsonResponse
    {
        $siret = $request->query->get('siret');

        if (!$siret) {
            return new JsonResponse(['error' => 'SIRET manquant'], 400);
        }

        $data = $inseeApiService->fetchCompanyBySiret($siret); // <- méthode à créer dans ton service
        if (!$data || !isset($data['etablissement'])) {
            return new JsonResponse(['error' => 'Entreprise introuvable'], 404);
        }

        $etab = $data['etablissement'];
        $adresse = $etab['adresseEtablissement'] ?? [];
        $periodeUniteLegale = $etab['uniteLegale'][0] ?? [];
        $periodeEtab = $etab['periodesEtablissement'][0] ?? [];

        return new JsonResponse([
            'legalName' => $etab['uniteLegale']['denominationUniteLegale'] ?? '',
            'siren' => $etab['siren'],
            'siret' => $etab['siret'],
            'creationDate' => $etab['dateCreationEtablissement'] ?? '',
            'sector' => '',
            'adress' => trim(
                ($adresse['typeVoieEtablissement'] ?? '') . ' ' .
                    ($adresse['libelleVoieEtablissement'] ?? '') . ', ' .
                    ($adresse['codePostalEtablissement'] ?? '') . ' ' .
                    ($adresse['libelleCommuneEtablissement'] ?? '')
            ),
            'stage' => ''
        ]);
    }




    #[Route('/customer/campany/{id}/{user}', name: 'app_campany')]
    public function campanyDatasheet(
        int $id,
        int $user,
        EntityManagerInterface $em,
        FundingRequestDeletionRequestRepository $deletionRequestRepository
    ): Response
    {

        $campanies = $em->getRepository(Campany::class)->find($id);

        $requestDemand = $em->getRepository(FundingRequest::class)->findBy(['campany' => $campanies]);

        $pendingDeletionByRequestId = [];
        foreach ($requestDemand as $fundingRequest) {
            $pendingDeletionByRequestId[$fundingRequest->getId()] = $deletionRequestRepository->hasPendingRequestForFundingRequest($fundingRequest);
        }


        return $this->render('customer_datasheet/campanyDatasheet.html.twig', [
            'campanies' => $campanies,
            'requestDemands' => $requestDemand,
            'user' => $user,
            'pendingDeletionByRequestId' => $pendingDeletionByRequestId,
        ]);
    }

    #[Route('/customer/campany/{id}/{user}/deletion-request', name: 'app_campany_deletion_request', methods: ['POST'])]
    public function requestDeletion(
        int $id,
        int $user,
        Request $request,
        EntityManagerInterface $em,
        FundingRequestDeletionRequestRepository $deletionRequestRepository,
        UserRepository $userRepository,
        MailerService $mailerService
    ): Response {
        if (!$this->isCsrfTokenValid('campany-deletion-request-'.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $campany = $em->getRepository(Campany::class)->find($id);
        if (!$campany) {
            throw $this->createNotFoundException('Entreprise introuvable.');
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            $isLinkedCustomer = false;
            foreach ($campany->getCustomer() as $customer) {
                if ($customer->getId() === $currentUser->getId()) {
                    $isLinkedCustomer = true;
                    break;
                }
            }

            if (!$isLinkedCustomer) {
                throw $this->createAccessDeniedException('Vous ne pouvez pas demander la suppression de ces dossiers.');
            }
        }

        $ids = array_map('intval', (array) $request->request->all('funding_request_ids'));
        $ids = array_values(array_filter($ids, static fn(int $value): bool => $value > 0));
        $reason = trim((string) $request->request->get('reason', ''));

        if ($ids === []) {
            $this->addFlash('warning', 'Veuillez sélectionner au moins un dossier.');

            return $this->redirectToRoute('app_campany', ['id' => $id, 'user' => $user]);
        }

        $fundingRequests = $em->getRepository(FundingRequest::class)->createQueryBuilder('fr')
            ->andWhere('fr.id IN (:ids)')
            ->andWhere('fr.campany = :campany')
            ->setParameter('ids', $ids)
            ->setParameter('campany', $campany)
            ->getQuery()
            ->getResult();

        $createdRequests = [];
        foreach ($fundingRequests as $fundingRequest) {
            if ($deletionRequestRepository->hasPendingRequestForFundingRequest($fundingRequest)) {
                continue;
            }

            $deletionRequest = (new FundingRequestDeletionRequest())
                ->setFundingRequest($fundingRequest)
                ->setRequestedBy($currentUser)
                ->setStatus(FundingRequestDeletionRequest::STATUS_PENDING)
                ->setReason($reason !== '' ? $reason : null);

            $em->persist($deletionRequest);
            $createdRequests[] = $deletionRequest;
        }

        if ($createdRequests === []) {
            $this->addFlash('info', 'Aucun nouveau dossier: une demande est déjà en attente.');

            return $this->redirectToRoute('app_campany', ['id' => $id, 'user' => $user]);
        }

        $em->flush();

        $admins = array_merge(
            $userRepository->findByRole('ROLE_ADMIN'),
            $userRepository->findByRole('ROLE_SUPER_ADMIN')
        );
        $adminsById = [];
        foreach ($admins as $admin) {
            $adminsById[$admin->getId()] = $admin;
        }

        foreach ($adminsById as $admin) {
            if (!$admin->getEmail()) {
                continue;
            }

            $mailerService->send(
                $admin->getEmail(),
                'Validation requise: suppression de dossier',
                'emails/funding_request_deletion_request.html.twig',
                [
                    'admin' => $admin,
                    'requester' => $currentUser,
                    'campany' => $campany,
                    'deletionRequests' => $createdRequests,
                    'reason' => $reason,
                ]
            );
        }

        $this->addFlash('success', 'Demande envoyée à l\'admin pour validation.');

        return $this->redirectToRoute('app_campany', ['id' => $id, 'user' => $user]);
    }
}
