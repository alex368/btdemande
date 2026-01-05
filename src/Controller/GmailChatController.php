<?php

namespace App\Controller;

use App\Service\GmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class GmailChatController extends AbstractController
{
   #[Route('/api/gmail/chat', name: 'gmail_chat', methods: ['POST'])]
public function chat(
    Request $request,
    HttpClientInterface $http,
    GmailService $gmail
): JsonResponse {

    $payload = json_decode($request->getContent(), true);
    $command = $payload['command'] ?? null;
    $emailId = $payload['emailId'] ?? null;

    if (!$command) {
        return $this->json(["error" => "Le champ 'command' est obligatoire"], 400);
    }

    // Si pas d'emailId => on prend le plus récent parmi les 5 derniers
    if (!$emailId) {
        $lastIds = $gmail->listMessagesIds("", "me");

        if (count($lastIds) === 0) {
            return $this->json(["error" => "Aucun email trouvé."], 404);
        }

        $lastIds = array_slice($lastIds, 0, 5);
        $emailId = $lastIds[0];
    }

    // Contenu du mail
    $body = $gmail->getMessageBody($emailId);

    // Métadonnées : expéditeur, sujet, etc.
    $meta      = $gmail->getMessageMetadata($emailId);
    $fromFull  = $meta["from"] ?? "";
    $fromEmail = strtolower($gmail->extractEmail($fromFull));
    $subject   = $meta["subject"] ?? "(Sans sujet)";
    $messageId = $meta["message_id"] ?? null;

    /*************************************************************
     * 1) CAS 1 : expéditeur ≠ contact@btdconsulting.fr
     *    → on ne répond pas, on peut juste faire un résumé
     *************************************************************/
    if ($fromEmail !== 'contact@btdconsulting.fr') {

        // Ici : pas de tools, juste un résumé / analyse
        $ai = $http->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $_ENV['OPENAI_API_KEY'],
                'Content-Type'  => 'application/json'
            ],
            'json' => [
                "model" => "gpt-4o-mini",
                "messages" => [
                    [
                        "role" => "system",
                        "content" =>
"Tu es un assistant email. Tu dois simplement résumer le mail ci-dessous.
Ne propose aucune réponse, ne génère pas de texte à envoyer."
                    ],
                    [
                        "role" => "user",
                        "content" =>
"Résumé demandé.\n\nSujet : $subject\nExpéditeur : $fromFull\n\nEmail :\n$body"
                    ]
                ],
            ]
        ])->toArray();

        return $this->json([
            "info"   => "Aucun email envoyé (expéditeur différent de contact@btdconsulting.fr).",
            "from"   => $fromEmail,
            "mode"   => "summary_only",
            "reply"  => $ai['choices'][0]['message']['content'] ?? null,
            "emailId"=> $emailId,
        ]);
    }

    /*************************************************************
     * 2) CAS 2 : expéditeur = contact@btdconsulting.fr
     *    → on PEUT répondre automatiquement (IA ou auto-text)
     *************************************************************/

    // Charger les tools (avec send_email)
    $tools = require $this->getParameter('kernel.project_dir') . '/config/openai/gmail_tools.php';

    $ai = $http->request('POST', 'https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $_ENV['OPENAI_API_KEY'],
            'Content-Type'  => 'application/json'
        ],
        'json' => [
            "model" => "gpt-4o-mini",
            "messages" => [
                [
                    "role" => "system",
                    "content" =>
"Tu es un assistant email professionnel. Tu rédiges une réponse à envoyer à l'expéditeur."
                ],
                [
                    "role" => "user",
                    "content" =>
"Commande : $command\n\nExpéditeur : $fromFull\nSujet : $subject\n\nEmail :\n$body"
                ]
            ],
            "tools" => $tools,
            "tool_choice" => "auto"
        ]
    ])->toArray();

    $toolCalls = $ai['choices'][0]['message']['tool_calls'] ?? null;

    // Si l'IA a demandé un envoi de mail
    if ($toolCalls) {
        foreach ($toolCalls as $call) {
            if (($call['function']['name'] ?? null) === 'send_email') {

                $args = json_decode($call['function']['arguments'], true);

                // On force bien la réponse UNIQUEMENT à cette adresse
                $to = 'contact@btdconsulting.fr';

                // Sujet par défaut si besoin
                $subjectReply = $args['subject'] ?? ("Re: " . $subject);
                $bodyReply    = $args['body']    ?? '';

                // Construction du mail RAW
                $raw  = "From: me\r\n";
                $raw .= "To: $to\r\n";
                $raw .= "Subject: $subjectReply\r\n";

                if ($messageId) {
                    $raw .= "In-Reply-To: $messageId\r\n";
                    $raw .= "References: $messageId\r\n";
                }

                $raw .= "\r\n" . $bodyReply;

                $encoded = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

                $gmail->sendMessage($encoded);

                return $this->json([
                    "reply"       => "Email envoyé uniquement à contact@btdconsulting.fr.",
                    "to"          => $to,
                    "subject"     => $subjectReply,
                    "body"        => $bodyReply,
                    "emailId"     => $emailId,
                    "mode"        => "ai_reply",
                ]);
            }
        }
    }

    // Si pas de tool_call → on renvoie juste ce que l'IA a généré (sans envoi d'email)
    return $this->json([
        "reply"   => $ai['choices'][0]['message']['content'] ?? null,
        "info"    => "Aucun email envoyé (pas de tool_call).",
        "mode"    => "text_only",
        "emailId" => $emailId,
    ]);
}


#[Route('/api/gmail/messages', name: 'gmail_messages', methods: ['GET'])]
public function list(
    GmailService $gmail,
    HttpClientInterface $http
): JsonResponse
{
    // 1) Récupérer les IDs
    $ids = $gmail->listMessagesIds(); // Triés du plus récent au plus ancien

    // On garde seulement les 5 derniers
    $ids = array_slice($ids, 0, 5);

    if (empty($ids)) {
        return $this->json(["error" => "Aucun email trouvé"]);
    }

    $messages = [];

    foreach ($ids as $id) {

        // Récupérer le contenu brut du mail
        $body = $gmail->getMessageBody($id);

        // ---------- Résumé IA -----------
        $ai = $http->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer '.$_ENV['OPENAI_API_KEY'],
                'Content-Type' => 'application/json'
            ],
            'json' => [
                "model" => "gpt-4o-mini",
                "messages" => [
                    [
                        "role" => "system",
                        "content" =>
"Tu es un assistant professionnel. Résume très clairement le contenu d'un email en 2 phrases maximum."
                    ],
                    [
                        "role" => "user",
                        "content" =>
"Voici le contenu de l'email :

$body

Fais un résumé court et clair."
                    ]
                ]
            ]
        ])->toArray();

        $summary = $ai['choices'][0]['message']['content'] ?? "(Résumé impossible)";

        $messages[] = [
            "id" => $id,
            "preview" => mb_substr($body, 0, 80) . "...",
            "summary" => $summary
        ];
    }

    return $this->json($messages);
}


    #[Route('/api/gmail/messages/unread', name: 'gmail_messages_unread', methods: ['GET'])]
    public function unread(GmailService $gmail): JsonResponse
    {
        $ids = $gmail->listMessagesIds("is:unread");
        $messages = [];

        foreach ($ids as $id) {
            $messages[] = [
                "id" => $id,
                "preview" => mb_substr($gmail->getMessageBody($id), 0, 80) . "..."
            ];
        }

        return $this->json($messages);
    }

    #[Route('/gmail/login', name: 'gmail_oauth_start')]
    public function gmailLogin(GmailService $gmail)
    {
        $client = $gmail->getClient();
        $authUrl = $client->createAuthUrl();
        return $this->redirect($authUrl);
    }

    #[Route('/gmail/auth', name: 'gmail_oauth_callback')]
    public function gmailAuthCallback(Request $request, GmailService $gmail)
    {
        $client = $gmail->getClient();
        $code = $request->query->get('code');

        $accessToken = $client->fetchAccessTokenWithAuthCode($code);

        file_put_contents(
            $gmail->getTokenPath(),
            json_encode($accessToken)
        );

        return new Response("TOKEN OK !");
    }
}
