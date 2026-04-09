<?php

namespace App\Controller;

use App\Entity\FundingRequest;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CustomerFolderController extends AbstractController
{
    #[Route('/customer/folder', name: 'app_customer_folder')]
    public function index(
        EntityManagerInterface $em,
        PaginatorInterface $paginator,
        Request $request
    ): Response
    {
        $user = $this->getUser();
        $qb = $em->getRepository(FundingRequest::class)->createQueryBuilder('fr')
            ->leftJoin('fr.campany', 'c')
            ->leftJoin('fr.product', 'p')
            ->addSelect('c', 'p')
            ->orderBy('fr.id', 'DESC');

        if ($this->isGranted('ROLE_ADMIN')) {
            // admin: tout
        } elseif ($this->isGranted('ROLE_COLLABORATOR')) {
            $qb->andWhere('fr.user = :currentUser')
                ->setParameter('currentUser', $user);
        } elseif ($this->isGranted('ROLE_CUSTOMER')) {
            $qb->leftJoin('c.customer', 'cu')
                ->andWhere('cu = :currentUser')
                ->setParameter('currentUser', $user);
        } else {
            $qb->andWhere('1 = 0');
        }

        $findRequests = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            10
        );

        $trackingStatuses = FundingRequest::getTrackingStatuses();

        return $this->render('customer_folder/index.html.twig', [
            'findRequests' => $findRequests,
            'trackingStatuses' => $trackingStatuses,
        ]);
    }
}
