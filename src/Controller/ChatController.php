<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\CourseContent;
use App\Entity\CourseRagChunk;
use App\Entity\ClassroomMembership;
use App\Entity\Contact;
use App\Entity\User;
use App\Model\LlmUserContext;
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

final class ChatController extends AbstractController
{
    #[Route('/collaborator/{id}/chat', name: 'app_chat_general', methods: ['GET'])]
    public function index(
        int $id,
        EntityManagerInterface $em
    ): Response {

  
    
    $admin = $em->getRepository(User::class)->findOneByIdAndRole($id, 'ROLE_ADMIN');

    $collaborator = $em->getRepository(User::class)->findOneByIdAndRole($id, 'ROLE_COLLABORATOR');


    if($user = $admin || $user = $collaborator){
       
    }
    else{ 
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

    $customer = $em->getRepository(Contact::class)->find($id);


    // dd($customer);


        // $course = $em->getRepository(Course::class)->find($id);

        return $this->render('chatbot/chat.html.twig', [
            // 'classroom'   => $classroom,
            // 'course'      => $course,
            // 'sidebarData' => $sidebarService->getSidebarData($classroom),
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

