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
#[Route('/request/new/{id}/{user}', name: 'app_funding_request')]
public function new(
    Request $request,
    EntityManagerInterface $em,
    int $id,
    int $user,
    MailerService $mailerService
): Response {

$clientId = $user;
    $fundingRequest = new FundingRequest();

    $form = $this->createForm(FundingRequestType::class, $fundingRequest);
    $form->handleRequest($request);

    $campany = $em->getRepository(Campany::class)->findOneById($id);

    if ($form->isSubmitted() && $form->isValid()) {
        $fundingRequest->setCampany($campany);
        $fundingRequest->setStatus(FundingRequest::STATUS_IN_PROGRESS);
        if (null === $fundingRequest->getCreatedAt()) {
            $fundingRequest->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
        }
        
        $em->persist($fundingRequest);
        $em->flush();

        // ✅ Envoi de mail aux clients de la société
        

        if ($campany) {
            foreach ($campany->getCustomer() as $client) {
                $clientId = $client->getId();

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

        $assistant = $fundingRequest->getAssistant();
        if ($assistant instanceof User && $assistant->getEmail()) {
            $mailerService->send(
                $assistant->getEmail(),
                'Vous avez été ajouté comme assistant sur un dossier',
                'emails/funding_created_collaborator.html.twig',
                [
                    'user' => $assistant,
                    'request' => $fundingRequest,
                ]
            );
        }

        $this->addFlash('success', 'Demande de financement enregistrée avec succès.');

        return $this->redirectToRoute('app_campany', ['id' => $id, 'user' => $clientId]);
    }

    return $this->render('funding_request/index.html.twig', [
        'form' => $form->createView(),
        'campany' => $campany,
        'user' => $clientId,
    ]);
}

}
