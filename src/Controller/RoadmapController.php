<?php

namespace App\Controller;

use App\Entity\Roadmap;
use App\Entity\User;
use App\Form\MultiroadmapType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RoadmapController extends AbstractController
{
    #[Route('/roadmap/{id}', name: 'app_roadmap')]
    public function index(EntityManagerInterface $em,$id): Response
    {
        $user = $em->getRepository(User::class)->findOneById($id);

        $roadmaps = $em->getRepository(Roadmap::class)->findByUser($user);

       

        return $this->render('roadmap/index.html.twig', [
            'user' => $user,
            'roadmaps' => $roadmaps,
        ]);
    }

 

    #[Route('/roadmap/new/{id}', name: 'app_new_roadmap')]
public function multiRoadmap(Request $request, EntityManagerInterface $em, int $id): Response
{
    // Récupère l'utilisateur par l'ID
    $user = $em->getRepository(User::class)->find($id);

    if (!$user) {
        throw $this->createNotFoundException("Utilisateur introuvable.");
    }

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
                $roadmap->setUser($user);

                $em->persist($roadmap);
            }
        }

        $em->flush();

        $this->addFlash('success', 'Les roadmaps ont été enregistrées avec succès !');

        return $this->redirectToRoute('app_roadmap', ['id' => $user->getId()]);
    }

    return $this->render('roadmap/add.html.twig', [
        'form' => $form->createView(),
        'user' => $user
    ]);
}

}
