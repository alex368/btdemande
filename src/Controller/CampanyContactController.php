<?php

namespace App\Controller;

use App\Entity\CampanyContact;
use App\Entity\Contact;
use App\Form\CampanyContactType;
use App\Form\CampanyType;
use App\Service\CampanyCreatorService;
use App\Service\InseeApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CampanyContactController extends AbstractController
{

    #[Route('/contact/campany/create/{id}', name: 'app_campany_contact_create')]
    public function createCampany(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        InseeApiService $inseeApiService
    ): Response {
        $user = $entityManager->getRepository(Contact::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        $campany = new CampanyContact();
        $campany->addContact($user);

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

        $form = $this->createForm(CampanyContactType::class, $campany);
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
            return $this->redirectToRoute('app_campany_contact_show', ['id' => $campany->getId()]);
        }

        return $this->render('campany_contact/create.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }




    #[Route('/customer/campany/edit/{id}', name: 'app_campany_contact_edit')]
    public function editCampany(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $campany = $entityManager->getRepository(CampanyContact::class)->find($id);

        if (!$campany) {
            throw $this->createNotFoundException('Entreprise introuvable.');
        }

        $form = $this->createForm(CampanyContactType::class, $campany);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $logoFile = $form->get('logo')->getData();

            if ($logoFile) {
                $newFilename = uniqid('logo_', true) . '.' . $logoFile->guessExtension();
                $logoFile->move($this->getParameter('logos_directory'), $newFilename);
                $campany->setLogo($newFilename);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Entreprise mise a jour !');
            return $this->redirectToRoute('app_campany_contact_show', ['id' => $campany->getId()]);
        }

        return $this->render('campany_contact/edit.html.twig', [
            'form' => $form->createView(),
            'campany' => $campany,
        ]);
    }




    #[Route('/customer/campany/delete/{id}', name: 'app_campany_contact_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        CampanyContact $campany,
        EntityManagerInterface $em,
        $id
    ): Response {

        $contact = $em->getRepository(CampanyContact::class)->findOneBy(['id' => $id]);
        $id = $contact->getId();

        if ($this->isCsrfTokenValid(
            'delete-campany-' . $campany->getId(),
            $request->request->get('_token')
        )) {
            $em->remove($campany);
            $em->flush();
        }

        return $this->redirectToRoute('app_contact_show', ['id' => $id]);
    }


    #[Route('/customer/campany/show/{id}', name: 'app_campany_contact_show', methods: ['GET'])]
    public function show(CampanyContact $campany): Response
    {
        return $this->render('campany_contact/show.html.twig', [
            'campanies' => $campany,
        ]);
    }
}
