<?php

namespace App\Controller;

use App\Entity\FundingRequest;
use App\Entity\User;
use App\Repository\EventCustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
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

        $id = $this->getUser();
        $user = $em->getRepository(User::class)->findOneById($id);




        $requests = [];


        if ($this->isGranted('ROLE_ADMIN')) {
            $requests = $em->getRepository(FundingRequest::class)->findAll();
        } elseif ($this->isGranted('ROLE_COLLABORATOR')) {
            $requests = $em->getRepository(FundingRequest::class)->findBy(
                ['user' => $user->getId()],
                ['id' => 'DESC']
            );
        } elseif ($this->isGranted('ROLE_CUSTOMER')) {
            $requestsCustomer = $em->getRepository(FundingRequest::class)
                ->createQueryBuilder('fr')
                ->join('fr.campany', 'c')
                ->join('c.customer', 'cu')
                ->where('cu = :user')
                ->setParameter('user', $this->getUser())
                ->orderBy('fr.id', 'DESC')
                ->getQuery()
                ->getResult();

            
        }







        $buildQb = function (string $type) use ($em): QueryBuilder {
            $qb = $em->getRepository(FundingRequest::class)->createQueryBuilder('fr')
                ->join('fr.product', 'p')
                ->andWhere('fr.status = :status')
                ->andWhere('p.typeProduct = :type')
                ->setParameter('status', 'Validé')
                ->setParameter('type', $type)
                ->orderBy('fr.id', 'DESC');

            if ($this->isGranted('ROLE_ADMIN')) {
                // Admin : toutes les demandes validées
            } elseif ($this->isGranted('ROLE_COLLABORATOR')) {
                $qb->andWhere('fr.user = :user')
                    ->setParameter('user', $this->getUser());
            } elseif ($this->isGranted('ROLE_CUSTOMER')) {
                $qb->join('fr.campany', 'c')
                    ->join('c.customer', 'cu')
                    ->andWhere('cu = :user')
                    ->setParameter('user', $this->getUser());
            }

            return $qb;
        };

        $subventionTotal  = array_sum(array_map(fn($fr) => $fr->getAmount(), $buildQb('Subvention')->getQuery()->getResult()));
        $pretTotal        = array_sum(array_map(fn($fr) => $fr->getAmount(), $buildQb('Pret')->getQuery()->getResult()));
        $pretHonneurTotal = array_sum(array_map(fn($fr) => $fr->getAmount(), $buildQb("Pret d'honneur")->getQuery()->getResult()));
        $totalAccorde     = $subventionTotal + $pretTotal + $pretHonneurTotal;



        return $this->render('dashboard/index.html.twig', [
            'events'           => $eventsToday,
            'requests'         => $requests,
            'requestsCustomer' => $requestsCustomer ?? [],
            'subventionTotal'  => $subventionTotal,
            'pretTotal'        => $pretTotal,
            'pretHonneurTotal' => $pretHonneurTotal,
            'totalAccorde'     => $totalAccorde,

        ]);
    }
}
