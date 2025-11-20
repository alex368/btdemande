<?php

namespace App\Command;

use App\Service\MailerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-mail',
    description: 'Envoie un email avec le MailerService (mode interactif)'
)]
class SendMailCommand extends Command
{
    public function __construct(
        private readonly MailerService $mailerService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Pose la question du destinataire
        $to = $io->ask('Destinataire (email)');

        // Pose la question de l’objet
        $subject = $io->ask('Objet du mail', 'Test par défaut');

        // Pour tester : template basique
        $template = 'emails/default.html.twig';
        $context = [
            'nom' => 'Client',
            'message' => 'Ceci est un test d’envoi via la console.'
        ];

        try {
            $this->mailerService->send($to, $subject, $template, $context);
            $io->success("Email envoyé avec succès à $to");
        } catch (\Exception $e) {
            $io->error("Erreur lors de l'envoi : {$e->getMessage()}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
