<?php

namespace App\Service;

use Google\Client;
use Google\Service\Calendar;

class GoogleCalendarService
{
    private Client $client;
    private Calendar $service;

    public function __construct(string $credentialsPath, string $tokenPath)
    {
        $this->client = new Client();
        $this->client->setAuthConfig($credentialsPath);

        // 👉 Scope COMPLET pour lire + écrire
        $this->client->addScope(Calendar::CALENDAR);

        $this->client->setAccessType('offline');
        $this->client->setPrompt("consent");

        // Charger un token existant
        if (file_exists($tokenPath) && filesize($tokenPath) > 10) {
            $tokenData = json_decode(file_get_contents($tokenPath), true);

            if (json_last_error() === JSON_ERROR_NONE && isset($tokenData['access_token'])) {
                $this->client->setAccessToken($tokenData);
            }
        }

        // Rafraîchir si expiré
        if ($this->client->isAccessTokenExpired()) {

            if ($this->client->getRefreshToken()) {
                $newToken = $this->client->fetchAccessTokenWithRefreshToken(
                    $this->client->getRefreshToken()
                );
                file_put_contents($tokenPath, json_encode($newToken));
            }
        }

        $this->service = new Calendar($this->client);
    }

    public function getEvents(string $calendarId): array
    {
        $events = $this->service->events->listEvents($calendarId, [
            'singleEvents' => true,
            'orderBy' => 'startTime',
        ]);

        $output = [];

        foreach ($events as $event) {
            $output[] = [
                'title' => $event->getSummary(),
                'start' => $event->start->dateTime ?? $event->start->date,
                'end'   => $event->end->dateTime ?? $event->end->date,
            ];
        }

        return $output;
    }

    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    public function authenticate(string $code, string $tokenPath): void
    {
        $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);

        // Sauvegarde token complet (DOIT contenir "refresh_token")
        file_put_contents($tokenPath, json_encode($accessToken));
    }

    public function createEvent(string $calendarId, string $title, string $start, string $end): array
    {
        // 👉 Force l'utilisation de la bonne classe Event
        $event = new \Google\Service\Calendar\Event([
            'summary' => $title,
            'start' => [
                'dateTime' => $start,
                'timeZone' => 'Europe/Paris'
            ],
            'end' => [
                'dateTime' => $end,
                'timeZone' => 'Europe/Paris'
            ],
        ]);

        $createdEvent = $this->service->events->insert($calendarId, $event);

        return [
            'id' => $createdEvent->id,
            'status' => 'created'
        ];
    }
}
