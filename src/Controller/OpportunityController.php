<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\Opportunity;
use App\Form\OpportunityType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OpportunityController extends AbstractController
{
    #[Route('/opportunity/{id}', name: 'app_opportunity_add')]
public function index(EntityManagerInterface $em, $id, Request $request): Response
{
    $user = $this->getUser();
    $contact = $em->getRepository(Contact::class)->find($id);

    if (!$contact) {
        throw $this->createNotFoundException("Contact introuvable");
    }

    // ----- Formulaire Opportunity -----
    $opportunity = new Opportunity();
    $opportunity->setContact($contact);

    // Définition de l'heure Europe/Paris
    $parisTz = new \DateTimeZone('Europe/Paris');
    $opportunity->setCreatedAt(new \DateTimeImmutable('now', $parisTz));

    $oppForm = $this->createForm(OpportunityType::class, $opportunity);
    $oppForm->handleRequest($request);

    if ($oppForm->isSubmitted() && $oppForm->isValid()) {

        $opportunity->setUser($user);

        $em->persist($opportunity);
        $em->flush();

        $this->addFlash('success', 'Opportunity ajoutée.');
        return $this->redirectToRoute('app_contact_show', ['id' => $contact->getId()]);
    }

    return $this->render('opportunity/index.html.twig', [
        'form' => $oppForm->createView(),
        'contact' => $contact,
    ]);
}



    #[Route('/opportunity/edit/{id}', name: 'app_opportunity_edit')]
    public function edit(EntityManagerInterface $em, $id, Request $request): Response
    {
        $opportunity = $em->getRepository(Opportunity::class)->find($id);

        $oppForm = $this->createForm(OpportunityType::class, $opportunity);
        $oppForm->handleRequest($request);

        if ($oppForm->isSubmitted() && $oppForm->isValid()) {
            $em->persist($opportunity);
            $em->flush();
            $this->addFlash('success', 'Opportunity ajoutée.');
            return $this->redirectToRoute('app_contact_show', ['id' => $opportunity->getContact()->getId()]);
        }

        return $this->render('opportunity/edit.html.twig', [
            'form' => $oppForm->createView(),
            'contact' => $opportunity->getContact(),
        ]);
    }

    #[Route('/opportunity/delete/{id}', name: 'app_opportunity_delete', methods: ['POST'])]
    public function delete(Opportunity $opportunity, Request $request, EntityManagerInterface $em): Response
    {
        $token = $request->request->get('_token');

        if ($this->isCsrfTokenValid('delete-opportunity-' . $opportunity->getId(), $token)) {

            $contactId = $opportunity->getContact()->getId();

            $em->remove($opportunity);
            $em->flush();

            $this->addFlash('success', 'Opportunité supprimée avec succès.');
            return $this->redirectToRoute('app_contact_show', ['id' => $contactId]);
        }

        $this->addFlash('danger', 'Token CSRF invalide.');
        return $this->redirectToRoute('app_contact_show', [
            'id' => $opportunity->getContact()->getId()
        ]);
    }
    #[Route('/opportunity/show/{id}', name: 'app_opportunity_show')]
    public function show(Opportunity $opportunity): Response
    {
        return $this->render('opportunity/show.html.twig', [
            'opportunity' => $opportunity,
        ]);
    }
}
