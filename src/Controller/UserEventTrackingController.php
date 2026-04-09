<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\UserSessionEventLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class UserEventTrackingController extends AbstractController
{
    #[Route('/track/event', name: 'app_user_event_track', methods: ['POST'])]
    public function track(Request $request, UserSessionEventLogger $eventLogger): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['ok' => true], 204);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['ok' => true], 204);
        }

        $eventType = (string) ($payload['eventType'] ?? 'button_click');
        $actionName = isset($payload['actionName']) ? (string) $payload['actionName'] : null;

        $eventLogger->logRequest($user, $request, $eventType, $actionName, [
            'page' => isset($payload['page']) ? (string) $payload['page'] : null,
            'target' => isset($payload['target']) ? (string) $payload['target'] : null,
            'href' => isset($payload['href']) ? (string) $payload['href'] : null,
            'route' => isset($payload['route']) ? (string) $payload['route'] : null,
        ]);

        return new JsonResponse(['ok' => true], 201);
    }
}
