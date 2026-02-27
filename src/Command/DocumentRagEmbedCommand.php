<?php

namespace App\Command;

use App\Entity\Document;
use App\Entity\BookRagChunk;
use App\Entity\BookRagIndex;
use App\Entity\DocumentRagChunk;
use App\Entity\DocumentRagIndex;
use App\Service\DocumentTextExtractor;
use Doctrine\ORM\EntityManagerInterface;
use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:rag:embed-document',
    description: 'Génère les chunks + embeddings d’un document et met à jour l’index (PDF paginé)'
)]
final class DocumentRagEmbedCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EmbeddingGeneratorInterface $embedder,
        private readonly DocumentTextExtractor $extractor,
        #[Autowire('%UPLOAD_DIR_rag%')] private readonly string $documentsUploadDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('documentId', InputArgument::REQUIRED, 'ID du document')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force la régénération même si le hash n’a pas changé')
            ->addOption('chunk-size', null, InputOption::VALUE_REQUIRED, 'Taille cible d’un chunk (en caractères)', 1400)
            ->addOption('overlap', null, InputOption::VALUE_REQUIRED, 'Overlap (en caractères)', 150)
            ->addOption('top', null, InputOption::VALUE_REQUIRED, 'Nombre max de chunks à générer (debug)', 0)
            ->addOption('batch', null, InputOption::VALUE_REQUIRED, 'Flush batch size', 25);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $documentId    = (int) $input->getArgument('documentId');
        $force     = (bool) $input->getOption('force');
        $chunkSize = max(200, (int) $input->getOption('chunk-size'));
        $overlap   = max(0, (int) $input->getOption('overlap'));
        $top       = max(0, (int) $input->getOption('top'));
        $batch     = max(1, (int) $input->getOption('batch'));

        /** @var Document|null $document */
        $document = $this->em->getRepository(Document::class)->find($documentId);
        if (!$document) {
            $output->writeln("<error>document #{$documentId} introuvable.</error>");
            return Command::FAILURE;
        }

        // ✅ proxy managé
        $documentRef = $this->em->getReference(Document::class, $documentId);

        // 1) Chemin fichier
        $absolutePath = $this->resolveBookFilePath($document);
        $output->writeln('<comment>Fichier:</comment> ' . $absolutePath);

        // 2) Extraction PAGINÉE (page PDF réelle)
        // Retour attendu: [ ['page'=>1,'text'=>'...'], ... ]
        $segments = $this->extractor->extractPaged($absolutePath);

        // Texte pour hash stable
        $fullTextForHash = trim(implode("\n\n", array_map(
            fn(array $s) => "[PAGE {$s['page']}]\n" . ($s['text'] ?? ''),
            $segments
        )));

        if ($fullTextForHash === '') {
            $output->writeln("<error>Texte vide: impossible de générer l’index.</error>");
            return Command::FAILURE;
        }

        $contentHash = hash('sha256', $fullTextForHash);

        /** @var DocumentRagIndex|null $index */
        $index = $this->em->getRepository(DocumentRagIndex::class)->findOneBy(['document' => $documentRef]);

        if ($index && !$force && $index->getContentHash() === $contentHash) {
            $output->writeln('<info>Index à jour (hash identique). Rien à faire.</info>');
            return Command::SUCCESS;
        }

        // 3) purge anciens chunks
        $output->writeln('<comment>Suppression des anciens chunks…</comment>');
        $this->em->createQuery('DELETE FROM App\Entity\DocumentRagChunk c WHERE c.document = :document')
            ->setParameter('document', $documentRef)
            ->execute();

        $output->writeln('<info>Pages détectées:</info> ' . count($segments));
        $output->writeln('<info>Embedding length:</info> ' . $this->embedder->getEmbeddingLength());

        // 4) chunks + embeddings
        $now = new \DateTimeImmutable();
        $chunkGlobalIndex = 0;
        $persisted = 0;

        foreach ($segments as $seg) {
            $page = (int)($seg['page'] ?? 1);
            $pageText = trim((string)($seg['text'] ?? ''));
            if ($pageText === '') {
                continue;
            }

            $pageChunks = $this->chunkText($pageText, $chunkSize, $overlap);

            foreach ($pageChunks as $chunk) {
                $chunk = trim($chunk);
                if ($chunk === '') continue;

                if ($top > 0 && $persisted >= $top) {
                    break 2;
                }

                $output->writeln(" - Embedding chunk #{$chunkGlobalIndex} (page {$page})");

                $embedding = $this->embedder->embedText($chunk);

                $entity = (new DocumentRagChunk())
                    ->setCourse($documentRef)
                    ->setDocumentId($documentId)
                    ->setChunkIndex($chunkGlobalIndex++)
                    ->setContent($chunk)
                    ->setEmbedding($embedding)
                    ->setCreatedAt($now);

                // ✅ pageNumber (si présent dans ton entity)
                if (method_exists($entity, 'setPageNumber')) {
                    $entity->setPageNumber($page);
                }

                $this->em->persist($entity);
                $persisted++;

                // Batch flush + clear + re-proxy
                if (($persisted % $batch) === 0) {
                    $this->em->flush();
                    $this->em->clear();

                    // Recréer proxy après clear
                    $documentRef = $this->em->getReference(document::class, $documentId);
                }
            }
        }

        // 5) index (recharge après clear)
        $index = $this->em->getRepository(DocumentRagIndex::class)->findOneBy(['document' => $documentRef]);

        if (!$index) {
            $index = new DocumentRagIndex();
            $index->setDocument($documentRef);
            $this->em->persist($index);
        }

        $index->setContentHash($contentHash);
        $index->setUpdatedAt($now);

        $this->em->flush();

        $output->writeln('<info>✅ Index généré.</info>');
        $output->writeln('Chunks persistés: ' . $persisted);
        $output->writeln('Hash: ' . $contentHash);

        return Command::SUCCESS;
    }

    private function resolveBookFilePath(Document $document): string
    {
        $storedName = trim((string) $document->getFilename());
        if ($storedName === '') {
            throw new \RuntimeException('document n’a pas de storedName.');
        }

        $path = rtrim($this->documentsUploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $storedName;

        $real = realpath($path);
        if ($real === false || !is_file($real)) {
            throw new \RuntimeException("Fichier introuvable sur disque: {$path}");
        }

        return $real;
    }

    private function chunkText(string $text, int $chunkSize, int $overlap): array
    {
        $text = preg_replace("/\r\n|\r/", "\n", $text);
        $text = preg_replace("/[ \t]+/", " ", $text);

        $len = mb_strlen($text);
        $chunks = [];
        $start = 0;

        while ($start < $len) {
            $end = min($len, $start + $chunkSize);
            $slice = mb_substr($text, $start, $end - $start);

            if ($end < $len) {
                $pos = max(mb_strrpos($slice, "."), mb_strrpos($slice, "\n"));
                if ($pos !== false && $pos > (int)($chunkSize * 0.6)) {
                    $slice = mb_substr($slice, 0, $pos + 1);
                    $end = $start + mb_strlen($slice);
                }
            }

            $chunks[] = $slice;

            if ($end >= $len) break;
            $start = max(0, $end - $overlap);
        }

        return $chunks;
    }
}
