<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

final class CustomerPortalController extends AbstractController
{

#[Route('/customer/portal', name: 'app_customer_portal')]
public function index(
    Request $request,
    UserRepository $userRepository,
    PaginatorInterface $paginator
): Response {
    $search = $request->query->get('search', '');

    $queryBuilder = $userRepository->getQueryBuilderByRoleAndSearch('ROLE_CUSTOMER', $search);

    $pagination = $paginator->paginate(
        $queryBuilder,
        $request->query->getInt('page', 1),
        10
    );

    return $this->render('customer_portal/index.html.twig', [
        'pagination' => $pagination,
        'search' => $search,
    ]);
}

}
