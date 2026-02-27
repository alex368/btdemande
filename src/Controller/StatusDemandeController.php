<?php

namespace App\Controller;

use App\Entity\FundingRequest;
use App\Entity\User;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StatusDemandeController extends AbstractController
{
    #[Route('/status/demande/{id}/{user}', name: 'app_status_demande')]
    public function index( MailerService $mailerService, FundingRequest $fundingRequest, EntityManagerInterface $em,
        Request $request,int $id, int $user): Response
    {

if ($request->isMethod('POST')) {

    $action   = $request->request->get('action');
    $status   = $request->request->get('status');
    $decision = $request->request->get('decision');
    $comment  = $request->request->get('comment');




    if ($action === 'save') {

        $fundingRequest->setStatus($status);

        // si tu as un champ commentaire
        $fundingRequest->setComment($comment);

        if ($decision === 'validate') {
            $fundingRequest->setDecision('Validé');
        }

        if ($decision === 'refuse') {
            $fundingRequest->setDecision('Refusé');
        }

        $em->flush();

        return $this->redirectToRoute('app_dashboard');
    }

    if ($action === 'cancel') {
        return $this->redirectToRoute('app_dashboard');
    }
}
  
        return $this->render('status_demande/index.html.twig', [
            'fundingRequest' => $fundingRequest,
        ]);
    }
}
