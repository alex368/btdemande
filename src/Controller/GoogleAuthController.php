<?php

namespace App\Controller;

use App\Service\GoogleCalendarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GoogleAuthController extends AbstractController
{
#[Route('/google/auth', name: 'google_auth')]
public function auth(GoogleCalendarService $google): Response
{
    return $this->redirect($google->getAuthUrl());
}


#[Route('/google/callback', name: 'google_callback')]
public function callback(Request $request, GoogleCalendarService $google): Response
{
    $code = $request->query->get('code');

    if (!$code) {
        return new Response("ERREUR : aucun code OAuth reçu");
    }

    $tokenPath = $this->getParameter('kernel.project_dir') . '/config/google/token.json';
    $google->authenticate($code, $tokenPath);

    return new Response("Authentification réussie ! Vous pouvez fermer cette page.");
}


       #[Route('/api/google-calendar/events', name: 'google_calendar_events')]
    public function events(GoogleCalendarService $google): JsonResponse
    {
        $calendarId = 'd79569cd6e5b25d499fe645aa1ea45336a144925782da1da3f912923b7342064@group.calendar.google.com';

        $events = $google->getEvents($calendarId);

        return $this->json($events);
    }
}
