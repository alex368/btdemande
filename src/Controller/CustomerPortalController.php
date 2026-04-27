<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    $search = trim((string) $request->query->get('search', ''));
    $campany = trim((string) $request->query->get('campany', ''));
    $project = trim((string) $request->query->get('project', ''));

    $queryBuilder = $userRepository->getQueryBuilderByRoleAndSearch('ROLE_CUSTOMER', $search, $campany, $project);
    $pagination = $paginator->paginate(
        $queryBuilder,
        $request->query->getInt('page', 1),
        10
    );

    $currentUser = $this->getUser();

    return $this->render('customer_portal/index.html.twig', [
        'pagination' => $pagination,
        'search' => $search,
        'campany' => $campany,
        'project' => $project,
        'referents' => $userRepository->findByRole('ROLE_COLLABORATOR'),
        'currentUser' => $currentUser instanceof User ? $currentUser : null,
    ]);
}

#[Route('/customer/portal/{id}/referent', name: 'app_customer_portal_assign_referent', methods: ['POST'])]
public function assignReferent(
    User $user,
    Request $request,
    UserRepository $userRepository,
    EntityManagerInterface $entityManager
): Response {
    $this->denyAccessUnlessGranted('ROLE_COLLABORATOR');

    if (!in_array('ROLE_CUSTOMER', $user->getRoles(), true)) {
        throw $this->createNotFoundException('Client introuvable.');
    }

    if (!$this->isCsrfTokenValid('assign-referent-' . $user->getId(), (string) $request->request->get('_token'))) {
        throw $this->createAccessDeniedException('Jeton invalide.');
    }

    $referentId = $request->request->get('referent_id');
    $referent = null;

    if (!empty($referentId)) {
        $referent = $userRepository->findOneByIdAndRole((int) $referentId, 'ROLE_COLLABORATOR');
        if (!$referent instanceof User) {
            $this->addFlash('danger', 'Le référent sélectionné est invalide.');
            return $this->redirectToRoute('app_customer_portal');
        }
    }

    $user->setReferent($referent);
    $entityManager->flush();

    $this->addFlash('success', 'Le référent du client a été mis à jour.');

    return $this->redirectToRoute('app_customer_portal');
}

}
