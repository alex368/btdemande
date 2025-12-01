<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\Contact;
use App\Entity\Opportunity;
use App\Form\ActivityType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ActivityController extends AbstractController
{
    #[Route('/activity/add/{id}', name: 'app_activity_add')]
    public function index(EntityManagerInterface $em, $id, Request $request): Response
    {


        $contact = $em->getRepository(Contact::class)->find($id);
        // ----- Formulaire Opportunity -----
        $opportunity = new Activity();
        $opportunity->setContact($contact);

        $oppForm = $this->createForm(ActivityType::class, $opportunity);
        $oppForm->handleRequest($request);

        if ($oppForm->isSubmitted() && $oppForm->isValid()) {
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


    #[Route('/activity/edit/{id}', name: 'app_activity_edit')]
    public function edit(EntityManagerInterface $em, $id, Request $request): Response
    {


        $activity = $em->getRepository(Activity::class)->find($id);

 
        // ----- Formulaire Opportunity -----
    

        $oppForm = $this->createForm(ActivityType::class, $activity);
        $oppForm->handleRequest($request);

        if ($oppForm->isSubmitted() && $oppForm->isValid()) {
            $em->persist($activity);
            $em->flush();

            $this->addFlash('success', 'Opportunity ajoutée.');
            return $this->redirectToRoute('app_contact_show', ['id' => $activity->getContact()->getId()]);
        }

        return $this->render('Activity/edit.html.twig', [
            'form' => $oppForm->createView(),
            'contact' => $activity->getContact(),
        ]);
    }


    #[Route('/activity/delete/{id}', name: 'app_activity_delete', methods: ['POST'])]
public function delete(Activity $activity, Request $request, EntityManagerInterface $em): Response
{
    $token = $request->request->get('_token');

    if ($this->isCsrfTokenValid('delete-activity-' . $activity->getId(), $token)) {
        $contactId = $activity->getContact()->getId();

        $em->remove($activity);
        $em->flush();

        $this->addFlash('success', 'Activité supprimée avec succès.');
        return $this->redirectToRoute('app_contact_show', ['id' => $contactId]);
    }

    $this->addFlash('danger', 'Token CSRF invalide.');
    return $this->redirectToRoute('app_contact_show', [
        'id' => $activity->getContact()->getId()
    ]);
}


    #[Route('/activity/show/{id}', name: 'app_activity_show')]
public function show(Activity $activity): Response
{
    return $this->render('activity/show.html.twig', [
        'activity' => $activity,
    ]);
}

}
