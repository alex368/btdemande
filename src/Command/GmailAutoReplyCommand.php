<?php

namespace App\Command;

use App\Service\GmailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'gmail:auto-reply',
    description: 'Répond automatiquement au dernier email envoyé par contact@btdconsulting.fr'
)]
class GmailAutoReplyCommand extends Command
{
    public function __construct(
        private GmailService $gmail,
        private HttpClientInterface $http
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("\n🔍 Vérification du dernier email reçu...");

        /*
        |--------------------------------------------------------------------------
        | 1) Récupérer le DERNIER email uniquement
        |--------------------------------------------------------------------------
        */
        $ids = $this->gmail->listMessagesIds("", "me");

        if (count($ids) === 0) {
            $output->writeln("❌ Aucun email trouvé.");
            return Command::SUCCESS;
        }

        $emailId = $ids[0]; // Le plus récent
        $output->writeln("📨 Email détecté : $emailId");

        /*
        |--------------------------------------------------------------------------
        | 2) Récupération des métadonnées
        |--------------------------------------------------------------------------
        */
        $meta = $this->gmail->getMessageMetadata($emailId);

        $fromEmail = strtolower($this->gmail->extractEmail($meta["from"] ?? ""));
        $subject   = $meta["subject"] ?? "(Sans sujet)";
        $messageId = $meta["message_id"] ?? null;

        $output->writeln("📧 Expéditeur : $fromEmail");
        $output->writeln("📝 Sujet : $subject");

        /*
        |--------------------------------------------------------------------------
        | 3) Condition : ne répondre qu’à contact@btdconsulting.fr
        |--------------------------------------------------------------------------
        */
        if ($fromEmail !== "contact@btdconsulting.fr") {
            $output->writeln("⏩ Email ignoré : expéditeur différent.");
            return Command::SUCCESS;
        }

        $output->writeln("✔ Email autorisé → réponse automatique en cours...");

        /*
        |--------------------------------------------------------------------------
        | 4) Récupérer le texte du mail
        |--------------------------------------------------------------------------
        */
        $body = $this->gmail->getMessageBody($emailId);

        /*
        |--------------------------------------------------------------------------
        | 5) Générer réponse avec OpenAI
        |--------------------------------------------------------------------------
        */
        $output->writeln("✍ Appel IA pour rédiger la réponse...");

        $ai = $this->http->request('POST', 'https://api.openai.com/v1/chat/completions', [
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
"Tu es un assistant email professionnel. Rédige une réponse claire, utile et polie.
Ne dépasse jamais 10 lignes. Ne mentionne jamais l’IA."
                    ],
                    [
                        "role" => "user",
                        "content" =>
"Voici le mail reçu de contact@btdconsulting.fr :

-----------------------
$body
-----------------------

Rédige une réponse adaptée et professionnelle."
                    ]
                ]
            ]
        ])->toArray();

        $reply = $ai['choices'][0]['message']['content'] ?? null;

        if (!$reply) {
            $output->writeln("❌ Impossible de générer une réponse IA.");
            return Command::SUCCESS;
        }

        $output->writeln("💬 Réponse générée :");
        $output->writeln($reply);

        /*
        |--------------------------------------------------------------------------
        | 6) Construire le RAW email RFC822 pour Gmail
        |--------------------------------------------------------------------------
        */
        $raw  = "From: me\r\n";
        $raw .= "To: contact@btdconsulting.fr\r\n";
        $raw .= "Subject: Re: $subject\r\n";

        if ($messageId) {
            $raw .= "In-Reply-To: $messageId\r\n";
            $raw .= "References: $messageId\r\n";
        }

        $raw .= "\r\n" . $reply;

        $encoded = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

        /*
        |--------------------------------------------------------------------------
        | 7) Envoi de l’email via Gmail
        |--------------------------------------------------------------------------
        */
        $this->gmail->sendMessage($encoded);
        $output->writeln("✅ Email envoyé automatiquement !");

        /*
        |--------------------------------------------------------------------------
        | 8) ARCHIVER l’email pour éviter les doublons
        |--------------------------------------------------------------------------
        */
        $this->gmail->modifyMessage(
            $emailId,
            ["ARCHIVE"],     // Ajouter label ARCHIVE
            ["INBOX"]        // Retirer de la boîte de réception
        );

        $output->writeln("📦 Email archivé → Il ne sera plus traité.");
        $output->writeln("🎉 Bot terminé.\n");

        return Command::SUCCESS;
    }
}
