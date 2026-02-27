<?php

namespace App\Command;

use App\Service\DocumentRagStrictQaService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:rag:ask-document',
    description: 'Pose une ou plusieurs questions en RAG strict sur un document'
)]
final class DocumentRagAskCommand extends Command
{
    public function __construct(private readonly DocumentRagStrictQaService $rag)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('documentId', InputArgument::REQUIRED, 'ID du Document')
            ->addArgument('questions', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Questions (si vide, utilise le preset)')
            ->addOption('topK',    null, InputOption::VALUE_REQUIRED, 'Nb chunks', 8)
            ->addOption('minScore',null, InputOption::VALUE_REQUIRED, 'Seuil pertinence', 0.20)
            ->addOption('preset',  null, InputOption::VALUE_NONE,    'Utilise la liste de questions prédéfinies')
            ->addOption('out',     null, InputOption::VALUE_REQUIRED, 'Fichier de sortie .txt (optionnel)');
    }

    // ---------------------------------------------------------------
    // Questions prédéfinies (adaptez selon votre contexte métier)
    // ---------------------------------------------------------------
    private function presetQuestions(): array
    {
        return [
            'Sujet principal'        => 'De quoi parle ce document ? Quel est son sujet principal ?',
            'Secteur / domaine'      => 'Quel est le secteur ou le domaine d\'activité décrit dans ce document ?',
            'Acteurs / porteurs'     => 'Qui sont les acteurs, porteurs ou responsables mentionnés dans ce document ?',
            'Dates clés'             => 'Quelles sont les dates ou périodes importantes mentionnées dans ce document ?',
            'Chiffres clés'          => 'Quels sont les chiffres, montants ou indicateurs chiffrés présents dans ce document ?',
            'Objectifs'              => 'Quels sont les objectifs ou buts décrits dans ce document ?',
            'Problèmes / enjeux'     => 'Quels problèmes, risques ou enjeux sont mentionnés dans ce document ?',
            'Solutions / dispositifs'=> 'Quelles solutions, dispositifs ou actions sont proposés dans ce document ?',
            'Financement'            => 'Quels financements, subventions ou budgets sont mentionnés dans ce document ?',
            'Résultats / conclusions'=> 'Quels résultats, conclusions ou recommandations sont présentés dans ce document ?',
        ];
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io         = new SymfonyStyle($input, $output);
        $documentId = (int) $input->getArgument('documentId');
        $topK       = (int) $input->getOption('topK');
        $minScore   = (float) $input->getOption('minScore');
        $outFile    = $input->getOption('out');

        // ---- Résoudre les questions ----
        $rawQuestions = (array) $input->getArgument('questions');
        $usePreset    = $input->getOption('preset') || empty($rawQuestions);

        if ($usePreset) {
            $questions = $this->presetQuestions();
        } else {
            // Questions passées en arguments => labels auto "Q1", "Q2"...
            $questions = [];
            foreach ($rawQuestions as $i => $q) {
                $questions['Q' . ($i + 1)] = trim($q);
            }
        }

        $questions = array_filter($questions, fn($q) => trim($q) !== '');

        if (!$questions) {
            $io->error('Aucune question à poser.');
            return Command::FAILURE;
        }

        $output->writeln('');
        $io->title(sprintf('RAG STRICT — Document #%d', $documentId));
        $output->writeln(sprintf('<comment>Questions :</comment> %d | <comment>topK :</comment> %d | <comment>minScore :</comment> %.2f', count($questions), $topK, $minScore));
        $output->writeln(str_repeat('─', 90));

        // ---- Progress bar ----
        $progress = new ProgressBar($output, count($questions));
        $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %message%');
        $progress->setMessage('Démarrage…');
        $progress->start();

        $results  = [];
        $found    = 0;
        $notFound = 0;

        foreach ($questions as $label => $question) {
            $progress->setMessage($label);

            $res    = $this->rag->ask($documentId, $question, $topK, $minScore);
            $answer = trim((string)($res['answer'] ?? ''));

            $isRefusal = str_contains($answer, 'Je ne sais pas à partir des documents fournis');

            $sources  = is_array($res['sources'] ?? null) ? $res['sources'] : [];
            $usedIds  = is_array($res['used_chunk_ids'] ?? null) ? $res['used_chunk_ids'] : [];
            $usedPages = $this->resolveUsedPages($sources, $usedIds);

            $results[$label] = [
                'question'  => $question,
                'answer'    => $answer,
                'refusal'   => $isRefusal,
                'usedIds'   => $usedIds,
                'usedPages' => $usedPages,
                'sources'   => $sources,
            ];

            $isRefusal ? $notFound++ : $found++;
            $progress->advance();
        }

        $progress->finish();
        $output->writeln("\n");

        // ---- Affichage des résultats ----
        foreach ($results as $label => $r) {
            $output->writeln(sprintf('<info>▶ %s</info>', $label));
            $output->writeln(sprintf('<comment>  Q:</comment> %s', $r['question']));

            if ($r['refusal']) {
                $output->writeln('  <fg=red>✗ Non trouvé dans le document.</>');
            } else {
                // Affiche la réponse (multilignes indentées)
                foreach (explode("\n", $r['answer']) as $line) {
                    $output->writeln('  ' . $line);
                }

                // Sources
                if ($r['usedPages']) {
                    $output->writeln(sprintf(
                        '  <comment>↳ Pages :</comment> %s',
                        implode(', ', array_map(fn($p) => "p.$p", $r['usedPages']))
                    ));
                } elseif ($r['usedIds']) {
                    $output->writeln(sprintf(
                        '  <comment>↳ Chunks :</comment> %s',
                        implode(', ', array_map(fn($id) => "#$id", $r['usedIds']))
                    ));
                }
            }

            $output->writeln('');
        }

        $output->writeln(str_repeat('─', 90));
        $io->success(sprintf(
            'Terminé. %d question(s) — %d réponse(s) trouvée(s) — %d non trouvée(s).',
            count($questions), $found, $notFound
        ));

        // ---- Export fichier optionnel ----
        if ($outFile) {
            $this->exportToFile($outFile, $documentId, $results, $io);
        }

        return Command::SUCCESS;
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function resolveUsedPages(array $sources, array $usedIds): array
    {
        $pages = [];
        $usedIdsStr = array_map('strval', $usedIds);

        foreach ($sources as $s) {
            $id = $s['id'] ?? null;
            if ($id === null) continue;
            if (!in_array((string)$id, $usedIdsStr, true)) continue;

            $page = $s['pageNumber'] ?? $s['page'] ?? null;
            if (is_numeric($page) && (int)$page > 0) {
                $pages[] = (int)$page;
            }
        }

        $pages = array_values(array_unique($pages));
        sort($pages);
        return $pages;
    }

    private function exportToFile(string $path, int $documentId, array $results, SymfonyStyle $io): void
    {
        $lines = [];
        $lines[] = sprintf('RAG STRICT — Document #%d', $documentId);
        $lines[] = sprintf('Exporté le %s', date('Y-m-d H:i:s'));
        $lines[] = str_repeat('=', 90);
        $lines[] = '';

        foreach ($results as $label => $r) {
            $lines[] = "▶ $label";
            $lines[] = "  Q: {$r['question']}";

            if ($r['refusal']) {
                $lines[] = '  ✗ Non trouvé dans le document.';
            } else {
                foreach (explode("\n", $r['answer']) as $line) {
                    $lines[] = '  ' . $line;
                }
                if ($r['usedPages']) {
                    $lines[] = '  ↳ Pages : ' . implode(', ', array_map(fn($p) => "p.$p", $r['usedPages']));
                } elseif ($r['usedIds']) {
                    $lines[] = '  ↳ Chunks : ' . implode(', ', array_map(fn($id) => "#$id", $r['usedIds']));
                }
            }

            $lines[] = '';
        }

        file_put_contents($path, implode("\n", $lines));
        $io->note("Résultats exportés dans : $path");
    }
}