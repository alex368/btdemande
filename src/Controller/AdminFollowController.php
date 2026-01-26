<?php

namespace App\Controller;

use App\Entity\Opportunity;
use App\Entity\Quote;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminFollowController extends AbstractController
{
 #[Route('/adminz/follow', name: 'app_admin_follow')]
    public function index(EntityManagerInterface $em): Response
    {
        // ===========================
        // 1) OPPORTUNITÉS
        // ===========================
        $opportunities = $em->getRepository(Opportunity::class)->findAll();

        $stages = [
            'prospect'      => 'Prospect',
            'qualification' => 'Qualification',
            'proposal'      => 'Proposition',
            'negotiation'   => 'Négociation',
            'won'           => 'Gagné',
            'lost'          => 'Perdu',
        ];

        $pipelineCounts = array_fill_keys(array_keys($stages), 0);

        foreach ($opportunities as $opp) {
            $stage = $opp->getStage();
            if (isset($pipelineCounts[$stage])) {
                $pipelineCounts[$stage]++;
            }
        }

        $stats = [
            'won'  => $pipelineCounts['won'],
            'lost' => $pipelineCounts['lost'],
            'open' => array_sum($pipelineCounts) - $pipelineCounts['won'] - $pipelineCounts['lost'],
        ];

        // ===========================
        // 2) DEVIS (suivi : valide/expiré)
        // ===========================
        $quotes = $em->getRepository(Quote::class)->findAll();
        $today  = new \DateTimeImmutable('today');

        $valid   = 0;
        $expired = 0;

        foreach ($quotes as $quote) {
            if ($quote->getExpirationDate() < $today) {
                $expired++;
            } else {
                $valid++;
            }
        }

        $quoteStats = [
            'total'   => $valid + $expired,
            'valid'   => $valid,
            'expired' => $expired,
        ];

        return $this->render('admin_follow/index.html.twig', [
            'stages'         => $stages,
            'pipelineCounts' => $pipelineCounts,
            'stats'          => $stats,
            'quoteStats'     => $quoteStats,
        ]);
    }

}
