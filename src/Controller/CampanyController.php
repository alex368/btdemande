<?php

namespace App\Controller;

use App\Entity\Campany;
use App\Entity\FundingRequest;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CampanyController extends AbstractController
{
    #[Route('/me/campany/{id}', name: 'app_me_campany')]
    public function index(EntityManagerInterface $em,$id): Response
    {
          $user = $em->getRepository(User::class)->find($id);
        // Force la récupération des campanies liées
        $campanies = $user->getCampanies();
     
        return $this->render('campany/index.html.twig', [
             'users' => $user,
            'campanies' => $campanies,
        ]);
    }



    #[Route('/me/campany/{id}/{user}', name: 'app_me_campany_datasheet')]
    public function campanyDatasheet(int $id, int $user,EntityManagerInterface $em): Response
    {

        $campanies = $em->getRepository(Campany::class)->find($id);

        $requestDemand = $em->getRepository(FundingRequest::class)->findBy(['campany'=>$campanies]);


        return $this->render('campany/campany_detail.html.twig', [
            'campanies' => $campanies,
            'requestDemands' => $requestDemand,
            'user' => $user
        ]);
    }
}
