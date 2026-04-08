<?php

namespace App\Controller;

use App\Entity\Funder;
use App\Entity\FundingMechanism;
use App\Form\FunderType;
use App\Form\FundingMechanismType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class FunderController extends AbstractController
{
#[Route('/funder', name: 'app_funder')]
public function index(
    Request $request, 
    EntityManagerInterface $em, 
    PaginatorInterface $paginator
): Response {
    $search = trim((string) $request->query->get('q', ''));
    $sector = trim((string) $request->query->get('sector', ''));
    $type = trim((string) $request->query->get('type', ''));
    $projectType = trim((string) $request->query->get('projectType', ''));

    $repository = $em->getRepository(FundingMechanism::class);

    $query = $repository
        ->createQueryBuilder('f')
        ->orderBy('f.id', 'DESC');

    if ($search !== '') {
        $query
            ->andWhere('f.name LIKE :search OR f.description LIKE :search')
            ->setParameter('search', '%' . $search . '%');
    }

    if ($sector !== '') {
        $query
            ->andWhere('f.sector = :sector')
            ->setParameter('sector', $sector);
    }

    if ($type !== '') {
        $query
            ->andWhere('f.type = :type')
            ->setParameter('type', $type);
    }

    if ($projectType !== '') {
        $query
            ->andWhere('f.projectType LIKE :projectType')
            ->setParameter('projectType', '%' . $projectType . '%');
    }

    $funders = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        10
    );

    $funders->setParam('q', $search);
    $funders->setParam('sector', $sector);
    $funders->setParam('type', $type);
    $funders->setParam('projectType', $projectType);

    $sectorChoices = $repository
        ->createQueryBuilder('f')
        ->select('DISTINCT f.sector')
        ->where('f.sector IS NOT NULL')
        ->andWhere("f.sector <> ''")
        ->orderBy('f.sector', 'ASC')
        ->getQuery()
        ->getSingleColumnResult();

    $typeChoices = $repository
        ->createQueryBuilder('f')
        ->select('DISTINCT f.type')
        ->where('f.type IS NOT NULL')
        ->andWhere("f.type <> ''")
        ->orderBy('f.type', 'ASC')
        ->getQuery()
        ->getSingleColumnResult();

    $projectTypeChoices = $repository
        ->createQueryBuilder('f')
        ->select('DISTINCT f.projectType')
        ->where('f.projectType IS NOT NULL')
        ->andWhere("f.projectType <> ''")
        ->orderBy('f.projectType', 'ASC')
        ->getQuery()
        ->getSingleColumnResult();

    return $this->render('funder/index.html.twig', [
        'funders' => $funders,
        'search' => $search,
        'selectedSector' => $sector,
        'selectedType' => $type,
        'selectedProjectType' => $projectType,
        'sectorChoices' => $sectorChoices,
        'typeChoices' => $typeChoices,
        'projectTypeChoices' => $projectTypeChoices,
    ]);
}
    #[Route('/funder/add', name: 'app_funder_add')]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $funder = new FundingMechanism();
        $form = $this->createForm(FundingMechanismType::class, $funder);
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

        $funder->setLogo($newFilename);
    }
            $em->persist($funder);
            $em->flush();

            $this->addFlash('success', 'Funder created successfully.');

            return $this->redirectToRoute('app_funder');
        }

        return $this->render('funder/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }


#[Route('/funder/{id}/edit', name: 'app_funder_edit')]
public function edit(
    Request $request, 
    FundingMechanism $funder, 
    EntityManagerInterface $em
): Response {

    // Sauvegarde du logo actuel
    $oldLogo = $funder->getLogo();

    $form = $this->createForm(FundingMechanismType::class, $funder);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        /** @var UploadedFile $logoFile */
        $logoFile = $form->get('logo')->getData();

        if ($logoFile) {

            $newFilename = uniqid('logo_', true) . '.' . $logoFile->guessExtension();

            try {
                $logoFile->move(
                    $this->getParameter('logos_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                throw new \RuntimeException('Erreur lors du téléchargement du logo.');
            }

            // Mise à jour du nouveau logo
            $funder->setLogo($newFilename);

        } else {
            // Si aucun nouveau logo n'est uploadé → garder l'ancien
            $funder->setLogo($oldLogo);
        }

        $em->flush();

        $this->addFlash('success', 'Funder updated successfully.');

        return $this->redirectToRoute('app_funder');
    }

    return $this->render('funder/edit.html.twig', [
        'form' => $form->createView(),
        'funder' => $funder,
    ]);
}


    #[Route('/funder/show/{id}', name: 'app_funder_show')]
public function show(
     $id, 
    Request $request, 
    EntityManagerInterface $em, 
    PaginatorInterface $paginator,
): Response {


    $funders = $em->getRepository(FundingMechanism::class)->find($id);

    return $this->render('funder/show.html.twig', [
        'funders' => $funders,
    ]);
}

#[Route('/funder/{id}/delete', name: 'app_funder_delete', methods: ['POST'])]
public function delete(Request $request, FundingMechanism $funder, EntityManagerInterface $em): Response
{
    if ($this->isCsrfTokenValid('delete' . $funder->getId(), $request->request->get('_token'))) {

        $em->remove($funder);
        $em->flush();
    }

    return $this->redirectToRoute('app_funder');
}


}
