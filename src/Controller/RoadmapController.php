<?php

namespace App\Controller;

use App\Entity\Campany;
use App\Entity\Roadmap;
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

final class RoadmapController extends AbstractController
{


    #[Route('/roadmap/{id}/{user}', name: 'app_roadmap')]
    public function index(EntityManagerInterface $em, QuarterService $quarterService, int $id,$user): Response
    {
        $campany = $em->getRepository(Campany::class)->findOneById($id);

        if (!$campany) {
            throw $this->createNotFoundException("Entreprise #{$id} introuvable.");
        }

        $roadmaps = $em->getRepository(Roadmap::class)->findBy(
            ['campany' => $campany],
            ['date' => 'ASC']
        );

        return $this->render('roadmap/index.html.twig', [
            'campanies' => $campany,
            'roadmaps'  => $this->buildRoadmapsWithQuarter($roadmaps, $quarterService),
            'user'      => $user,
        ]);
    }



    #[Route('/roadmap/new/{id}/{user}', name: 'app_new_roadmap')]
    public function multiRoadmap(Request $request, EntityManagerInterface $em, int $id,int $user): Response
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
                    $roadmap->setCampany($campany);

                    $em->persist($roadmap);
                }
            }

            $em->flush();

            $this->addFlash('success', 'Les roadmaps ont été enregistrées avec succès !');

            return $this->redirectToRoute('app_roadmap', ['id' => $campany->getId(), 'user' => $user]);
        }

        return $this->render('roadmap/add.html.twig', [
            'form' => $form->createView(),
            'campany' => $campany,
            'user' => $user,
        ]);
    }

    #[Route('/roadmap/edit/{id}', name: 'app_edit_roadmap')]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $roadmap = $em->getRepository(Roadmap::class)->find($id);

        if (!$roadmap) {
            throw $this->createNotFoundException("Roadmap introuvable");
        }

        $form = $this->createForm(RoadmapType::class, $roadmap);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Roadmap modifiée avec succès !');

            /** @var \App\Entity\User $currentUser */
            $currentUser = $this->getUser();

            return $this->redirectToRoute('app_roadmap', [
                'id'   => $roadmap->getCampany()->getId(),
                'user' => $currentUser->getId(),
            ]);
        }

        return $this->render('roadmap/edit.html.twig', [
            'form'    => $form->createView(),
            'roadmap' => $roadmap,
        ]);
    }

    #[Route('/roadmap/delete/{id}', name: 'app_delete_roadmap', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $roadmap = $em->getRepository(Roadmap::class)->find($id);

        if (!$roadmap) {
            throw $this->createNotFoundException("Roadmap introuvable.");
        }

        if (!$this->isCsrfTokenValid('delete_roadmap_' . $id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $campanyId = $roadmap->getCampany()->getId();

        $em->remove($roadmap);
        $em->flush();

        $this->addFlash('success', 'Roadmap supprimée avec succès.');

        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        return $this->redirectToRoute('app_roadmap', [
            'id'   => $campanyId,
            'user' => $currentUser->getId(),
        ]);
    }

    #[Route('/roadmap/{id}/export', name: 'app_roadmap_export')]
    public function exportRoadmap(EntityManagerInterface $em, QuarterService $quarterService, int $id): Response
    {
        $campany = $em->getRepository(Campany::class)->find($id);

        if (!$campany) {
            throw $this->createNotFoundException("Entreprise #{$id} introuvable.");
        }

        $roadmaps = $em->getRepository(Roadmap::class)->findBy(
            ['campany' => $campany],
            ['date' => 'ASC']
        );

        $html = $this->renderView('roadmap/export.html.twig', [
            'user'     => $campany,
            'roadmaps' => $this->buildRoadmapsWithQuarter($roadmaps, $quarterService),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setIsRemoteEnabled(true);

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

    private function buildRoadmapsWithQuarter(array $roadmaps, QuarterService $quarterService): array
    {
        $result = [];
        foreach ($roadmaps as $roadmap) {
            $result[] = [
                'entity'  => $roadmap,
                'quarter' => $quarterService->getQuarter($roadmap->getDate()),
            ];
        }
        return $result;
    }
}
