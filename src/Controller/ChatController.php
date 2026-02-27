<?php

namespace App\Controller;

use App\Entity\Campany;
use App\Entity\Contact;
use App\Entity\FundingRequest;
use App\Entity\User;
use App\Service\AiEntityKnowledgeService;
use App\Service\DocumentRagStrictQaService;
use App\Service\LlmService;
use App\Service\RdvStartupWordExporter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ChatController extends AbstractController
{
    #[Route('/collaborator/{id}/chat', name: 'app_chat_general', methods: ['GET'])]
    public function index(
        int $id,
        EntityManagerInterface $em
    ): Response {



        $admin = $em->getRepository(User::class)->findOneByIdAndRole($id, 'ROLE_ADMIN');

        $collaborator = $em->getRepository(User::class)->findOneByIdAndRole($id, 'ROLE_COLLABORATOR');


        if ($user = $admin || $user = $collaborator) {
        } else {
            throw $this->createNotFoundException('Utilisateur non trouvé avec le rôle spécifié.');
        }

        return $this->render('chatbot/chatGeneral.html.twig', [
            // 'classroom'   => $classroom,
            // 'course'      => $course,
            // 'sidebarData' => $sidebarService->getSidebarData($classroom),
        ]);
    }

    #[Route('/collaborator/contact/{id}/chat', name: 'app_chat_contact', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function chatSpecialise(
        int $id,
        EntityManagerInterface $em
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Non authentifié.');
        }

        // ✅ {id} = Contact ID
        $contact = $em->getRepository(Contact::class)->find($id);
        if (!$contact) {
            throw $this->createNotFoundException('Contact introuvable.');
        }

        // ✅ Autorisation simple par rôle
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_COLLABORATOR')) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        return $this->render('chatbot/chatSpecialise.html.twig', [
            'contact' => $contact,
            'contactId' => $contact->getId(), // utile pour ton JS endpoint /api/chat-ai/contact/{id}
        ]);
    }




    #[Route('/customer/contact/{id}/chat/{user}', name: 'app_chat_customer', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function chatCustomer(
        int $id,
        int $user,
        EntityManagerInterface $em
    ): Response {



        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Non authentifié.');
        }

        // ✅ {id} = Contact ID
        $user = $em->getRepository(User::class)->find($id);


        $campany = $em->getRepository(Campany::class)->findAll();

        $campanyId = null;
        foreach ($campany as $request) {
    $campanyId = $request->getId();
}
  

        if (!$user) {
            throw $this->createNotFoundException('utilisateur introuvable.');
        }

        // ✅ Autorisation simple par rôle
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_COLLABORATOR')) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        return $this->render('chatbot/chatSpecialiseFolder.html.twig', [
            'contact' => $user,
            'userId' => $user->getId(), // utile pour ton JS endpoint /api/chat-ai/contact/{id}
            'campanyId' => $campanyId, // pour afficher le nom de la société dans le header de Lucy Folder
        ]);
    }




    #[Route('/api/ai/resolve', name: 'api_ai_resolve', methods: ['POST'])]
    public function resolve(Request $request, AiEntityKnowledgeService $knowledge): JsonResponse
    {
        $payload = json_decode($request->getContent() ?: '{}', true);

        $query = trim((string)($payload['query'] ?? ''));
        if ($query === '') {
            return $this->json([
                'ok' => false,
                'error' => 'Missing query'
            ], 400);
        }

        $resolved = $knowledge->resolveLinkFromText($query);

        return $this->json([
            'ok' => true,
            'query' => $query,
            'resolved' => $resolved, // null si rien trouvé
        ]);
    }

    #[Route('/api/ai/index', name: 'api_ai_index', methods: ['GET'])]
    public function indexs(AiEntityKnowledgeService $knowledge): JsonResponse
    {
        // ⚠️ peut être lourd si tu as beaucoup de data
        $docs = $knowledge->buildIndex();

        return $this->json([
            'ok' => true,
            'count' => count($docs),
            'documents' => $docs,
        ]);
    }

    #[Route('/api/chat-ai', name: 'api_chat_ai', methods: ['POST'])]
    public function apiChatAi(
        Request $request,
        LlmService $llmService,
        AiEntityKnowledgeService $knowledge
    ): JsonResponse {
        // ✅ 1) Lire le body brut
        $raw = $request->getContent() ?: '';

        // ✅ 2) Parser JSON sans 400 auto
        $payload = [];
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $payload = $decoded;
            }
        }

        // ✅ 3) Récupérer la question (JSON ou form-data)
        $question = trim((string) ($payload['question'] ?? $request->request->get('question', '')));

        if ($question === '') {
            return $this->json([
                'error' => 'Missing "question" in JSON body',
                'received_raw' => mb_substr($raw, 0, 300),
            ], 400);
        }

        // ✅ 4) Nouveau service : gère directory + recherche + best match
        $knowledgeResult = $knowledge->handleQuestion($question, 8);

        /**
         * $knowledgeResult contient :
         * - action: open_directory | open_item | no_match
         * - directory: {type,url} ou null
         * - resolved: {type,label,url,...} ou null
         * - matches: [] (suggestions)
         */

        // ✅ 5) Construire un contexte utile pour l’IA
        $context = '';

        if (!empty($knowledgeResult['directory']['url'])) {
            $context .= "Page à ouvrir (répertoire) : " . $knowledgeResult['directory']['url'] . "\n";
        }

        if (!empty($knowledgeResult['resolved']['url'])) {
            $context .= "Lien interne exact trouvé : " . $knowledgeResult['resolved']['url'] . "\n";
            $context .= "Label : " . ($knowledgeResult['resolved']['label'] ?? '') . "\n";
            $context .= "Type : " . ($knowledgeResult['resolved']['type'] ?? '') . "\n";
        }

        if (!empty($knowledgeResult['matches'])) {
            $context .= "Suggestions internes :\n";
            foreach ($knowledgeResult['matches'] as $m) {
                $label = $m['label'] ?? '';
                $url   = $m['url'] ?? '';
                $type  = $m['type'] ?? '';
                if ($url) {
                    $context .= "- {$type} | {$label} => {$url}\n";
                }
            }
        }

        // ✅ 6) Prompt IA (Lucy)
        $prompt = <<<PROMPT
        Tu es Lucy, l'assistante du CRM.
        Tu dois aider l'utilisateur à naviguer dans l'application avec les liens internes.

        Question utilisateur :
        {$question}

        Contexte interne (liens internes trouvés) :
        {$context}

        Règles :
        - Réponds en français.
        - Réponse courte et claire.
        - Si un lien existe, donne-le tel quel.
        - Si un répertoire est pertinent, donne le lien du répertoire.
        - Si plusieurs suggestions existent, propose 3 liens max.
        PROMPT;

        // ✅ 7) Appel LLM avec sécurité
        try {
            $answer = $llmService->generate($prompt, [
                'max_tokens' => 600,
                'temperature' => 0.2,
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'LLM error',
                'details' => $e->getMessage(),
                'knowledge' => $knowledgeResult,
            ], 500);
        }

        // ✅ 8) JSON final (front + debug)
        return $this->json([
            'answer' => trim($answer),
            'knowledge' => $knowledgeResult, // ✅ remplace "resolved"
        ]);
    }








    #[Route('/api/chat-ai/contact/{id}', name: 'api_chat_ai_contact', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function apiChatAiContact(
        int $id,
        Request $request,
        LlmService $llmService,
        EntityManagerInterface $em,
        AiEntityKnowledgeService $knowledge,
        LoggerInterface $logger
    ): JsonResponse {
        try {
            $raw = $request->getContent() ?: '';
            $payload = [];

            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $payload = $decoded;
                }
            }

            $question = trim((string)($payload['question'] ?? $request->request->get('question', '')));
            if ($question === '') {
                return $this->json([
                    'error' => 'Missing "question" in JSON body',
                    'received_raw' => mb_substr($raw, 0, 300),
                ], 400);
            }



            // IMPORTANT: sécuriser contact (sinon null->getFirstname() => 500 HTML)
            $contact = $em->getRepository(Contact::class)->find($id);
            // $customer = 


            if (!$contact) {
                return $this->json([
                    'error' => "Contact introuvable (id={$id})",
                    'contactId' => $id,
                ], 404);
            }

            $customerDetails = $knowledge->getCustomerDetails($id, 20);
            if (!$customerDetails) {
                return $this->json([
                    'error' => "Données CRM introuvables (id={$id})",
                    'contactId' => $id,
                    'directoryUrl' => $knowledge->customerDirectoryUrl(),
                ], 404);
            }

            $journal = $knowledge->buildCustomerJournal($customerDetails, 8);

            $n = trim((string)($contact->getFirstname() ?? ''));
            if ($n === '') {
                $n = 'Lucy';
            }

            $prompt = <<<PROMPT
Tu es {$n}, assistante CRM.

QUESTION UTILISATEUR :
{$question}

JOURNAL CRM (source unique) :
{$journal}

Consignes (style naturel, texte brut uniquement) :
- Pas de Markdown. Pas de lien cliquable. Pas de bloc "Détails contact".
Ne donne jamais le lien de la fiche contact (pas de "Fiche :", pas d'URL).
- Si la question demande ce qui "s'est dit" / un résumé :
  - commence par "Il s'est dit :" puis 1 à 8 lignes, chacune commence par "- ".
  - chaque ligne reformule fidèlement "desc=" des activités (si desc=N/A -> "Aucun détail noté.").
- Si la question demande les opportunités :
  - commence par "Opportunités :" puis 1 à 8 lignes "- " avec date + stage + lead.
- Ensuite, ajoute une seule ligne "Action : ..."
  - si une activité contient l'idée "envoyer un devis" ou "devis" et EMAIL_CONTACT n'est pas N/A :
    Action : Envoyer le devis à EMAIL_CONTACT
  - sinon si EMAIL_CONTACT est N/A :
    Action : Ajouter l'email du contact puis envoyer le devis
  - sinon :
    Action : Suivre le dossier avec le contact
PROMPT;

            $answer = $llmService->generate($prompt, [
                'max_tokens' => 650,
                'temperature' => 0.2,
            ]);

            return $this->json([
                'answer' => trim((string) $answer),
                'contactId' => $id,
                // en prod, évite de renvoyer customerDetails si c’est gros / sensible
                // 'customerDetails' => $customerDetails,
            ]);
        } catch (\Throwable $e) {
            $logger->error('api_chat_ai_contact failed', [
                'contactId' => $id,
                'exception' => $e,
            ]);

            // JSON garanti même si exception non prévue
            return $this->json([
                'error' => 'Erreur serveur',
                'details' => $e->getMessage(),
                'contactId' => $id,
            ], 500);
        }
    }



#[Route('/api/chat-ai/customer/{id}', name: 'api_chat_ai_customer', methods: ['POST'], requirements: ['id' => '\d+'])]
public function apiChatAiCustomer(
    int $id,
    Request $request,
    LlmService $llmService,
    EntityManagerInterface $em,
    AiEntityKnowledgeService $knowledge,
    DocumentRagStrictQaService $qaService,
    LoggerInterface $logger
): JsonResponse {
    try {
        $raw     = $request->getContent() ?: '';
        $payload = [];

        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $payload = $decoded;
            }
        }

        $question = trim((string)($payload['question'] ?? $request->request->get('question', '')));
        $mode     = trim((string)($payload['mode'] ?? 'libre'));

        // En mode dossier la question est optionnelle
        if ($question === '' && $mode !== 'dossier') {
            return $this->json([
                'error'        => 'Missing "question" in JSON body',
                'received_raw' => mb_substr($raw, 0, 300),
            ], 400);
        }

        $customer = $em->getRepository(User::class)->find($id);
        if (!$customer) {
            return $this->json(['error' => "Client #{$id} introuvable"], 404);
        }

        // ── Chargement commun : company + fundingRequest + documents ──────────
        $requestDemand = null;
        $legalName     = 'le projet';

        foreach ($customer->getCampanies() as $company) {
            $legalName     = $company->getLegalName() ?: $legalName;
            $requestDemand = $em->getRepository(FundingRequest::class)
                ->findOneBy(['campany' => $company]);
            if ($requestDemand) break;
        }

        if (!$requestDemand) {
            return $this->json(['error' => 'Aucune demande de financement trouvée'], 404);
        }

        $documents = $requestDemand->getDocuments()->toArray();
        if (!$documents) {
            return $this->json(['error' => 'Aucun document trouvé'], 404);
        }

        $topK     = (int)($payload['topK']     ?? 10);
        $minScore = (float)($payload['minScore'] ?? 0.20);

        // ── MODE "simple" : une question sur tous les docs, meilleure réponse ─
        if ($mode === 'simple') {

            $best = null;

            foreach ($documents as $doc) {
                $res = $qaService->ask((int)$doc->getId(), $question, $topK, $minScore);

                $answerText = trim((string)($res['answer'] ?? ''));
                $isRefusal  = $answerText === ''
                    || str_contains($answerText, 'Je ne sais pas à partir des documents fournis');

                if ($isRefusal) continue;

                $bestScore = null;
                foreach ($res['sources'] ?? [] as $s) {
                    if (!isset($s['score'])) continue;
                    $sc        = (float)$s['score'];
                    $bestScore = $bestScore === null ? $sc : max($bestScore, $sc);
                }

                if ($best === null || ($bestScore ?? -INF) > ($best['score'] ?? -INF)) {
                    $best = [
                        'answer'  => $answerText,
                        'score'   => $bestScore,
                        'docId'   => $doc->getId(),
                        'sources' => $res['sources']    ?? [],
                        'pages'   => $res['used_pages'] ?? [],
                    ];
                }
            }

            if ($best === null) {
                return $this->json([
                    'mode'     => 'simple',
                    'project'  => $legalName,
                    'question' => $question,
                    'answer'   => 'Je ne sais pas à partir des documents fournis.',
                    'found'    => false,
                ]);
            }

            return $this->json([
                'mode'     => 'simple',
                'project'  => $legalName,
                'question' => $question,
                'answer'   => $best['answer'],
                'score'    => $best['score'],
                'docId'    => $best['docId'],
                'pages'    => $best['pages'],
                'found'    => true,
            ]);
        }

        // ── MODE "dossier" : multi-questions RAG ──────────────────────────────
        if ($mode === 'dossier') {

            $questions = [
                // 'Nom du projet'            => "Confirme le nom exact du projet « {$legalName} ».",
                // 'Nom du porteur'           => "Pour le projet « {$legalName} », quel est le nom du porteur de projet ?",
                // 'Coordonnées'              => "Pour le projet « {$legalName} », quels sont le téléphone et l'email de contact ?",
                // 'Date de création'         => "Pour le projet « {$legalName} », quelle est la date de création ou date envisagée ?",
                // 'Lieu du siège'            => "Pour le projet « {$legalName} », quel est le lieu du siège ou de commercialisation ?",
                'Descriptif'               => "Pour le projet « {$legalName} », donne un descriptif clair du projet.",
                'Secteur'                  => "Pour le projet « {$legalName} », quel est le secteur ou domaine d'activité ?",
                // "Stade d'avancement"       => "Pour le projet « {$legalName} », quel est le stade d'avancement (idée, MVP, traction…) ?",
                // 'Équipe et actionnariat'   => "Pour le projet « {$legalName} », décris l'équipe et l'actionnariat.",
                // 'Incubation'               => "Pour le projet « {$legalName} », y a-t-il une incubation ou un accompagnement ?",
                // "Type d'innovation"        => "Pour le projet « {$legalName} », quel est le type d'innovation et le domaine ?",
                // 'Stade innovation'         => "Pour le projet « {$legalName} », où en est l'innovation (prototype, R&D, brevet…) ?",
                // 'Propriété intellectuelle' => "Pour le projet « {$legalName} », quelle est la situation en propriété intellectuelle ?",
                // 'Business model'           => "Pour le projet « {$legalName} », quel est le business model ?",
                // 'Prix'                     => "Pour le projet « {$legalName} », quels sont les prix ou la stratégie de pricing ?",
                // 'Concurrence'              => "Pour le projet « {$legalName} », quels sont les concurrents et l'avantage concurrentiel ?",
                // 'Stratégie commerciale'    => "Pour le projet « {$legalName} », quelle est la stratégie commerciale ?",
                // 'Stratégie marketing'      => "Pour le projet « {$legalName} », quelle est la stratégie marketing et communication ?",
                // 'Besoin de financement'    => "Pour le projet « {$legalName} », quel est le besoin de financement (montants, usages) ?",
                // 'Fonds propres'            => "Pour le projet « {$legalName} », quels fonds propres ou capital social sont apportés ?",
                // 'Financement obtenu'       => "Pour le projet « {$legalName} », quels financements ont déjà été obtenus ?",
                // 'Prévisions CA'            => "Pour le projet « {$legalName} », quelles sont les prévisions de chiffre d'affaires ?",
                // 'Besoins généraux'         => "Pour le projet « {$legalName} », quels besoins généraux sont mentionnés ?",
                // 'Mises en relation'        => "Pour le projet « {$legalName} », quelles mises en relation sont souhaitées ?",
                // 'Infos à noter'            => "Pour le projet « {$legalName} », quelles informations importantes faut-il noter ?",
            ];

            $answers  = [];
            $found    = 0;
            $notFound = 0;

            foreach ($questions as $label => $q) {
                $best = null;

                foreach ($documents as $doc) {
                    $res = $qaService->ask((int)$doc->getId(), $q, $topK, $minScore);

                    $answerText = trim((string)($res['answer'] ?? ''));
                    $isRefusal  = $answerText === ''
                        || str_contains($answerText, 'Je ne sais pas à partir des documents fournis');

                    if ($isRefusal) continue;

                    $bestScore = null;
                    foreach ($res['sources'] ?? [] as $s) {
                        if (!isset($s['score'])) continue;
                        $sc        = (float)$s['score'];
                        $bestScore = $bestScore === null ? $sc : max($bestScore, $sc);
                    }

                    if ($best === null || ($bestScore ?? -INF) > ($best['score'] ?? -INF)) {
                        $best = [
                            'answer' => $answerText,
                            'score'  => $bestScore,
                            'docId'  => $doc->getId(),
                        ];
                    }
                }

                if ($best !== null) {
                    $answers[$label] = $best['answer'];
                    $found++;
                } else {
                    $answers[$label] = null;
                    $notFound++;
                }
            }

            return $this->json([
                'mode'      => 'dossier',
                'project'   => $legalName,
                'found'     => $found,
                'not_found' => $notFound,
                'answers'   => $answers,
            ]);
        }

        // ── MODE "libre" : question libre sur tous les docs ───────────────────
        $best = null;

        foreach ($documents as $doc) {
            $res = $qaService->ask((int)$doc->getId(), $question, $topK, $minScore);

            $answerText = trim((string)($res['answer'] ?? ''));
            $isRefusal  = $answerText === ''
                || str_contains($answerText, 'Je ne sais pas à partir des documents fournis');

            if ($isRefusal) continue;

            $bestScore = null;
            foreach ($res['sources'] ?? [] as $s) {
                if (!isset($s['score'])) continue;
                $sc        = (float)$s['score'];
                $bestScore = $bestScore === null ? $sc : max($bestScore, $sc);
            }

            if ($best === null || ($bestScore ?? -INF) > ($best['score'] ?? -INF)) {
                $best = [
                    'answer' => $answerText,
                    'score'  => $bestScore,
                    'docId'  => $doc->getId(),
                    'pages'  => $res['used_pages'] ?? [],
                ];
            }
        }

        if ($best === null) {
            return $this->json([
                'mode'      => 'libre',
                'project'   => $legalName,
                'question'  => $question,
                'answer'    => 'Je ne sais pas à partir des documents fournis.',
                'found'     => false,
                'contactId' => $id,
            ]);
        }

        return $this->json([
            'mode'      => 'libre',
            'project'   => $legalName,
            'question'  => $question,
            'answer'    => $best['answer'],
            'score'     => $best['score'],
            'docId'     => $best['docId'],
            'pages'     => $best['pages'],
            'found'     => true,
            'contactId' => $id,
        ]);

    } catch (\Throwable $e) {
        $logger->error('api_chat_ai_contact failed', [
            'contactId' => $id,
            'exception' => $e,
        ]);

        return $this->json([
            'error'     => 'Erreur serveur',
            'details'   => $e->getMessage(),
            'contactId' => $id,
        ], 500);
    }
}

}
