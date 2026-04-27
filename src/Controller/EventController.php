<?php

namespace App\Controller;

use App\Entity\EventCustomer;
use App\Form\EventCustomerType;
use App\Repository\EventCustomerRepository;
use App\Repository\UserRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
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
    EventCustomerRepository $repository,
    UserRepository $userRepository
): Response {
    $event = $repository->findOneBy(['slug' => $slug]);

    if (!$event) {
        throw $this->createNotFoundException('Événement introuvable.');
    }

    $rawMembers = array_merge(
        $userRepository->findByRole('ROLE_SUPER_ADMIN'),
        $userRepository->findByRole('ROLE_ADMIN'),
        $userRepository->findByRole('ROLE_COLLABORATOR')
    );
    $teamMembers = [];
    $seenIds = [];
    foreach ($rawMembers as $member) {
        $memberId = (int) $member->getId();
        if ($memberId <= 0 || isset($seenIds[$memberId])) {
            continue;
        }

        $seenIds[$memberId] = true;
        $teamMembers[] = $member;
    }

    return $this->render('event/show.html.twig', [
        'event' => $event,
        'teamMembers' => $teamMembers,
    ]);
}

    #[Route('/{id}/invite-members', name: 'app_event_invite_members', methods: ['POST'])]
    public function inviteMembers(
        EventCustomer $event,
        Request $request,
        UserRepository $userRepository,
        MailerService $mailerService
    ): Response {
        if (
            !$this->isGranted('ROLE_COLLABORATOR')
            && !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_SUPER_ADMIN')
        ) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if (!$this->isCsrfTokenValid('invite-members-' . $event->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_event_show', ['slug' => $event->getSlug()]);
        }

        $memberIds = array_map('intval', (array) $request->request->all('member_ids'));
        $memberIds = array_values(array_filter($memberIds, static fn (int $id): bool => $id > 0));

        if ($memberIds === []) {
            $this->addFlash('warning', 'Sélectionnez au moins un membre de l’équipe.');
            return $this->redirectToRoute('app_event_show', ['slug' => $event->getSlug()]);
        }

        $rawCandidates = array_merge(
            $userRepository->findByRole('ROLE_SUPER_ADMIN'),
            $userRepository->findByRole('ROLE_ADMIN'),
            $userRepository->findByRole('ROLE_COLLABORATOR')
        );
        $allCandidates = [];
        $seenIds = [];
        foreach ($rawCandidates as $candidate) {
            $candidateId = (int) $candidate->getId();
            if ($candidateId <= 0 || isset($seenIds[$candidateId])) {
                continue;
            }

            $seenIds[$candidateId] = true;
            $allCandidates[] = $candidate;
        }

        $sentCount = 0;
        $errors = 0;

        foreach ($allCandidates as $candidate) {
            if (!in_array((int) $candidate->getId(), $memberIds, true)) {
                continue;
            }

            $email = trim((string) $candidate->getEmail());
            if ($email === '') {
                continue;
            }

            try {
                $mailerService->send(
                    $email,
                    'Invitation meeting: ' . (string) $event->getTitle(),
                    'emails/event_invitation.html.twig',
                    [
                        'event' => $event,
                        'member' => $candidate,
                    ]
                );
                ++$sentCount;
            } catch (\Throwable) {
                ++$errors;
            }
        }

        if ($sentCount > 0 && $errors === 0) {
            $this->addFlash('success', 'Invitation envoyée à ' . $sentCount . ' membre(s) de l’équipe.');
        } elseif ($sentCount > 0) {
            $this->addFlash('warning', 'Invitation partiellement envoyée (' . $sentCount . ' envois, ' . $errors . ' erreur(s)).');
        } else {
            $this->addFlash('danger', 'Aucune invitation n’a pu être envoyée.');
        }

        return $this->redirectToRoute('app_event_show', ['slug' => $event->getSlug()]);
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
