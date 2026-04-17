<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\Campany;
use App\Entity\CampanyContact;
use App\Entity\Contact;
use App\Entity\ContactStageHistory;
use App\Entity\Opportunity;
use App\Entity\Quote;
use App\Entity\User;
use App\Form\ActivityType;
use App\Form\ContactType;
use App\Form\ImportContactsType;
use App\Form\OpportunityType;
use App\Repository\ContactRepository;
use App\Repository\ContactStageHistoryRepository;
use App\Service\CampanyCreatorService;
use App\Service\ContactConverterService;
use Doctrine\ORM\EntityManagerInterface;
use Google\Service\AndroidProvisioningPartner\Company;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{



    #[Route('/contact', name: 'app_contact')]
    public function index(ContactRepository $contactRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $company = trim((string) $request->query->get('company', ''));
        $city = trim((string) $request->query->get('city', ''));

        $qb = $contactRepository->createQueryBuilder('c')
            ->leftJoin('c.campanyContacts', 'cc')
            ->addSelect('cc')
            ->distinct();

        if ($search !== '') {
            $qb
                ->where('c.firstName LIKE :search')
                ->orWhere('c.lastName LIKE :search')
                ->orWhere('c.city LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($company !== '') {
            $qb
                ->andWhere('cc.legalName LIKE :company')
                ->setParameter('company', '%' . $company . '%');
        }

        if ($city !== '') {
            $qb
                ->andWhere('c.city LIKE :city')
                ->setParameter('city', '%' . $city . '%');
        }

        $qb->orderBy('c.lastName', 'DESC');

        $contacts = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('contact/index.html.twig', [
            'contacts' => $contacts,
            'q' => $search,
            'company' => $company,
            'city' => $city,
        ]);
    }

    #[Route('/contact/add', name: 'app_contact_add')]
    public function add(EntityManagerInterface $em, Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {

        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($contact);
            $plainPassword = $this->handleOptionalCompanyAndAccount($contact, $form, $em, $passwordHasher);
            $em->flush();

            if ($plainPassword !== null) {
                $this->addFlash('success', sprintf('Contact enregistré. Compte client créé (mot de passe temporaire : %s).', $plainPassword));
            } else {
                $this->addFlash('success', 'Contact enregistré avec succès.');
            }

            return $this->redirectToRoute('app_contact');
        }
        return $this->render('contact/add.html.twig', [
            'form' => $form->createView(),
            'hasAccount' => false,
        ]);
    }


    #[Route('/contact/{id}/edit', name: 'app_contact_edit')]
    public function edit(Contact $contact, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Contact est automatiquement récupéré grâce au ParamConverter

        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $this->handleOptionalCompanyAndAccount($contact, $form, $em, $passwordHasher);
            $em->flush();

            if ($plainPassword !== null) {
                $this->addFlash('success', sprintf('Contact mis à jour. Compte client créé (mot de passe temporaire : %s).', $plainPassword));
            } else {
                $this->addFlash('success', 'Le contact a été mis à jour.');
            }

            return $this->redirectToRoute('app_contact'); // page liste
        }

        return $this->render('contact/edit.html.twig', [
            'form' => $form->createView(),
            'contact' => $contact,
            'hasAccount' => $contact->getAccount() !== null,
        ]);
    }


    #[Route('/contact/{id}/delete', name: 'app_contact_delete', methods: ['POST'])]
    public function delete(Contact $contact, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete-contact-' . $contact->getId(), $request->request->get('_token'))) {

            $em->remove($contact);
            $em->flush();

            $this->addFlash('success', 'Le contact a bien été supprimé.');
        }

        return $this->redirectToRoute('app_contact');
    }

    #[Route('/contact/{id}', name: 'app_contact_show', methods: ['GET'])]
    public function show(Contact $contact, ContactStageHistoryRepository $contactStageHistoryRepository): Response
    {


        return $this->render('contact/show.html.twig', [
            'contact' => $contact,
            'opportunities' => $contact->getOpportunity(),
            'activities' => $contact->getActivities(),
            'quotes' => $contact->getQuotes(),
            'campanies' => $contact->getCampanyContacts(),
            'stageHistories' => $contactStageHistoryRepository->findByContactOrdered($contact),
            'stageChoices' => ContactStageHistory::getStageChoices(),
        ]);
    }

    #[Route('/contact/{id}/stage', name: 'app_contact_stage_add', methods: ['POST'])]
    public function addStage(
        Contact $contact,
        Request $request,
        EntityManagerInterface $em,
        ContactStageHistoryRepository $contactStageHistoryRepository
    ): Response {
        if (!$this->isCsrfTokenValid('add-stage-' . $contact->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton invalide.');
            return $this->redirectToRoute('app_contact_show', ['id' => $contact->getId()]);
        }

        $stage = (string) $request->request->get('stage');
        $allowedStages = array_values(ContactStageHistory::getStageChoices());
        if (!in_array($stage, $allowedStages, true)) {
            $this->addFlash('danger', 'Étape invalide.');
            return $this->redirectToRoute('app_contact_show', ['id' => $contact->getId()]);
        }

        $history = $contactStageHistoryRepository->findOneByContactAndStage($contact, $stage);
        if (!$history instanceof ContactStageHistory) {
            $history = (new ContactStageHistory())
                ->setContact($contact)
                ->setStage($stage);
            $em->persist($history);
        }

        $history
            ->setOccurredAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))
            ->setUpdatedBy($this->getUser() instanceof User ? $this->getUser() : null);

        $em->flush();

        $this->addFlash('success', 'Étape enregistrée/mise à jour avec date et heure.');

        return $this->redirectToRoute('app_contact_show', ['id' => $contact->getId()]);
    }

    #[Route('/contacts/import', name: 'app_contact_import')]
    public function import(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ImportContactsType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $file = $form->get('file')->getData();
            $spreadsheet = IOFactory::load($file->getPathname());
            $rows = $spreadsheet->getActiveSheet()->toArray();

            $header = array_shift($rows); // en-têtes

            foreach ($rows as $row) {

                if (!isset($row[0]) || empty(array_filter($row))) {
                    continue;
                }

                $data = array_combine($header, $row);

                // 🔍 Étape 1 : récupérer toutes les infos utiles
                $values = array_filter([
                    $data['salutation'] ?? null,
                    $data['lastName'] ?? null,
                    $data['firstName'] ?? null,
                    ...array_filter($data, fn($value, $key) => str_starts_with($key, 'email'), ARRAY_FILTER_USE_BOTH),
                    ...array_filter($data, fn($value, $key) => str_starts_with($key, 'phone'), ARRAY_FILTER_USE_BOTH),
                    ...array_filter($data, fn($value, $key) => str_starts_with($key, 'mobile'), ARRAY_FILTER_USE_BOTH),
                    ...array_filter($data, fn($value, $key) => str_starts_with($key, 'socialMedia'), ARRAY_FILTER_USE_BOTH),
                    $data['country'] ?? null,
                    $data['address'] ?? null,
                    $data['city'] ?? null,
                    $data['website'] ?? null,
                    $data['occupation'] ?? null,
                    $data['zipCode'] ?? null,
                ]);

                // 🔍 Étape 2 : enlever les valeurs vides
                $values = array_filter($values, fn($v) => !empty(trim((string) $v)));

                // 🔍 Étape 3 : vérifier si TOUTES les valeurs sont les mêmes
                if (count($values) > 1 && count(array_unique($values)) === 1) {
                    // 🛑 Ligne ignorée car entièrement dupliquée
                    continue;
                }

                // Si on arrive ici → la ligne est valide, on importe
                $contact = new Contact();

                // Informations de base
                $contact->setSalutation($data['salutation'] ?? null);
                $contact->setLastName($data['lastName'] ?? null);
                $contact->setFirstName($data['firstName'] ?? null);

                // Emails
                $emails = [];
                foreach ($data as $key => $value) {
                    if (str_starts_with($key, 'email') && $value) {
                        $emails[] = $value;
                    }
                }
                $contact->setEmail(array_unique($emails));

                // Téléphones
                $phones = [];
                foreach ($data as $key => $value) {
                    if (str_starts_with($key, 'phone') && $value) {
                        $phones[] = $value;
                    }
                }
                $contact->setPhone(array_unique($phones));

                // Mobiles
                $mobiles = [];
                foreach ($data as $key => $value) {
                    if (str_starts_with($key, 'mobile') && $value) {
                        $mobiles[] = $value;
                    }
                }
                $contact->setMobilePhone(array_unique($mobiles));

                // Réseaux sociaux
                $socialMedia = [];
                foreach ($data as $key => $value) {
                    if (str_starts_with($key, 'socialMedia') && $value) {
                        $socialMedia[] = $value;
                    }
                }
                $contact->setSocialMedia(array_unique($socialMedia));

                // Champs simples
                $contact->setCountry($data['country'] ?? null);
                $contact->setAdress($data['address'] ?? null);
                $contact->setCity($data['city'] ?? null);
                $contact->setWebsite($data['website'] ?? null);
                $contact->setOccupation($data['occupation'] ?? null);
                $contact->setZipCode($data['zipCode'] ?? null);

                // Enregistrement
                $em->persist($contact);
            }

            $em->flush();

            $this->addFlash('success', 'Import terminé avec succès !');
            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/import.html.twig', [
            'form' => $form->createView(),
        ]);
    }
#[Route('/contact/{id}/convert-user', name: 'contact_convert_user')]
public function convertToUser(
    Contact $contact,
    EntityManagerInterface $em,
    UserPasswordHasherInterface $passwordHasher
): Response {

    // Vérifier email existant
    if ($contact->getEmail()) {

        $existing = $em->getRepository(User::class)->findOneBy([
            'email' => $contact->getEmail()
        ]);

        if ($existing) {
            $this->addFlash('warning', 'Un utilisateur existe déjà pour cet email.');
            return $this->redirectToRoute('app_contact_show', [
                'id' => $contact->getId()
            ]);
        }
    }

    // 1️⃣ Création User
    $user = $contact->toUser();

    $hashedPassword = $passwordHasher->hashPassword($user, 'ChangeMe123!');
    $user->setPassword($hashedPassword);

    $contact->setAccount($user);

    // 2️⃣ Création Campany(s) via CampanyContact
    foreach ($contact->getCampanyContacts() as $campanyContact) {

        $campany = new Campany();

        $campany->setLegalName($campanyContact->getLegalName());
        $campany->setProjetName($campanyContact->getProjectName());
        $campany->setSiren($campanyContact->getSiren());
        $campany->setSector($campanyContact->getSector());
        $campany->setAdress($campanyContact->getAdress());
        $campany->setCreationDate($campanyContact->getCreationDate());
        $campany->setStage($campanyContact->getStage());
        $campany->setLogo($campanyContact->getLogo());

        // 🔥 Liaison ManyToMany propre
        $user->addCampany($campany);

        $em->persist($campany);
    }

    $em->persist($user);
    $em->flush();

    $this->addFlash('success', 'Le contact a été converti en client.');

    return $this->redirectToRoute('app_customer_datasheet', [
        'id' => $user->getId(),
    ]);
}

  #[Route('/campany/create', name: 'campany_create', methods: ['POST'])]
    public function create(
        Request $request,
        CampanyCreatorService $creator
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);

        if (empty($data['legalName']) || empty($data['siren'])) {
            return new JsonResponse([
                'error' => 'legalName et siren sont obligatoires'
            ], 400);
        }

        $campany = $creator->createFromArray($data);

        return new JsonResponse([
            'success' => true,
            'id' => $campany->getId(),
            'legalName' => $campany->getLegalName()
        ]);
    }






#[Route('/campany/create-from-siret/{siret}', name: 'campany_create_siret', methods: ['GET'])]
public function createFromSiret(
    string $siret,
    CampanyCreatorService $creator
): JsonResponse {
    if (strlen($siret) !== 14) {
        return new JsonResponse([
            'error' => 'SIRET invalide (14 chiffres requis)'
        ], 400);
    }


    
    $campany = $creator->createFromSiret($siret);

    if (!$campany) {
        return new JsonResponse([
            'error' => 'Entreprise non trouvée via INSEE'
        ], 404);
    }
    return new JsonResponse([
        'success' => true,
        'id' => $campany->getId(),
        'legalName' => $campany->getLegalName()
    ]);
}


#[Route('/campany/create-from-siren/{siren}', name: 'campany_create_siren', methods: ['GET'])]
public function createFromSiren(
    string $siren,
    CampanyCreatorService $creator
): JsonResponse {


 
    $campany = $creator->createFromSiren($siren);

    dd($campany);

    if (!$campany) {
        return new JsonResponse([
            'error' => 'Entreprise non trouvée ou SIREN invalide'
        ], 404);
    }

    return new JsonResponse([
        'success' => true,
        'id' => $campany->getId(),
        'legalName' => $campany->getLegalName(),
        'siren' => $campany->getSiren()
    ]);
}

private function handleOptionalCompanyAndAccount(
    Contact $contact,
    FormInterface $form,
    EntityManagerInterface $em,
    UserPasswordHasherInterface $passwordHasher
): ?string {
    $plainPassword = null;

    $shouldCreateCompany = (bool) $form->get('createCompany')->getData();
    if ($shouldCreateCompany) {
        $companyLegalName = trim((string) $form->get('companyLegalName')->getData());
        $companyProjectName = trim((string) $form->get('companyProjectName')->getData());
        $companySiren = trim((string) $form->get('companySiren')->getData());

        if ($companyLegalName !== '' || $companyProjectName !== '' || $companySiren !== '') {
            $campanyContact = new CampanyContact();
            $campanyContact->addContact($contact);
            $campanyContact->setLegalName($companyLegalName !== '' ? $companyLegalName : 'Entreprise');
            $campanyContact->setProjectName($companyProjectName !== '' ? $companyProjectName : null);
            $campanyContact->setSiren($companySiren !== '' ? $companySiren : '0');
            $campanyContact->setSector($form->get('companySector')->getData());
            $campanyContact->setAdress($form->get('companyAddress')->getData());
            $campanyContact->setStage($form->get('companyStage')->getData());
            $campanyContact->setCreationDate($form->get('companyCreationDate')->getData());
            $em->persist($campanyContact);
        }
    }

    $shouldCreateAccount = $form->has('createAccount') && (bool) $form->get('createAccount')->getData();
    if ($shouldCreateAccount && $contact->getAccount() === null) {
        $primaryEmail = $this->extractPrimaryEmail($contact);
        if ($primaryEmail === null) {
            throw new \RuntimeException('Impossible de créer un compte sans adresse email.');
        }

        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $primaryEmail]);
        if ($existingUser instanceof User) {
            $contact->setAccount($existingUser);
            return null;
        }

        $user = $contact->toUser();
        $plainPassword = bin2hex(random_bytes(5));
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
        $user->setRoles(['ROLE_CUSTOMER']);
        $contact->setAccount($user);
        $em->persist($user);
    }

    return $plainPassword;
}

private function extractPrimaryEmail(Contact $contact): ?string
{
    $emails = $contact->getEmail();
    if (!is_array($emails)) {
        return null;
    }

    foreach ($emails as $email) {
        $value = trim((string) $email);
        if ($value !== '') {
            return $value;
        }
    }

    return null;
}

}
