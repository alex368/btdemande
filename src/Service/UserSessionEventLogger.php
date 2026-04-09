<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserSessionEvent;
use App\Repository\UserSessionEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class UserSessionEventLogger
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserSessionEventRepository $repository
    ) {
    }

    public function logRequest(User $user, Request $request, string $eventType = 'page_view', ?string $actionName = null, ?array $metadata = null): void
    {
        $now = new \DateTimeImmutable();
        $sessionId = $request->hasSession() ? $request->getSession()->getId() : 'no-session';
        $routeName = $request->attributes->get('_route');
        $path = $request->getPathInfo();

        $event = $this->repository->findOneByUser($user) ?? (new UserSessionEvent())->setUser($user);
        $currentMetadata = $event->getMetadata() ?? [];
        $updatedMetadata = $this->aggregateMetadata(
            $currentMetadata,
            $eventType,
            $actionName,
            $sessionId,
            $path,
            $routeName,
            $metadata,
            $now,
            $user->getRoles()
        );

        $event
            ->setSessionId($sessionId)
            ->setEventType($eventType)
            ->setActionName($actionName)
            ->setRouteName($routeName)
            ->setPath($path)
            ->setMethod($request->getMethod())
            ->setRoleSnapshot($user->getRoles())
            ->setIpAddress($request->getClientIp())
            ->setUserAgent($request->headers->get('User-Agent'))
            ->setReferrer($request->headers->get('Referer'))
            ->setMetadata($updatedMetadata)
            ->setOccurredAt($now);

        $this->em->persist($event);
        $this->em->flush();
    }

    /**
     * @param array<string, mixed> $current
     * @param array<string, mixed>|null $eventMetadata
     * @param list<string> $roles
     * @return array<string, mixed>
     */
    private function aggregateMetadata(
        array $current,
        string $eventType,
        ?string $actionName,
        string $sessionId,
        string $path,
        ?string $routeName,
        ?array $eventMetadata,
        \DateTimeImmutable $now,
        array $roles
    ): array {
        $isoNow = $now->format(DATE_ATOM);
        $summary = $current;

        $summary['first_seen_at'] ??= $isoNow;
        $summary['last_seen_at'] = $isoNow;
        $summary['totals'] ??= ['events' => 0, 'page_views' => 0, 'clicks' => 0];
        $summary['totals']['events'] = (int) ($summary['totals']['events'] ?? 0) + 1;

        $summary['sessions'] ??= [];
        $summary['sessions'][$sessionId] ??= [
            'started_at' => $isoNow,
            'last_seen_at' => $isoNow,
            'roles' => $roles,
            'page_views' => 0,
            'clicks' => 0,
            'last_page' => $path,
        ];
        $summary['sessions'][$sessionId]['last_seen_at'] = $isoNow;
        $summary['sessions'][$sessionId]['roles'] = $roles;
        $summary['sessions'][$sessionId]['last_page'] = $path;

        $summary['pages'] ??= [];
        $summary['pages'][$path] ??= ['views' => 0, 'last_seen_at' => $isoNow, 'route' => $routeName];
        $summary['pages'][$path]['last_seen_at'] = $isoNow;
        $summary['pages'][$path]['route'] = $routeName;

        if ($eventType === 'page_view') {
            $summary['totals']['page_views'] = (int) ($summary['totals']['page_views'] ?? 0) + 1;
            $summary['sessions'][$sessionId]['page_views'] = (int) ($summary['sessions'][$sessionId]['page_views'] ?? 0) + 1;
            $summary['pages'][$path]['views'] = (int) ($summary['pages'][$path]['views'] ?? 0) + 1;
        }

        if ($eventType === 'button_click') {
            $summary['totals']['clicks'] = (int) ($summary['totals']['clicks'] ?? 0) + 1;
            $summary['sessions'][$sessionId]['clicks'] = (int) ($summary['sessions'][$sessionId]['clicks'] ?? 0) + 1;
            $summary['actions'] ??= [];
            $summary['actions'][] = [
                'at' => $isoNow,
                'name' => $actionName,
                'page' => $eventMetadata['page'] ?? $path,
                'target' => $eventMetadata['target'] ?? null,
                'href' => $eventMetadata['href'] ?? null,
                'route' => $eventMetadata['route'] ?? $routeName,
            ];

            if (count($summary['actions']) > 250) {
                $summary['actions'] = array_slice($summary['actions'], -250);
            }
        }

        if (count($summary['sessions']) > 50) {
            uasort(
                $summary['sessions'],
                static fn(array $a, array $b): int => strcmp((string) ($a['last_seen_at'] ?? ''), (string) ($b['last_seen_at'] ?? ''))
            );
            $summary['sessions'] = array_slice($summary['sessions'], -50, null, true);
        }

        if (count($summary['pages']) > 200) {
            uasort(
                $summary['pages'],
                static fn(array $a, array $b): int => strcmp((string) ($a['last_seen_at'] ?? ''), (string) ($b['last_seen_at'] ?? ''))
            );
            $summary['pages'] = array_slice($summary['pages'], -200, null, true);
        }

        return $summary;
    }
}
