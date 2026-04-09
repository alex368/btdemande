<?php

namespace App\Controller\Admin;

use App\Entity\UserSessionEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class UserTrackingReportController extends AbstractController
{
    #[Route('/admin/tracking/{id}/report', name: 'app_admin_tracking_report', methods: ['GET'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function download(UserSessionEvent $tracking): Response
    {
        $user = $tracking->getUser();
        $metadata = $tracking->getMetadata() ?? [];
        $totals = is_array($metadata['totals'] ?? null) ? $metadata['totals'] : [];

        $report = [
            'generated_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'user' => [
                'id' => $user?->getId(),
                'email' => $user?->getEmail(),
                'name' => trim((string) ($user?->getLastname().' '.$user?->getName())),
                'roles' => $user?->getRoles(),
            ],
            'global' => [
                'first_seen_at' => $metadata['first_seen_at'] ?? null,
                'last_seen_at' => $metadata['last_seen_at'] ?? null,
                'events' => (int) ($totals['events'] ?? 0),
                'page_views' => (int) ($totals['page_views'] ?? 0),
                'clicks' => (int) ($totals['clicks'] ?? 0),
            ],
            'pages' => $metadata['pages'] ?? [],
            'sessions' => $metadata['sessions'] ?? [],
            'recent_actions' => $metadata['actions'] ?? [],
        ];

        $content = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $filename = sprintf('tracking-synthese-user-%s.json', $user?->getId() ?? $tracking->getId());

        return new Response(
            $content ?: '{}',
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );
    }
}
