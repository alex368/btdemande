<?php

namespace App\Controller;

use App\Service\GoogleCalendarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    #[Route('/api/chat', name: 'chat', methods: ['POST'])]
    public function chat(
        Request $request,
        HttpClientInterface $http,
        GoogleCalendarService $google
    ): JsonResponse {

        // 1. Lire le message utilisateur
        $payload = json_decode($request->getContent(), true);
        $message = $payload['message'] ?? null;

        if (!$message) {
            return $this->json(["error" => "Le champ 'message' est obligatoire"], 400);
        }

        // 2. Charger les Tools OpenAI
        $tools = require $this->getParameter('kernel.project_dir').'/config/openai/tools.php';

        // 3. Appel OpenAI (avec timeout augmenté)
        try {
            $ai = $http->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$_ENV['OPENAI_API_KEY'],
                    'Content-Type' => 'application/json'
                ],
                'timeout' => 30,
                'max_duration' => 60,
                'json' => [
                    "model" => "gpt-4o-mini",
                    "messages" => [
                        [
                            "role" => "system",
                            "content" =>
"Tu es un assistant agenda professionnel. Utilise l'action create_event si un rendez-vous est demandé."
                        ],
                        ["role" => "user", "content" => $message]
                    ],
                    "tools" => $tools,
                    "tool_choice" => "auto"
                ]
            ])->toArray();
        } catch (\Exception $e) {
            return $this->json(["error" => "Erreur OpenAI : ".$e->getMessage()], 500);
        }

        // 4. L’IA demande-t-elle une action ?
        $toolCalls = $ai['choices'][0]['message']['tool_calls'] ?? null;

        if ($toolCalls) {
            foreach ($toolCalls as $call) {

                if ($call['function']['name'] === 'create_event') {

                    $args = json_decode($call['function']['arguments'], true);

                    if (!isset($args['title'], $args['start'], $args['end'])) {
                        return $this->json(["error" => "Arguments IA invalides"], 400);
                    }

                    // ✅ 5. APPEL DIRECT AU SERVICE (PLUS DE HTTP → PLUS DE TIMEOUT)
                    try {
                        $created = $google->createEvent(
                            $_ENV['GOOGLE_CALENDAR_ID'],
                            $args['title'],
                            $args['start'],
                            $args['end']
                        );
                    } catch (\Exception $e) {
                        return $this->json([
                            "error" => "Google Calendar error: ".$e->getMessage()
                        ], 500);
                    }

                    return $this->json([
                        "reply" => "Le rendez-vous a été créé.",
                        "event" => $created
                    ]);
                }
            }
        }

        // 6. Sinon → réponse IA normale
        return $this->json([
            "reply" => $ai['choices'][0]['message']['content']
        ]);
    }
}
