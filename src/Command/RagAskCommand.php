<?php

namespace App\Command;

use App\AI\RagRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rag:ask',
    description: 'Pose une question à l’agent RAG basé sur les documents indexés',
)]
class RagAskCommand extends Command
{
    public function __construct(
        private readonly RagRunner $ragRunner,
        private readonly string $documentsPath
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('question', InputArgument::REQUIRED, 'La question à poser à l’agent RAG');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $question = $input->getArgument('question');

        $io->section("Recherche : « $question »");
        $response = $this->ragRunner->run($this->documentsPath, $question);

        $io->success("Réponse :");
        $io->writeln('');
        $io->writeln($response);

        return Command::SUCCESS;
    }
}
