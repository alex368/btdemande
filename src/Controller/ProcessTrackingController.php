<?php

namespace App\Controller;

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
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if (
            !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_COLLABORATOR')
            && !$this->isGranted('ROLE_CUSTOMER')
        ) {
            throw $this->createAccessDeniedException('Acces refuse.');
        }

        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        if (
            ($this->isGranted('ROLE_COLLABORATOR') || $this->isGranted('ROLE_CUSTOMER'))
            && (int) $user->getId() !== (int) $currentUser->getId()
        ) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas consulter ce suivi.');
        }

        $campanies = $user->getCampanies();
        $campanyTrackingData = [];

        foreach ($campanies as $campany) {
            $requests = $em->getRepository(FundingRequest::class)->findBy([
                'campany' => $campany,
            ], [
                'id' => 'DESC',
            ]);

            if (\count($requests) === 0) {
                continue;
            }

            $campanyTrackingData[] = [
                'campany'  => $campany,
                'requests' => $requests,
            ];
        }

        return $this->render('process_tracking/index.html.twig', [
            'trackingData' => $campanyTrackingData,
            'waitingClientStatus' => FundingRequest::STATUS_WAITING_CLIENT,
            'trackingStatuses' => FundingRequest::getTrackingStatuses(),
        ]);
    }


    #[Route('/client/request/{id}/tracking', name: 'client_request_tracking')]
    public function tracking(FundingRequest $fundingRequest): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $canAccess = false;
        if ($this->isGranted('ROLE_ADMIN')) {
            $canAccess = true;
        } elseif ($this->isGranted('ROLE_COLLABORATOR')) {
            $canAccess = $fundingRequest->getUser()?->getId() === $currentUser->getId();
        } elseif ($this->isGranted('ROLE_CUSTOMER')) {
            $company = $fundingRequest->getCampany();
            if ($company) {
                foreach ($company->getCustomer() as $customer) {
                    if ($customer->getId() === $currentUser->getId()) {
                        $canAccess = true;
                        break;
                    }
                }
            }
        }

        if (!$canAccess) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas consulter cette demande.');
        }

        return $this->render('process_tracking/tracking.html.twig', [
            'request' => $fundingRequest,
            'steps' => FundingRequest::getTrackingStatuses(),
        ]);
    }
}
