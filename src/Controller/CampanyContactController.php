<?php

namespace App\Controller;

use App\Entity\CampanyContact;
use App\Entity\Campany;
use App\Entity\Contact;
use App\Entity\User;
use App\Form\CampanyContactType;
use App\Service\InseeApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class CampanyContactController extends AbstractController
{

    #[Route('/contact/campany/create/{id}', name: 'app_campany_contact_create')]
    #[Route('/contact/{id}/create', name: 'app_contact_create')]
    public function createCampany(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        InseeApiService $inseeApiService,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $contact = $entityManager->getRepository(Contact::class)->find($id);

        if (!$contact) {
            throw $this->createNotFoundException('Contact introuvable.');
        }

        $campany = new CampanyContact();
        $campany->addContact($contact);

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

        $form = $this->createForm(CampanyContactType::class, $campany, [
            'include_create_account' => $contact->getAccount() === null,
        ]);
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

            $plainPassword = null;
            $shouldCreateAccount = $contact->getAccount() === null && $form->has('createAccount') && (bool) $form->get('createAccount')->getData();
            if ($shouldCreateAccount) {
                [$createdUser, $plainPassword] = $this->createCustomerAccountFromContact($contact, $passwordHasher, $entityManager);
                $this->syncCampaniesFromContact($contact, $createdUser, $entityManager);
            }

            $entityManager->flush();

            if ($plainPassword !== null) {
                $this->addFlash('success', sprintf('Entreprise créée et compte client activé. Mot de passe temporaire : %s', $plainPassword));
                return $this->redirectToRoute('app_customer_datasheet', ['id' => $contact->getAccount()?->getId()]);
            }

            $this->addFlash('success', 'Entreprise créée et liée au contact.');
            return $this->redirectToRoute('app_contact_show', ['id' => $contact->getId()]);
        }

        return $this->render('campany_contact/create.html.twig', [
            'form' => $form->createView(),
            'user' => $contact,
        ]);
    }




    #[Route('/contact/campany/edit/{id}', name: 'app_campany_contact_edit')]
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

            $this->addFlash('success', 'Entreprise mise à jour !');
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

    private function createCustomerAccountFromContact(Contact $contact, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): array
    {
        $emails = $contact->getEmail();
        $email = is_array($emails) && count($emails) > 0 ? trim((string) $emails[0]) : '';
        if ($email === '') {
            throw new \RuntimeException('Impossible de créer un compte sans adresse email.');
        }

        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser instanceof User) {
            $contact->setAccount($existingUser);
            return [$existingUser, null];
        }

        $user = $contact->toUser();
        $plainPassword = bin2hex(random_bytes(5));
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
        $user->setRoles(['ROLE_CUSTOMER']);

        $contact->setAccount($user);
        $entityManager->persist($user);

        return [$user, $plainPassword];
    }

    private function syncCampaniesFromContact(Contact $contact, User $user, EntityManagerInterface $entityManager): void
    {
        foreach ($contact->getCampanyContacts() as $campanyContact) {
            $alreadyLinked = false;

            foreach ($user->getCampanies() as $existingCampany) {
                if (
                    (string) ($existingCampany->getSiren() ?? '') !== ''
                    && (string) ($existingCampany->getSiren() ?? '') === (string) ($campanyContact->getSiren() ?? '')
                ) {
                    $alreadyLinked = true;
                    break;
                }

                if (
                    (string) ($existingCampany->getLegalName() ?? '') !== ''
                    && (string) ($existingCampany->getLegalName() ?? '') === (string) ($campanyContact->getLegalName() ?? '')
                ) {
                    $alreadyLinked = true;
                    break;
                }
            }

            if ($alreadyLinked) {
                continue;
            }

            $campany = new Campany();
            $campany->setLegalName($campanyContact->getLegalName() ?? 'Entreprise');
            $campany->setProjetName($campanyContact->getProjectName());
            $campany->setSiren($campanyContact->getSiren() ?: '0');
            $campany->setSector($campanyContact->getSector() ?? 'Autre');
            $campany->setAdress($campanyContact->getAdress() ?? '');
            $campany->setCreationDate($campanyContact->getCreationDate() ?? new \DateTime());
            $campany->setStage($campanyContact->getStage() ?? 'N/A');
            $campany->setLogo($campanyContact->getLogo());
            $user->addCampany($campany);

            $entityManager->persist($campany);
        }
    }
}
