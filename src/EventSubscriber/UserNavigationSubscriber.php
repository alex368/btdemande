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
}
