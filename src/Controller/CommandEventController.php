<?php

namespace App\Controller;

use App\Service\GoogleCalendarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class CommandEventController extends AbstractController
{
    #[Route('/api/command/create-event', name: 'command_create_event', methods: ['POST'])]
    public function createEvent(
        Request $request,
        GoogleCalendarService $google
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['title'], $data['start'], $data['end'])) {
            return $this->json(["error" => "Missing title/start/end"], 400);
        }

        $calendarId = $_ENV['GOOGLE_CALENDAR_ID'];

        $createdEvent = $google->createEvent(
            $calendarId,
            $data['title'],
            $data['start'],
            $data['end']
        );

        return $this->json([
            "status" => "created",
            "event" => $createdEvent
        ]);
    }
}
