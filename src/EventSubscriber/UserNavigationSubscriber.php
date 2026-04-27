<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\UserSessionEventLogger;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class UserNavigationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly UserSessionEventLogger $eventLogger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = (string) $request->attributes->get('_route', '');
        $session = $request->hasSession() ? $request->getSession() : null;

        if ($session !== null && $this->shouldTrackNavigation($request, $route)) {
            $currentUri = (string) $request->getRequestUri();
            $lastCurrentUri = (string) $session->get('nav.current_uri', '');

            if ($lastCurrentUri !== '' && $lastCurrentUri !== $currentUri) {
                $session->set('nav.previous_uri', $lastCurrentUri);
            }

            $session->set('nav.current_uri', $currentUri);
        }

        if ($route === '' || str_starts_with($route, '_')) {
            return;
        }

        if ($route === 'app_user_event_track') {
            return;
        }

        if (!in_array($request->getMethod(), ['GET', 'POST'], true)) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $this->eventLogger->logRequest($user, $request, 'page_view');
    }

    private function shouldTrackNavigation(\Symfony\Component\HttpFoundation\Request $request, string $route): bool
    {
        if ($route === '' || str_starts_with($route, '_')) {
            return false;
        }

        if (!in_array($request->getMethod(), ['GET'], true)) {
            return false;
        }

        if ($request->isXmlHttpRequest()) {
            return false;
        }

        $excludedRoutes = [
            'app_user_event_track',
            'app_back',
            'app_logout',
        ];

        return !in_array($route, $excludedRoutes, true);
    }
}
