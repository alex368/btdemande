<?php

namespace App\Controller;

use App\Entity\EventCustomer;
use App\Form\EventCustomerType;
use App\Repository\EventCustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/event')]
class EventController extends AbstractController
{
    #[Route('', name: 'app_event_index', methods: ['GET'])]
    public function index(EventCustomerRepository $repository): Response
    {
        return $this->render('event/index.html.twig', [
            'events' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $event = new EventCustomer();
        $form = $this->createForm(EventCustomerType::class, $event);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($event);
            $em->flush();

            return $this->redirectToRoute('app_event_index');
        }

        return $this->render('event/new.html.twig', [
            'form' => $form,
        ]);
    }

#[Route('/{slug}', name: 'app_event_show', methods: ['GET'])]
public function show(
    string $slug,
    EventCustomerRepository $repository
): Response {
    $event = $repository->findOneBy(['slug' => $slug]);

    if (!$event) {
        throw $this->createNotFoundException('Event not found');
    }

    return $this->render('event/show.html.twig', [
        'event' => $event,
    ]);
}


    #[Route('/{id}/edit', name: 'app_event_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EventCustomer $event,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(EventCustomerType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_event_index');
        }

        return $this->render('event/edit.html.twig', [
            'form' => $form,
            'event' => $event,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_event_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        EventCustomer $event,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {
            $em->remove($event);
            $em->flush();
        }

        return $this->redirectToRoute('app_event_index');
    }

    
}
