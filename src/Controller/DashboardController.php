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
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $tz = new \DateTimeZone('Europe/Paris');

        $todayStart = new \DateTimeImmutable('today', $tz);
        $tomorrowStart = $todayStart->modify('+1 day');
        $yearStart = new \DateTimeImmutable('first day of january this year midnight', $tz);
        $yearEnd = $yearStart->modify('+1 year');

        $eventsToday = $eventRepository->createQueryBuilder('e')
            ->andWhere('e.startDate < :tomorrowStart')
            ->andWhere('e.endDate >= :todayStart')
            ->setParameter('todayStart', $todayStart)
            ->setParameter('tomorrowStart', $tomorrowStart)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();

        $requestsQb = $em->getRepository(FundingRequest::class)->createQueryBuilder('fr')
            ->orderBy('fr.id', 'DESC');
        $this->applyRoleScope($requestsQb, $currentUser);
        $dashboardRequests = $requestsQb->getQuery()->getResult();

        $buildValidatedAmountQb = function (string $type) use ($em, $currentUser): QueryBuilder {
            $qb = $em->getRepository(FundingRequest::class)->createQueryBuilder('fr')
                ->join('fr.product', 'p')
                ->andWhere('fr.status = :closedStatus')
                ->andWhere('fr.decision = :validatedDecision')
                ->andWhere('p.typeProduct = :type')
                ->andWhere('fr.createdAt IS NOT NULL')
                ->setParameter('closedStatus', FundingRequest::STATUS_CLOSED)
                ->setParameter('validatedDecision', FundingRequest::DECISION_VALIDATED)
                ->setParameter('type', $type)
                ->orderBy('fr.id', 'DESC');

            $this->applyRoleScope($qb, $currentUser);

            return $qb;
        };

        $subventionTotal  = array_sum(array_map(fn($fr) => $fr->getAmount(), $buildValidatedAmountQb('Subvention')->getQuery()->getResult()));
        $pretTotal        = array_sum(array_map(fn($fr) => $fr->getAmount(), $buildValidatedAmountQb('Pret')->getQuery()->getResult()));
        $pretHonneurTotal = array_sum(array_map(fn($fr) => $fr->getAmount(), $buildValidatedAmountQb("Pret d'honneur")->getQuery()->getResult()));
        $totalAccorde     = $subventionTotal + $pretTotal + $pretHonneurTotal;

        $baseFinanceurQb = $em->getRepository(FundingRequest::class)->createQueryBuilder('fr')
            ->join('fr.product', 'p')
            ->leftJoin('p.fundingMechanism', 'fm');
        $this->applyRoleScope($baseFinanceurQb, $currentUser);

        $ongoingRequests = (clone $baseFinanceurQb)
            ->andWhere('fr.status = :inProgressStatus')
            ->andWhere('fr.createdAt IS NOT NULL')
            ->andWhere('fr.createdAt >= :yearStart')
            ->andWhere('fr.createdAt < :yearEnd')
            ->setParameter('inProgressStatus', FundingRequest::STATUS_IN_PROGRESS)
            ->setParameter('yearStart', $yearStart)
            ->setParameter('yearEnd', $yearEnd)
            ->getQuery()
            ->getResult();

        $validatedThisYearRequests = (clone $baseFinanceurQb)
            ->andWhere('fr.status = :closedStatus')
            ->andWhere('fr.decision = :validatedDecision')
            ->andWhere('fr.createdAt IS NOT NULL')
            ->andWhere('fr.createdAt >= :yearStart')
            ->andWhere('fr.createdAt < :yearEnd')
            ->setParameter('closedStatus', FundingRequest::STATUS_CLOSED)
            ->setParameter('validatedDecision', FundingRequest::DECISION_VALIDATED)
            ->setParameter('yearStart', $yearStart)
            ->setParameter('yearEnd', $yearEnd)
            ->getQuery()
            ->getResult();

        $groupByFinanceur = static function (array $items): array {
            $counts = [];

            foreach ($items as $item) {
                $label = $item->getProduct()?->getFundingMechanism()?->getName() ?? 'Non défini';
                $counts[$label] = ($counts[$label] ?? 0) + 1;
            }

            return $counts;
        };

        return $this->render('dashboard/index.html.twig', [
            'events'           => $eventsToday,
            'dashboardRequests' => $dashboardRequests,
            'subventionTotal'  => $subventionTotal,
            'pretTotal'        => $pretTotal,
            'pretHonneurTotal' => $pretHonneurTotal,
            'totalAccorde'     => $totalAccorde,
            'ongoingFinanceurData' => $groupByFinanceur($ongoingRequests),
            'validatedFinanceurData' => $groupByFinanceur($validatedThisYearRequests),
            'statusWaitingClient' => FundingRequest::STATUS_WAITING_CLIENT,
            'statusBackFromClient' => FundingRequest::STATUS_BACK_FROM_CLIENT,
            'trackingStatuses' => FundingRequest::getTrackingStatuses(),
        ]);
    }

    private function applyRoleScope(QueryBuilder $qb, User $currentUser): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        if ($this->isGranted('ROLE_COLLABORATOR')) {
            $qb->andWhere('fr.user = :currentUser')
                ->setParameter('currentUser', $currentUser);

            return;
        }

        if ($this->isGranted('ROLE_CUSTOMER')) {
            $qb->join('fr.campany', 'c')
                ->join('c.customer', 'cu')
                ->andWhere('cu = :currentUser')
                ->setParameter('currentUser', $currentUser);
        }
    }
}
