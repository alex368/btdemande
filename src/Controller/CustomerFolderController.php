<?php

namespace App\Controller;

use App\Entity\FundingRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CustomerFolderController extends AbstractController
{
    #[Route('/customer/folder', name: 'app_customer_folder')]
    public function index(EntityManagerInterface $em): Response
    {

        $user = $this->getUser();
        $repository = $em->getRepository(FundingRequest::class);

        if ($this->isGranted('ROLE_ADMIN')) {
            $findRequests = $repository->findAll();
        } elseif ($this->isGranted('ROLE_COLLABORATOR')) {
            $findRequests = $repository->findByUser($user);
        } elseif ($this->isGranted('ROLE_CUSTOMER')) {
            $findRequests = $repository->findByUser($user);
        } else {
            $findRequests = [];
        }

        // Initialize variables
        $campany = null;
        $userCampany = null;

        // Get company and user company from first request
        if (!empty($findRequests)) {
            $firstRequest = $findRequests[0];
            $campany = $firstRequest->getCampany();
            if ($campany) {
                $userCampany = $campany->getCustomer();
            }
        }

        return $this->render('customer_folder/index.html.twig', [
            'findRequests' => $findRequests,
            'campanyId' => $campany?->getId(),
            'userCampany' => $userCampany,
        ]);
    }
}
