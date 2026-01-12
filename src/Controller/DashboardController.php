<?php

namespace App\Controller;

use App\Entity\FundingRequest;
use App\Entity\User;
use App\Repository\EventCustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(EventCustomerRepository $eventRepository, EntityManagerInterface $em): Response
    {


$tz = new \DateTimeZone('Europe/Paris');

$todayStart = new \DateTimeImmutable('today', $tz);
$tomorrowStart = $todayStart->modify('+1 day');

$eventsToday = $eventRepository->createQueryBuilder('e')
    ->andWhere('e.startDate < :tomorrowStart')
    ->andWhere('e.endDate >= :todayStart')
    ->setParameter('todayStart', $todayStart)
    ->setParameter('tomorrowStart', $tomorrowStart)
    ->orderBy('e.startDate', 'ASC')
    ->getQuery()
    ->getResult();




        


      $requests = $em->getRepository(FundingRequest::class)->findBy(
        [],
        ['id' => 'DESC'], // ⚠️ champ date requis
        2                          // ⬅️ seulement les 2 derniers
    );


        return $this->render('dashboard/index.html.twig', [
            'events' => $eventsToday,
            'requests' => $requests,
        ]);
    }
}
