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
        $findRequest = $em->getRepository(FundingRequest::class)->findByUser($user);


        return $this->render('customer_folder/index.html.twig', [
            'findRequest' => $findRequest,
        ]);
    }
}
