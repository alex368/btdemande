<?php

namespace App\Command;

use App\Service\RagTestService;
use App\Service\DocumentTextExtractor;
use App\Repository\DocumentRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:test:rag-pdf',
    description: 'Teste le RAG à partir d’un document stocké en base (PDF, Word, Excel, PPT, image)'
)]
class TestRagPdfCommand extends Command
{
    public function __construct(
        private RagTestService $ragTestService,
        private DocumentTextExtractor $textExtractor,
        private DocumentRepository $documentRepository,
        private KernelInterface $kernel
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'documentId',
                InputArgument::REQUIRED,
                'ID du document en base de données'
            )
            ->addArgument(
                'question',
                InputArgument::REQUIRED,
                'Question ou instruction'
            )
            ->addArgument(
                'mode',
                InputArgument::OPTIONAL,
                'Mode : qa | summary | quiz | flashcards',
                'qa'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $documentId = (int) $input->getArgument('documentId');
        $question   = (string) $input->getArgument('question');
        $mode       = (string) $input->getArgument('mode');

        try {
            /* ===============================
               1️⃣ Récupération du document
               =============================== */

            $document = $this->documentRepository->find($documentId);

            if (!$document) {
                throw new \RuntimeException("Document introuvable (ID=$documentId)");
            }

            $relativePath = ltrim($document->getFilePath(), '/');
            $absolutePath = "public/uploads/documents/" . $relativePath;

            if (!is_file($absolutePath)) {
                throw new \RuntimeException("Fichier introuvable : $absolutePath");
            }

            /* ===============================
               2️⃣ Extraction TEXTE (UNIQUE)
               =============================== */

            $text = $this->textExtractor->extract($absolutePath);

            if ($text === '') {
                throw new \RuntimeException('Aucun texte exploitable extrait du document');
            }

            /* ===============================
               3️⃣ Appel du RAG (TEXTE ONLY)
               =============================== */

            $answer = $this->ragTestService->answerFromText(
                documentId: $documentId,
                text: $text,
                questionOrInstruction: $question,
                mode: $mode
            );

            $output->writeln('');
            $output->writeln('<info>✅ Réponse :</info>');
            $output->writeln(str_repeat('-', 50));
            $output->writeln($answer);
            $output->writeln(str_repeat('-', 50));
            $output->writeln('');

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $output->writeln('');
            $output->writeln('<error>❌ Erreur :</error>');
            $output->writeln($e->getMessage());
            $output->writeln('');

            return Command::FAILURE;
        }
    }
}
