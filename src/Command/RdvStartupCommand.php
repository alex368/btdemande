<?php

namespace App\Command;

use App\Entity\Book;
use App\Entity\FundingRequest;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\DocumentRepository;
use App\Repository\FundingRequestRepository;
use App\Repository\UserRepository;
use App\Service\BookRagStrictQaService;
use App\Service\DocumentRagStrictQaService;
use App\Service\RdvStartupWordExporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:rag:rdv-startup',
    description: 'Remplit la trame RDV START-UP via RAG strict, en filtrant par nom de projet, et exporte en Word'
)]
final class RdvStartupCommand extends Command
{
    public function __construct(
        private readonly DocumentRepository $documents,
        private readonly UserRepository $users,
        private readonly FundingRequestRepository $fundingRequests,
        private readonly DocumentRagStrictQaService $rag,
        private readonly RdvStartupWordExporter $wordExporter,
        private readonly string $projectDir, // injecté via services.yaml: %kernel.project_dir%
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('project', null, InputOption::VALUE_REQUIRED, 'Nom du projet (obligatoire)')
            ->addOption('topK', null, InputOption::VALUE_REQUIRED, 'Nb chunks par document', 10)
            ->addOption('minScore', null, InputOption::VALUE_REQUIRED, 'Seuil pertinence', 0.20)
            ->addOption('maxDocs', null, InputOption::VALUE_REQUIRED, 'Nb max de documents utilisés', 5)
            ->addOption('out', null, InputOption::VALUE_REQUIRED, 'Chemin fichier .docx de sortie (optionnel)');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $project = trim((string)$input->getOption('project'));
        if ($project === '') {
            $io->error('Option --project obligatoire. Ex: --project="Fidelissimo"');
            return Command::FAILURE;
        }

        $topK     = max(1, (int)$input->getOption('topK'));
        $minScore = (float)$input->getOption('minScore');
        $maxDocs  = max(1, (int)$input->getOption('maxDocs'));

        // // 1) Filtrer documents par nom projet (originalName)
        // /** @var Document[] $docs */
        // $docs = $this->documents->searchByOriginalName($project, $maxDocs);


   $customer = $this->users->find(2);

if (!$customer) {
    $io->error('Utilisateur #2 introuvable');
    return Command::FAILURE;
}

$docs = [];

foreach ($customer->getCampanies() as $company) {

    $requestDemand = $this->fundingRequests->findOneBy([
        'campany' => $company
    ]);

  

    if (!$requestDemand) {
        continue;
    }

    foreach ($requestDemand->getDocuments() as $document) {
        $docs[] = $document;
    }
}



        if (!$docs) {
            $io->error("Aucun document trouvé dont originalName contient: {$project}");
            return Command::FAILURE;
        }

        $output->writeln('');
        $output->writeln('<info>RDV START-UP</info>');
        $output->writeln('<comment>Projet:</comment> ' . $project);
        $output->writeln('<comment>Documents pris en compte:</comment>');
        foreach ($docs as $d) {
            $output->writeln(' - [Book ' . $d->getId() . '] ' . ($d->getFilename() ?? ''));
        }
        $output->writeln(str_repeat('-', 90));

        // 2) Liste des questions (une par item). Le "Nom du projet" est injecté partout.
        $questions = [
            'projet' => "Fait une analyse fondamentale ?",
             'projet' => "de quoi parle le document ?",
            // 'Nom du projet' => "Confirme le nom exact du projet dans les documents pour « {$project} ». Réponds par le nom.",
            // 'Nom du porteur' => "Pour le projet « {$project} », quel est le nom du porteur ?",
            // 'Coordonnées tel & mail' => "Pour le projet « {$project} », quels sont le téléphone et l’email ?",
            // 'Date de création (ou envisagée)' => "Pour le projet « {$project} », quelle est la date de création (ou date envisagée) ?",
            // 'Lieu du siège ou de la commercialisation' => "Pour le projet « {$project} », quel est le lieu du siège ou de commercialisation ?",

            // 'Descriptif du projet' => "Pour le projet « {$project} », donne un descriptif clair du projet.",
            // 'Secteur' => "Pour le projet « {$project} », quel est le secteur (indshstrie / domaine) ?",
            // 'Stade d’avancement' => "Pour le projet « {$project} », quel est le stade d’avancement (idée, MVP, traction, etc.) tel qu’indiqué ?",
            // 'Equipe & Actionnariat' => "Pour le projet « {$project} », décris l’équipe et l’actionnariat (qui détient quoi) si présent.",
            // 'Incubation ou accompagnement' => "Pour le projet « {$project} », y a-t-il une incubation / un accompagnement ? lequel ?",

            // 'Type d’Innovation & domaine' => "Pour le projet « {$project} », quel est le type d’innovation et le domaine ?",
            // 'Stade d’avancement de l’innovation' => "Pour le projet « {$project} », où en est l’innovation (prototype, R&D, brevet, etc.) ?",
            // 'Version à venir' => "Pour le projet « {$project} », quelles sont les versions / évolutions à venir mentionnées ?",
            // 'Propriété intellectuelle' => "Pour le projet « {$project} », quelle est la situation de propriété intellectuelle (brevet, marque, code, etc.) ?",

            // 'Business model' => "Pour le projet « {$project} », quel est le business model ?",
            // 'Prix' => "Pour le projet « {$project} », quels sont les prix ou la stratégie de pricing mentionnés ?",
            // 'Concurrence & avantage concurrentiel' => "Pour le projet « {$project} », quels sont les concurrents et l’avantage concurrentiel ?",
            // 'Stratégie commerciale' => "Pour le projet « {$project} », quelle est la stratégie commerciale ?",
            // 'Stratégie marketing & communication' => "Pour le projet « {$project} », quelle est la stratégie marketing et communication ?",

            // 'Besoin de financement' => "Pour le projet « {$project} », quel est le besoin de financement (montants, usages) ?",
            // 'Fonds propres et capital social apportés' => "Pour le projet « {$project} », quels fonds propres / capital social sont apportés ?",
            // 'Financement déjà obtenu' => "Pour le projet « {$project} », quels financements ont déjà été obtenus (subventions, prêts, love money, etc.) ?",
            // 'Prévision financière de CA' => "Pour le projet « {$project} », quelles prévisions de chiffre d’affaires sont indiquées ?",

            // 'Besoins généraux' => "Pour le projet « {$project} », quels besoins généraux sont mentionnés (recrutement, partenaires, technique, etc.) ?",
            // 'Mise en relation potentielle' => "Pour le projet « {$project} », quelles mises en relation potentielles sont souhaitées ?",
            // 'Infos à noter' => "Pour le projet « {$project} », quelles infos importantes faut-il noter ?",
        ];

         
        // 3) Ask par document, et on garde la meilleure réponse (score max)
$io = new SymfonyStyle($input, $output);

$totalSteps = count($questions) * count($docs);
$progress = new ProgressBar($output, max(1, $totalSteps));
$progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %message%');
$progress->setMessage('Initialisation...');
$progress->start();

$answers = [];
$found = 0;
$unknown = 0;

$qIndex = 0;
$qTotal = count($questions);
$docTotal = count($docs);

foreach ($questions as $label => $q) {
    $qIndex++;
    $best = null;

    $docIndex = 0;
    foreach ($docs as $doc) {
        $docIndex++;
        $progress->setMessage("Q{$qIndex}/{$qTotal}: {$label} | Doc {$docIndex}/{$docTotal}");

        $res = $this->rag->ask((int)$doc->getId(), $q, $topK, $minScore);
       
        //dump($res);

        // bestScore = max score des sources candidates
        $bestScore = null;
        $sources = is_array($res['sources'] ?? null) ? $res['sources'] : [];
        foreach ($sources as $s) {
            if (!isset($s['score'])) continue;
            $sc = (float)$s['score'];
            $bestScore = $bestScore === null ? $sc : max($bestScore, $sc);
        }
    //    dump($sources);

        $answerText = trim((string)($res['answer'] ?? ''));
        dump($answerText);

        // On considère "Je ne sais pas..." comme non exploitable
        $isUnknown = ($answerText === '' || str_contains($answerText, 'Je ne sais pas à partir des documents fournis'));

        if (!$isUnknown) {
            if ($best === null || (($bestScore ?? -INF) > ($best['bestScore'] ?? -INF))) {
                $best = [
                    'answer' => $answerText,
                    'bestScore' => $bestScore,
                    'bookId' => (int)$doc->getId(),
                    'docName' => (string)($doc->getFilename() ?? ''),
                ];
            }
        }

        $progress->advance();
    }

    if ($best !== null) {
        $answers[$label] = $best['answer'];
        $found++;
    } else {
        $answers[$label] = "Je ne sais pas à partir des documents fournis.";
        $unknown++;
    }
}

$progress->finish();
$output->writeln("\n");

// Petit résumé d’avancement
$io->success(sprintf(
    'Analyse terminée. Questions: %d | Réponses trouvées: %d | Non trouvées: %d | Docs analysés: %d',
    $qTotal,
    $found,
    $unknown,
    $docTotal
));


        // 4) Sortie finale “naturelle” (une fiche remplie)
        $output->writeln("projet : " . $answers['projet']);
         $output->writeln("projet : " . $answers['projet']);
        // $output->writeln("Nom du projet : " . $answers['Nom du projet']);
        // $output->writeln("Nom du porteur : " . $answers['Nom du porteur']);
        // $output->writeln("Coordonnées tel & mail : " . $answers['Coordonnées tel & mail']);
        // $output->writeln("Date de création (ou envisagée) : " . $answers['Date de création (ou envisagée)']);
        // $output->writeln("Lieu du siège ou de la commercialisation : " . $answers['Lieu du siège ou de la commercialisation']);
        // $output->writeln('');

        // $output->writeln("Projet");
        // $output->writeln("- Descriptif du projet : " . $answers['Descriptif du projet']);
        // $output->writeln("- Secteur : " . $answers['Secteur']);
        // $output->writeln("- Stade d’avancement : " . $answers['Stade d’avancement']);
        // $output->writeln("- Equipe & Actionnariat : " . $answers['Equipe & Actionnariat']);
        // $output->writeln("- Incubation ou accompagnement : " . $answers['Incubation ou accompagnement']);
        // $output->writeln('');

        // $output->writeln("Innovation");
        // $output->writeln("- Type d’Innovation & domaine : " . $answers['Type d’Innovation & domaine']);
        // $output->writeln("- Stade d’avancement de l’innovation : " . $answers['Stade d’avancement de l’innovation']);
        // $output->writeln("- Version à venir : " . $answers['Version à venir']);
        // $output->writeln("- Propriété intellectuelle : " . $answers['Propriété intellectuelle']);
        // $output->writeln('');

        // $output->writeln("Marché");
        // $output->writeln("- Business model : " . $answers['Business model']);
        // $output->writeln("- Prix : " . $answers['Prix']);
        // $output->writeln("- Concurrence & avantage concurrentiel : " . $answers['Concurrence & avantage concurrentiel']);
        // $output->writeln("- Stratégie commerciale : " . $answers['Stratégie commerciale']);
        // $output->writeln("- Stratégie marketing & communication : " . $answers['Stratégie marketing & communication']);
        // $output->writeln('');

        // $output->writeln("Financement");
        // $output->writeln("- Besoin de financement : " . $answers['Besoin de financement']);
        // $output->writeln("- Fonds propres et capital social apportés : " . $answers['Fonds propres et capital social apportés']);
        // $output->writeln("- Financement déjà obtenu : " . $answers['Financement déjà obtenu']);
        // $output->writeln("- Prévision financière de CA : " . $answers['Prévision financière de CA']);
        // $output->writeln('');

        // $output->writeln("- Besoins généraux : " . $answers['Besoins généraux']);
        // $output->writeln("- Mise en relation potentielle : " . $answers['Mise en relation potentielle']);
        // $output->writeln("- Infos à noter : " . $answers['Infos à noter']);
        // $output->writeln('');

        // $output->writeln(str_repeat('-', 90));
        // $output->writeln('<comment>Présentation de l’entreprise / Offre:</comment>');
        // $output->writeln("Accompagnement à la recherche de financement, publics et privés. Spécialiste des aides et subventions publiques. Cartographie des dispositifs, montage des dossiers (subventions, bancaire, levée de fonds). Stratégie d’effet de levier, veille et inscription concours/AAP. Accompagnement création -> développement.");
        // $output->writeln("Modèle: forfait de démarrage 2000€ HT + 10% des subventions obtenues et versées.");
        $output->writeln('');

        return Command::SUCCESS;
    }
}
