<?php

namespace App\EventSubscriber;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class MaintenanceModeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly bool $maintenanceMode,
        private readonly Security $security,
        private readonly Environment $twig,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->maintenanceMode) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();
        $route = (string) $request->attributes->get('_route', '');

        if ($this->isAllowedPath($path) || $this->isAllowedRoute($route)) {
            return;
        }

        if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return;
        }

        $response = new Response(
            $this->twig->render('maintenance/index.html.twig'),
            Response::HTTP_SERVICE_UNAVAILABLE
        );

        $response->headers->set('Retry-After', '3600');
        $event->setResponse($response);
    }

    private function isAllowedPath(string $path): bool
    {
        $prefixes = [
            '/_profiler',
            '/_wdt',
            '/css',
            '/js',
            '/images',
            '/build',
            '/uploads',
            '/favicon',
        ];

        foreach ($prefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function isAllowedRoute(string $route): bool
    {
        return \in_array($route, ['app_login', 'app_logout'], true);
    }
}
