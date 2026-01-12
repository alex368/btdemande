<?php

namespace App\Controller;

use App\Entity\Campany;
use App\Entity\Roadmap;
use App\Entity\User;
use App\Form\MultiroadmapType;
use App\Form\RoadmapType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\QuarterService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Knp\Snappy\Pdf as SnappyPdf;
use PhpOffice\PhpSpreadsheet\Writer\Pdf;

final class RoadmapController extends AbstractController
{


    #[Route('/roadmap/{id}', name: 'app_roadmap')]
    public function index(EntityManagerInterface $em, QuarterService $quarterService, int $id): Response
    {


        $campany = $em->getRepository(Campany::class)->findOneById($id);

        $roadmaps = $em->getRepository(Roadmap::class)->findBy(
            ['campany' => $campany],
            ['date' => 'ASC'] // tri croissant
        );


        // On prépare un tableau enrichi avec trimestre
        $roadmapsWithQuarter = [];

        foreach ($roadmaps as $roadmap) {
            $roadmapsWithQuarter[] = [
                'entity'   => $roadmap,
                'quarter'  => $quarterService->getQuarter($roadmap->getDate()),
            ];
        }


        return $this->render('roadmap/index.html.twig', [
            'user' => $campany,
            'roadmaps' => $roadmapsWithQuarter,
        ]);
    }



    #[Route('/roadmap/new/{id}', name: 'app_new_roadmap')]
    public function multiRoadmap(Request $request, EntityManagerInterface $em, int $id): Response
    {
        // Récupère l'utilisateur par l'ID
        $campany = $em->getRepository(Campany::class)->find($id);

        

        // Tableau contenant des Roadmap vides
        $data = ['roadmaps' => []];

        // Une roadmap par défaut
        $data['roadmaps'][] = new Roadmap();

        $form = $this->createForm(MultiroadmapType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var array $submittedRoadmaps */
            $submittedRoadmaps = $form->get('roadmaps')->getData();

            foreach ($submittedRoadmaps as $roadmap) {
                if ($roadmap instanceof Roadmap) {
                    // 🔥 On lie la roadmap à l'utilisateur
                    $roadmap->setCampany($campany);

                    $em->persist($roadmap);
                }
            }

            $em->flush();

            $this->addFlash('success', 'Les roadmaps ont été enregistrées avec succès !');

            return $this->redirectToRoute('app_roadmap', ['id' => $campany->getId()]);
        }

        return $this->render('roadmap/add.html.twig', [
            'form' => $form->createView(),
            'user' => $campany
        ]);
    }

    #[Route('/roadmap/edit/{id}', name: 'app_edit_roadmap')]
public function edit(
    int $id,
    Request $request,
    EntityManagerInterface $em
): Response
{
    $roadmap = $em->getRepository(Roadmap::class)->find($id);

    if (!$roadmap) {
        throw $this->createNotFoundException("Roadmap introuvable");
    }

    // Création du formulaire RoadmapType
    $form = $this->createForm(RoadmapType::class, $roadmap);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        $em->flush();

        $this->addFlash('success', 'Roadmap modifiée avec succès !');

        // Redirection vers la fiche entreprise de l'utilisateur
        return $this->redirectToRoute('app_roadmap', [
            'id' => $roadmap->getCampany()->getId()
        ]);
    }

    return $this->render('roadmap/edit.html.twig', [
        'form' => $form->createView(),
        'roadmap' => $roadmap,
    ]);
}

#[Route('/roadmap/delete/{id}', name: 'app_delete_roadmap', methods: ['GET'])]
public function delete(int $id, EntityManagerInterface $em): Response
{
    $roadmap = $em->getRepository(Roadmap::class)->find($id);

    if (!$roadmap) {
        throw $this->createNotFoundException("Roadmap introuvable.");
    }

    $userId = $roadmap->getCampany()->getId();

    $em->remove($roadmap);
    $em->flush();

    $this->addFlash('success', 'Roadmap supprimée avec succès.');

    return $this->redirectToRoute('app_roadmap', ['id' => $userId]);
}



#[Route('/roadmap/{id}/export', name: 'app_roadmap_export')]
public function exportRoadmap(
    EntityManagerInterface $em,
    QuarterService $quarterService,
    int $id
): Response {
    $campany = $em->getRepository(Campany::class)->find($id);

    $roadmaps = $em->getRepository(Roadmap::class)->findBy(
        ['campany' => $campany],
        ['date' => 'ASC']
    );

    $roadmapsWithQuarter = [];
    foreach ($roadmaps as $roadmap) {
        $roadmapsWithQuarter[] = [
            'entity'  => $roadmap,
            'quarter' => $quarterService->getQuarter($roadmap->getDate()),
        ];
    }

    // Générer le HTML à partir de Twig
    $html = $this->renderView('roadmap/export.html.twig', [
        'user' => $campany,
        'roadmaps' => $roadmapsWithQuarter,
    ]);



$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->setIsRemoteEnabled(true); // Important si tu utilises des images ou CSS externes

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return new Response(
        $dompdf->output(),
        200,
        [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="roadmap.pdf"',
        ]
    );
}


}
