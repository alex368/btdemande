<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\CourseContent;
use App\Entity\CourseRagChunk;
use App\Entity\ClassroomMembership;
use App\Entity\Contact;
use App\Entity\FundingMechanism;
use App\Entity\Partnership;
use App\Entity\Product;
use App\Entity\User;
use App\Model\LlmUserContext;
use App\Service\AiEntityKnowledgeService;
use App\Service\DashboardService;
use App\Service\LlmService;
use App\Service\RagTestService;
use App\Service\SidebarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security as SecurityBundleSecurity;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\Event;

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





        // dd($customer);


        // $course = $em->getRepository(Course::class)->find($id);

        return $this->render('chatbot/chatGeneral.html.twig', [
            // 'classroom'   => $classroom,
            // 'course'      => $course,
            // 'sidebarData' => $sidebarService->getSidebarData($classroom),
        ]);
    }




    #[Route('/collaborator/{id}/chat', name: 'app_chat', methods: ['GET'])]
    public function chatSpecialise(
        int $id,
        EntityManagerInterface $em
    ): Response {



        $user = $em->getRepository(User::class)->findAll();
        $customer = $em->getRepository(Contact::class)->findAll();
        $product = $em->getRepository(Product::class)->findAll();
        $event = $em->getRepository(Event::class)->findAll();
        $financementMechanism = $em->getRepository(FundingMechanism::class)->findAll();
        $partnership = $em->getRepository(Partnership::class)->findAll();



        // dd($customer);


        // $course = $em->getRepository(Course::class)->find($id);

        return $this->render('chatbot/chat.html.twig', [
            // 'classroom'   => $classroom,
            // 'course'      => $course,
            // 'sidebarData' => $sidebarService->getSidebarData($classroom),
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


    // #[Route('/api/chat-ai', name: 'api_chat_ai', methods: ['POST'])]
    // public function apiChatAi(
    //     Request $request,
    //     EntityManagerInterface $em,
    //     LlmService $llmService,
    //     RagTestService $ragTestService,
    //     SecurityBundleSecurity $security
    // ): JsonResponse {
    //     try {
    //         set_time_limit(8000);

    //         $user = $security->getUser();
    //         if (!$user) {
    //             return $this->jsonError('Unauthorized', 401);
    //         }

    //         $data = json_decode($request->getContent(), true);
    //         if (!is_array($data)) {
    //             return $this->jsonError('Invalid JSON body', 400);
    //         }

    //         $courseId = (int)($data['courseId'] ?? 0);
    //         $question = trim((string)($data['question'] ?? ''));
    //         $mode     = (string)($data['mode'] ?? 'explication');
    //         $engine   = (string)($data['engine'] ?? 'strict');

    //         $userAnswer       = $data['userAnswer'] ?? null;
    //         $previousQuestion = $data['previousQuestion'] ?? null;

    //         if ($courseId <= 0 || $question === '') {
    //             return $this->jsonError('Missing courseId or question', 400);
    //         }

    //         /** @var Course|null $course */
    //         $course = $em->getRepository(Course::class)->find($courseId);
    //         if (!$course) {
    //             return $this->jsonError('Course not found', 404);
    //         }

    //         $membership = $em->getRepository(ClassroomMembership::class)->findOneBy([
    //             'user'      => $user,
    //             'classroom' => $course->getClassroom(),
    //         ]);

    //         if (!$membership) {
    //             return $this->jsonError('Access denied (not a classroom member)', 403);
    //         }

    //         // Normalisation UI -> backend
    //         $mode = $this->normalizeMode($mode);

    //         // ===========================
    //         // 🔍 MODE STRICT (RAG)
    //         // ===========================
    //         if ($engine === 'strict') {
    //             try {
    //                 // ✅ un seul appel (important)
    //                 $payload = $ragTestService->answerFromCoursePayload(
    //                     courseId: $courseId,
    //                     question: $question,
    //                     mode: $mode,
    //                     topK: 8,
    //                     output: new NullOutput()
    //                 );

    //                 $answer = (string)($payload['answer'] ?? '');

    //                 $chunkCount = (int)$em->getRepository(CourseRagChunk::class)->countByCourseId($courseId);
    //                 if ($chunkCount === 0) {
    //                     return new JsonResponse([
    //                         'status'       => 'indexing',
    //                         'answer'       => 'Indexation en cours… veuillez patienter.',
    //                         'retryAfterMs' => 2000,
    //                     ], 200);
    //                 }

    //                 $sources = $this->buildSourcesFromAnswer($answer, $course, $em);

    //                 return new JsonResponse([
    //                     'answer'   => $answer,
    //                     'sources'  => $sources,
    //                     'ragIndex' => $payload['ragIndex'] ?? [
    //                         'indexed'    => true,
    //                         'chunkCount' => $chunkCount,
    //                         'updatedAt'  => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
    //                     ],
    //                 ], 200);

    //             } catch (\Throwable $e) {
    //                 return new JsonResponse([
    //                     'error'   => 'Erreur RAG strict',
    //                     'details' => $e->getMessage(),
    //                 ], 500);
    //             }
    //         }

    //         // ===========================
    //         // 🧠 MODE LLM (non strict)
    //         // ===========================
    //         $contents = $em->getRepository(CourseContent::class)->findBy(['course' => $course]);
    //         if (!$contents) {
    //             return $this->jsonError('No course content available', 404);
    //         }

    //         $contextText = '';
    //         foreach ($contents as $c) {
    //             $contextText .= $c->getTitle() . "\n" . $c->getContent() . "\n\n";
    //         }

    //         $context = new LlmUserContext();

    //         $answer = match ($mode) {
    //             'quiz' => ($userAnswer && $previousQuestion)
    //                 ? $llmService->checkQuizAnswer($userAnswer, $previousQuestion, $context, $contextText)
    //                 : $llmService->generateQuizQuestion($context, $contextText),

    //             'calcul' => $llmService->resolveCalcul($question, $context, $contextText),

    //             'summary' => $llmService->generateSummary($contextText, $context, $contextText),

    //             default => $llmService->modeEtudeDeCas($question, $context, $contextText),
    //         };

    //         return new JsonResponse([
    //             'answer'  => (string)$answer,
    //             'sources' => [],
    //         ], 200);

    //     } catch (\Throwable $e) {
    //         return new JsonResponse([
    //             'error'   => 'Erreur serveur (exception)',
    //             'details' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // private function jsonError(string $message, int $status): JsonResponse
    // {
    //     return new JsonResponse(['error' => $message], $status);
    // }

    // private function normalizeMode(string $mode): string
    // {
    //     $mode = trim(mb_strtolower($mode));

    //     return match ($mode) {
    //         'quizz'   => 'quiz',
    //         'quiz'    => 'quiz',
    //         'resume'  => 'summary',
    //         'summary' => 'summary',
    //         default   => $mode,
    //     };
    // }

    // private function buildSourcesFromAnswer(
    //     string $answer,
    //     Course $course,
    //     EntityManagerInterface $em
    // ): array {
    //     $sources = [];

    //     preg_match_all(
    //         '/\[EXTRACT\s+(\d+)\s*-\s*page\s*([0-9]+(?:-[0-9]+)?)\]/i',
    //         $answer,
    //         $matches,
    //         PREG_SET_ORDER
    //     );

    //     foreach ($matches as $m) {
    //         $extractNum = (int)$m[1];
    //         $page       = (string)$m[2];

    //         $chunk = $em->getRepository(CourseRagChunk::class)->findOneBy([
    //             'course'     => $course,
    //             'chunkIndex' => $extractNum - 1,
    //         ]);

    //         if (!$chunk) {
    //             continue;
    //         }

    //         $sources[] = [
    //             'extract' => $extractNum,
    //             'page'    => $page,
    //             'snippet' => mb_substr(trim((string)$chunk->getContent()), 0, 400),
    //         ];
    //     }

    //     // unique
    //     $unique = [];
    //     foreach ($sources as $s) {
    //         $key = ($s['extract'] ?? '') . '|' . ($s['page'] ?? '') . '|' . ($s['snippet'] ?? '');
    //         $unique[$key] = $s;
    //     }

    //     return array_values($unique);
    // }




    // #[Route('/api/chat-ai', name: 'api_chat_ai', methods: ['POST'])]
    // public function apiChatAi(
    //     Request $request,
    //     EntityManagerInterface $em,
    //     LlmService $llmService,
    //     SecurityBundleSecurity $security,
    // ): JsonResponse {
    //     set_time_limit(80000); // ⚡ très long pour les gros prompts

    //     $user = $security->getUser();
    //     if (!$user) {
    //         return new JsonResponse(['error' => 'Unauthorized'], 401);
    //     }

    //     $data = json_decode($request->getContent(), true);
    //     $courseId = $data['courseId'] ?? null;
    //     $question = trim($data['question'] ?? '');
    //     $mode = $data['mode'] ?? 'etude_de_cas';
    //     $userAnswer = $data['userAnswer'] ?? null;
    //     $previousQuestion = $data['previousQuestion'] ?? null;

    //     if (!$courseId || !$question) {
    //         return new JsonResponse(['error' => 'Missing courseId or question'], 400);
    //     }


    // }
}
