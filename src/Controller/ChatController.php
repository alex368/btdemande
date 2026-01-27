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
use Dom\Entity;
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
    AiEntityKnowledgeService $knowledge
): JsonResponse {
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

    $customerDetails = $knowledge->getCustomerDetails($id, 20);
    if (!$customerDetails) {
        return $this->json([
            'error' => "Contact introuvable (id={$id})",
            'contactId' => $id,
            'directoryUrl' => $knowledge->customerDirectoryUrl(),
        ], 404);
    }

   
    $journal = $knowledge->buildCustomerJournal($customerDetails, 8);
 $contact = $em->getRepository(Contact::class)->find($id);
 $n = $contact->getFirstname();
 dump($n);
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

    try {
        $answer = $llmService->generate($prompt, [
            'max_tokens' => 650,
            'temperature' => 0.2,
        ]);
    } catch (\Throwable $e) {
        return $this->json([
            'error' => 'LLM error',
            'details' => $e->getMessage(),
            'contactId' => $id,
        ], 500);
    }

    return $this->json([
        'answer' => trim($answer),
        'contactId' => $id,
        // on peut garder customerDetails pour debug, mais front ne l’affiche plus
        'customerDetails' => $customerDetails,
    ]);
}

}
