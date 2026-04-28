<?php

namespace App\Controller\Admin;

use App\Entity\UserSessionEvent;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class UserTrackingReportController extends AbstractController
{
    #[Route('/admin/tracking/{id}/report/view', name: 'app_admin_tracking_report_view', methods: ['GET'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function view(UserSessionEvent $tracking): Response
    {
        $report = $this->buildReport($tracking);

        return $this->render('admin/tracking/report_view.html.twig', [
            'report' => $report,
            'tracking' => $tracking,
        ]);
    }

    #[Route('/admin/tracking/{id}/report', name: 'app_admin_tracking_report', methods: ['GET'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function download(UserSessionEvent $tracking): Response
    {
        $report = $this->buildReport($tracking);
        $user = $tracking->getUser();

        $normalizedReport = $this->normalizeForJson($report);
        $content = json_encode($normalizedReport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($content === false) {
            $content = json_encode(
                [
                    'generated_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
                    'error' => 'Impossible de générer la synthèse JSON',
                    'reason' => json_last_error_msg(),
                ],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ) ?: '{}';
        }

        $filename = sprintf('tracking-synthese-user-%s.json', $user?->getId() ?? $tracking->getId());
        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename);

        return new Response(
            $content,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Disposition' => $disposition,
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReport(UserSessionEvent $tracking): array
    {
        $user = $tracking->getUser();
        $metadata = $tracking->getMetadata() ?? [];
        $totals = is_array($metadata['totals'] ?? null) ? $metadata['totals'] : [];
        $pages = is_array($metadata['pages'] ?? null) ? $metadata['pages'] : [];
        $sessions = is_array($metadata['sessions'] ?? null) ? $metadata['sessions'] : [];
        $actions = is_array($metadata['actions'] ?? null) ? $metadata['actions'] : [];

        uasort($pages, static function (array $a, array $b): int {
            return ((int) ($b['views'] ?? 0)) <=> ((int) ($a['views'] ?? 0));
        });

        uasort($sessions, static function (array $a, array $b): int {
            return strcmp((string) ($b['last_seen_at'] ?? ''), (string) ($a['last_seen_at'] ?? ''));
        });

        usort($actions, static function (array $a, array $b): int {
            return strcmp((string) ($b['at'] ?? ''), (string) ($a['at'] ?? ''));
        });

        return [
            'generated_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'user' => [
                'id' => $user?->getId(),
                'email' => $user?->getEmail(),
                'name' => trim((string) ($user?->getLastname() . ' ' . $user?->getName())),
                'roles' => $user?->getRoles() ?? [],
            ],
            'global' => [
                'first_seen_at' => $metadata['first_seen_at'] ?? null,
                'last_seen_at' => $metadata['last_seen_at'] ?? null,
                'events' => (int) ($totals['events'] ?? 0),
                'page_views' => (int) ($totals['page_views'] ?? 0),
                'clicks' => (int) ($totals['clicks'] ?? 0),
            ],
            'pages' => $pages,
            'sessions' => $sessions,
            'recent_actions' => $actions,
        ];
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function normalizeForJson(mixed $value): mixed
    {
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeForJson($item);
            }

            return $normalized;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_string($value)) {
            if (mb_check_encoding($value, 'UTF-8')) {
                return $value;
            }

            return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
