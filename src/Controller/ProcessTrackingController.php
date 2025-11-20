<?php

namespace App\Controller;

use App\Entity\Campany;
use App\Entity\FundingRequest;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProcessTrackingController extends AbstractController
{
    #[Route('/process/tracking/{id}', name: 'app_process_tracking')]
    public function index(int $id, EntityManagerInterface $em): Response
    {

        // Récupérer l'utilisateur
        $user = $em->getRepository(User::class)->find($id);

        // Vérification de l'existence de l'utilisateur
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        // Récupérer les campanies liées
        $campanies = $user->getCampanies(); // ou ->getCompanies() si c’est le bon nom

        // Construire un tableau avec chaque campany + ses FundingRequests
        $campanyTrackingData = [];

        foreach ($campanies as $campany) {
    $requests = $em->getRepository(FundingRequest::class)->findBy([
        'campany' => $campany,
    ]);


    if (count($requests) === 0) {
        continue; // ⚠️ on saute cette campany si aucune demande
    }

    $campanyTrackingData[] = [
        'campany'  => $campany,
        'requests' => $requests,
    ];
}


        // ⚠️ On sort du foreach et on fait le render ici
        return $this->render('process_tracking/index.html.twig', [
            'trackingData' => $campanyTrackingData, // c’est ce qu’on a construit
        ]);
    }


    #[Route('/client/request/{id}/tracking', name: 'client_request_tracking')]
public function tracking(FundingRequest $fundingRequest): Response
{
    return $this->render('process_tracking/tracking.html.twig', [
        'request' => $fundingRequest,
    ]);
}

}

