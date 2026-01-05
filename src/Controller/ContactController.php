<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\Contact;
use App\Entity\Opportunity;
use App\Entity\Quote;
use App\Entity\User;
use App\Form\ActivityType;
use App\Form\ContactType;
use App\Form\ImportContactsType;
use App\Form\OpportunityType;
use App\Repository\ContactRepository;
use App\Service\ContactConverterService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
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
        $search = $request->query->get('q');

        $qb = $contactRepository->createQueryBuilder('c');

        if ($search) {
            $qb
                ->where('c.firstName LIKE :search')
                ->orWhere('c.lastName LIKE :search')
                ->orWhere('c.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $qb->orderBy('c.lastName', 'DESC');

        $contacts = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('contact/index.html.twig', [
            'contacts' => $contacts,
        ]);
    }

    #[Route('/contact/add', name: 'app_contact_add')]
    public function add(EntityManagerInterface $em, Request $request): Response
    {

        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $logoFile */
            $em->persist($contact);
            $em->flush();

            $this->addFlash('success', 'contact created successfully.');

            return $this->redirectToRoute('app_contact');
        }
        return $this->render('contact/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/contact/{id}/edit', name: 'app_contact_edit')]
    public function edit(Contact $contact, Request $request, EntityManagerInterface $em): Response
    {
        // Contact est automatiquement récupéré grâce au ParamConverter

        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->flush();

            $this->addFlash('success', 'Le contact a été mis à jour.');

            return $this->redirectToRoute('app_contact'); // page liste
        }

        return $this->render('contact/edit.html.twig', [
            'form' => $form->createView(),
            'contact' => $contact,
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
    public function show(Contact $contact): Response
    {

        return $this->render('contact/show.html.twig', [
            'contact' => $contact,
            'opportunities' => $contact->getOpportunity(),
            'activities' => $contact->getActivities(),
            'quotes' => $contact->getQuotes(),
        ]);
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

    // Vérifier qu'un User n'existe pas déjà
    if (!empty($contact->getEmail()) && is_array($contact->getEmail())) {
        $existing = $em->getRepository(User::class)->findOneBy([
            'email' => $contact->getEmail()[0]
        ]);

        if ($existing) {
            $this->addFlash('warning', 'Un utilisateur existe déjà pour cet email.');
            return $this->redirectToRoute('app_contact_show', ['id' => $contact->getId()]);
        }
    }

    // 1️⃣ Créer un User à partir du Contact
    $user = $contact->toUser();

    // 2️⃣ Mot de passe réel
    $hashedPassword = $passwordHasher->hashPassword($user, 'ChangeMe123!');
    $user->setPassword($hashedPassword);

    // 3️⃣ Lier Contact → User si la méthode existe
    if (method_exists($user, 'setContact')) {
        $user->setContact($contact);
    }

    // 4️⃣ Persister et flush pour créer l'user (accountId du user est déjà positionné dans toUser())
    $em->persist($user);
    $em->flush();

    //assignation du CRM
    $contact->setAccount($user);
    $em->flush();
   

    $this->addFlash('success', 'Le contact a été converti en utilisateur !');

    return $this->redirectToRoute('app_contact_show', [
        'id' => $contact->getId(),
    ]);
}



}
