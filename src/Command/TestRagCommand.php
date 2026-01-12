<?php
namespace App\Command;

use App\Service\RagService;
use App\Service\RagTestService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// src/Command/TestRagCommand.php
#[AsCommand(name: 'app:test:rag-chat')]
class TestRagCommand extends Command
{
    public function __construct(
        private RagTestService $ragTestService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = '/Users/alexganvo/Documents/Cours/variables-js_pdf.pdf';
        $question = "Qu'est-ce qu'une variable en JavaScript ?";

        // $answer = $this->ragTestService->testFromPdf($path, $question); TODO
        // $output->writeln("✅ Réponse : " . $answer);

        return Command::SUCCESS;
    }
}
