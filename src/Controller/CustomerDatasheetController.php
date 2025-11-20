<?php

namespace App\Controller;

use App\Entity\Campany;
use App\Entity\FundingRequest;
use App\Entity\User;
use App\Form\CampanyType;
use App\Service\InseeApiService;
use Doctrine\ORM\EntityManager;
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
            // Gestion de l’erreur de déplacement
            throw new \RuntimeException('Erreur lors du téléchargement du logo.');
        }

        $campany->setLogo($newFilename);
    }
        $entityManager->persist($campany);
        $entityManager->flush();

        $this->addFlash('success', 'Company created and linked to user!');
        return $this->redirectToRoute('app_customer_portal');
    }

    return $this->render('customer_datasheet/create.html.twig', [
        'form' => $form->createView(),
        'user' => $user,
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
    'sector' =>'',
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
    public function campanyDatasheet(int $id, int $user,EntityManagerInterface $em): Response
    {

        $campanies = $em->getRepository(Campany::class)->find($id);

        $requestDemand = $em->getRepository(FundingRequest::class)->findBy(['campany'=>$campanies]);


        return $this->render('customer_datasheet/campanyDatasheet.html.twig', [
            'campanies' => $campanies,
            'requestDemands' => $requestDemand,
            'user' => $user
        ]);
    }

   


}
