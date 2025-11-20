<?php

namespace App\Controller;

use App\Entity\Campany;
use App\Entity\FundingRequest;
use App\Entity\User;
use App\Form\FundingRequestType;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FundingRequestController extends AbstractController
{
    #[Route('/funding')]
#[Route('/request/new/{id}', name: 'app_funding_request')]
public function new(
    Request $request,
    EntityManagerInterface $em,
    int $id,
    MailerService $mailerService
): Response {
    $fundingRequest = new FundingRequest();

    $form = $this->createForm(FundingRequestType::class, $fundingRequest);
    $form->handleRequest($request);

    $campany = $em->getRepository(Campany::class)->findOneById($id);

    if ($form->isSubmitted() && $form->isValid()) {
        $fundingRequest->setCampany($campany);
        $fundingRequest->setStatus('En cours');
        
        $em->persist($fundingRequest);
        $em->flush();

        // ✅ Envoi de mail aux clients de la société
        if ($campany) {
            foreach ($campany->getCustomer() as $client) {
                if ($client->getEmail()) {
                    $mailerService->send(
                        $client->getEmail(),
                        'Nouvelle demande de financement créée',
                        'emails/funding_created.html.twig',
                        [
                            'client'  => $client,
                            'request' => $fundingRequest,
                        ]
                    );
                }
            }
        }

        // ✅ Envoi de mail au collaborateur connecté
        $collaborator = $this->getUser();
        if ($collaborator instanceof User && $collaborator->getEmail()) {
            $mailerService->send(
                $collaborator->getEmail(),
                'Vous avez créé une nouvelle demande de financement',
                'emails/funding_created_collaborator.html.twig',
                [
                    'user'    => $collaborator,
                    'request' => $fundingRequest,
                ]
            );
        }

        $this->addFlash('success', 'Demande de financement enregistrée avec succès.');

        return $this->redirectToRoute('app_campany', ['id' => $id , 'user' => $campany->getCustomer()->first()->getId()]);
    }

    return $this->render('funding_request/index.html.twig', [
        'form' => $form->createView(),
    ]);
}

}
