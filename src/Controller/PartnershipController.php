<?php

namespace App\Controller;

use App\Entity\Partnership;
use App\Entity\User;
use App\Form\ImportpartnershipsType;
use App\Form\PartnershipType;
use App\Repository\PartnershipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class PartnershipController extends AbstractController
{
  


    #[Route('/partnership', name: 'app_partnership')]
    public function index(PartnershipRepository $partnershipRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $structure = trim((string) $request->query->get('structure', ''));
        $occupation = trim((string) $request->query->get('occupation', ''));

        $qb = $partnershipRepository->createQueryBuilder('c')
            ->leftJoin('c.fundingMechanism', 'fm')
            ->addSelect('fm');

        if ($search !== '') {
            $qb
                ->where('c.firstname LIKE :search')
                ->orWhere('c.lastname LIKE :search')
                ->orWhere('c.occupation LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($structure !== '') {
            $qb
                ->andWhere('fm.name LIKE :structure')
                ->setParameter('structure', '%' . $structure . '%');
        }

        if ($occupation !== '') {
            $qb
                ->andWhere('c.occupation LIKE :occupation')
                ->setParameter('occupation', '%' . $occupation . '%');
        }

        $qb->orderBy('c.lastname', 'DESC');

        $partnerships = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('partnership/index.html.twig', [
            'partnerships' => $partnerships,
            'q' => $search,
            'structure' => $structure,
            'occupation' => $occupation,
        ]);
    }

    #[Route('/partnership/add', name: 'app_partnership_add')]
    public function add(EntityManagerInterface $em, Request $request): Response
    {

        $partnership = new Partnership();
        $form = $this->createForm(PartnershipType::class, $partnership);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $logoFile */
            $em->persist($partnership);
            $em->flush();

            $this->addFlash('success', 'partnership created successfully.');

            return $this->redirectToRoute('app_partnership');
        }
        return $this->render('partnership/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/partnership/{id}/edit', name: 'app_partnership_edit')]
    public function edit(partnership $partnership, Request $request, EntityManagerInterface $em): Response
    {
        // partnership est automatiquement récupéré grâce au ParamConverter

        $form = $this->createForm(partnershipType::class, $partnership);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->flush();

            $this->addFlash('success', 'Le partnership a été mis à jour.');

            return $this->redirectToRoute('app_partnership'); // page liste
        }

        return $this->render('partnership/edit.html.twig', [
            'form' => $form->createView(),
            'partnership' => $partnership,
        ]);
    }


    #[Route('/partnership/{id}/delete', name: 'app_partnership_delete', methods: ['POST'])]
    public function delete(partnership $partnership, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete-partnership-' . $partnership->getId(), $request->request->get('_token'))) {

            $em->remove($partnership);
            $em->flush();

            $this->addFlash('success', 'Le partnership a bien été supprimé.');
        }

        return $this->redirectToRoute('app_partnership');
    }

    #[Route('/partnership/{id}', name: 'app_partnership_show', methods: ['GET'])]
    public function show(partnership $partnership): Response
    {

        return $this->render('partnership/show.html.twig', [
            'partnership' => $partnership,
            // 'opportunities' => $partnership->getOpportunity(),
            // 'activities' => $partnership->getActivities(),
            // 'quotes' => $partnership->getQuotes(),
        ]);
    }

    #[Route('/partnerships/import', name: 'app_partnership_import')]
    public function import(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ImportpartnershipsType::class);
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
                $partnership = new partnership();

                // Informations de base
                // $partnership->setSalutation($data['salutation'] ?? null);
                $partnership->setLastName($data['lastName'] ?? null);
                $partnership->setFirstName($data['firstName'] ?? null);

                // Emails
                $emails = [];
                foreach ($data as $key => $value) {
                    if (str_starts_with($key, 'email') && $value) {
                        $emails[] = $value;
                    }
                }
                // $partnership->setEmail(array_unique($emails));

                // Téléphones
                $phones = [];
                foreach ($data as $key => $value) {
                    if (str_starts_with($key, 'phone') && $value) {
                        $phones[] = $value;
                    }
                }
                // $partnership->setPhone(array_unique($phones));

                // Mobiles
                $mobiles = [];
                foreach ($data as $key => $value) {
                    if (str_starts_with($key, 'mobile') && $value) {
                        $mobiles[] = $value;
                    }
                }
                // $partnership->setMobilePhone(array_unique($mobiles));

                // Réseaux sociaux
                $socialMedia = [];
                foreach ($data as $key => $value) {
                    if (str_starts_with($key, 'socialMedia') && $value) {
                        $socialMedia[] = $value;
                    }
                }
                // $partnership->setSocialMedia(array_unique($socialMedia));

                // Champs simples
                // $partnership->setCountry($data['country'] ?? null);
                // $partnership->setAdress($data['address'] ?? null);
                // $partnership->setCity($data['city'] ?? null);
                // $partnership->setWebsite($data['website'] ?? null);
                // $partnership->setOccupation($data['occupation'] ?? null);
                // $partnership->setZipCode($data['zipCode'] ?? null);

                // Enregistrement
                $em->persist($partnership);
            }

            $em->flush();

            $this->addFlash('success', 'Import terminé avec succès !');
            return $this->redirectToRoute('app_partnership');
        }

        return $this->render('partnership/import.html.twig', [
            'form' => $form->createView(),
        ]);
    }

}
