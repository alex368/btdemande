<?php

namespace App\Controller;

use App\Entity\FundingRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StatusDemandeController extends AbstractController
{
    #[Route('/status/demande/{id}/{user}', name: 'app_status_demande')]
    public function index(
        FundingRequest $fundingRequest,
        EntityManagerInterface $em,
        Request $request
    ): Response
    {
        if ($request->isMethod('POST')) {
            $action   = $request->request->get('action');
            $status   = FundingRequest::normalizeStatus((string) $request->request->get('status'));
            $decision = $request->request->get('decision');
            $comment  = $request->request->get('comment');

            if ($action === 'save') {
                if (!in_array($status, FundingRequest::getBackOfficeStatusChoices(), true)) {
                    $this->addFlash('danger', 'Statut de dossier invalide.');

                    return $this->redirectToRoute('app_status_demande', [
                        'id' => $fundingRequest->getId(),
                        'user' => $fundingRequest->getUser()->getId(),
                    ]);
                }

                $fundingRequest->setStatus($status);
                $fundingRequest->setComment($comment);

                if ($status !== FundingRequest::STATUS_CLOSED) {
                    $fundingRequest->setDecision(null);
                } elseif ($decision === 'validate') {
                    $fundingRequest->setDecision(FundingRequest::DECISION_VALIDATED);
                } elseif ($decision === 'refuse') {
                    $fundingRequest->setDecision(FundingRequest::DECISION_REFUSED);
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
            'statusOptions' => FundingRequest::getBackOfficeStatusChoices(),
            'closedStatus' => FundingRequest::STATUS_CLOSED,
        ]);
    }
}
