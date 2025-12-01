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

    // Récupération de la query (query builder recommandé)
    $query = $em->getRepository(FundingMechanism::class)
                ->createQueryBuilder('f')   // alias f
                ->orderBy('f.id', 'DESC'); // optionnel

    // Pagination
    $funders = $paginator->paginate(
        $query,                                // Query, pas findAll()
        $request->query->getInt('page', 1),    // Page courante
        10                                     // Nombre d’éléments par page
    );

    return $this->render('funder/index.html.twig', [
        'funders' => $funders,
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
