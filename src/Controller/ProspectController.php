<?php

namespace App\Controller;

use App\Entity\Opportunity;
use App\Repository\OpportunityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProspectController extends AbstractController
{
#[Route('/prospects/kanban', name: 'app_prospect_kanban')]
public function kanban(
    OpportunityRepository $opportunityRepository,
    Request $request
): Response {

    $user = $this->getUser();

    // Pipeline
    $stages = [
        'prospect'      => 'Prospect',
        'qualification' => 'Qualification',
        'proposal'      => 'Proposition',
        'negotiation'   => 'Négociation',
        'won'           => 'Gagné',
        'lost'          => 'Perdu',
    ];

    // Récupérer toutes les opportunités selon le rôle
    if (in_array('ROLE_COLLABORATOR', $user->getRoles())) {
        $allOpportunities = $opportunityRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );
    } else {
        $allOpportunities = $opportunityRepository->findBy([], ['createdAt' => 'DESC']);
    }

    // -------------------------------------------------------------
    // 🔥 Garder UNIQUEMENT la dernière opportunité par contact
    // -------------------------------------------------------------
    $latest = []; // contactId => opportunity

    foreach ($allOpportunities as $opp) {
        $contact = $opp->getContact();
        if (!$contact) continue;

        $contactId = $contact->getId();

        // La première rencontrée est la plus récente (tri DESC)
        if (!isset($latest[$contactId])) {
            $latest[$contactId] = $opp;
        }
    }

    $opportunities = array_values($latest);

    // -------------------------------------------------------------
    // Construire les colonnes du Kanban
    // -------------------------------------------------------------
    $columns = [];
    foreach ($stages as $key => $label) {
        $columns[$key] = [];
    }

    $stats = ['total' => 0, 'won' => 0, 'lost' => 0, 'open' => 0];

    foreach ($opportunities as $opp) {
        $stage = $opp->getStage();

        $columns[$stage][] = $opp;
        $stats['total']++;

        if ($stage === 'won') $stats['won']++;
        elseif ($stage === 'lost') $stats['lost']++;
        else $stats['open']++;
    }

    return $this->render('prospect/kanban.html.twig', [
        'stages'  => $stages,
        'columns' => $columns,
        'stats'   => $stats,
    ]);
}

#[Route('/prospects/kanban/update-stage', name: 'app_prospect_kanban_update_stage', methods: ['POST'])]
public function updateStage(
    Request $request,
    EntityManagerInterface $em
): JsonResponse {

    $data = json_decode($request->getContent(), true);

    if (!isset($data['id'], $data['stage'])) {
        return new JsonResponse(['success' => false], 400);
    }

    $oldOpp = $em->getRepository(Opportunity::class)->find($data['id']);
    $user = $this->getUser();

    if (!$oldOpp) {
        return new JsonResponse(['success' => false], 404);
    }

    // 🔐 Sécurité collaborateur
    if (
        in_array('ROLE_COLLABORATOR', $user->getRoles()) &&
        $oldOpp->getUser() !== $user
    ) {
        return new JsonResponse(['success' => false, 'message' => 'Accès refusé'], 403);
    }

    // -------------------------------------------------------------
    // 1️⃣ CRÉATION NOUVELLE OPPORTUNITÉ (heure Europe/Paris)
    // -------------------------------------------------------------
    $parisTz = new \DateTimeZone('Europe/Paris');

    $newOpp = clone $oldOpp; // Doctrine → clone sans ID = nouvelle entité
    $newOpp->setStage($data['stage']);
    $newOpp->setUser($user);
    $newOpp->setCreatedAt(new \DateTimeImmutable('now', $parisTz));

    $em->persist($newOpp);
    $em->flush(); // génère l'ID
    // -------------------------------------------------------------

    // -------------------------------------------------------------
    // 2️⃣ SUPPRESSION DES OPPORTUNITÉS EN DOUBLE (même stage + contact)
    // -------------------------------------------------------------
    $repo = $em->getRepository(Opportunity::class);

    $sameStage = $repo->createQueryBuilder('o')
        ->where('o.contact = :contact')
        ->andWhere('o.stage = :stage')
        ->andWhere('o.id != :newId')
        ->setParameter('contact', $newOpp->getContact())
        ->setParameter('stage', $data['stage'])
        ->setParameter('newId', $newOpp->getId())
        ->getQuery()
        ->getResult();

    foreach ($sameStage as $duplicate) {
        $em->remove($duplicate);
    }

    $em->flush();
    // -------------------------------------------------------------

    return new JsonResponse(['success' => true]);
}





}
