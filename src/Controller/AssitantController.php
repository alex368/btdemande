<?php

namespace App\Controller;

use App\Service\GoogleCalendarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\AI\RagRunner;

final class AssitantController extends AbstractController
{
     public function __construct(
        private readonly RagRunner $ragRunner,
    ) {}

    
    #[Route('/assitant', name: 'app_assitant')]
    public function index(): Response
    {
        return $this->render('assitant/index.html.twig', [
            'controller_name' => 'AssitantController',
        ]);
    }

    #[Route('/api/google-calendar/create', name: 'google_calendar_create', methods: ['POST'])]
public function create(Request $request, GoogleCalendarService $google): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    $title = $data['title'] ?? 'Nouvel événement';
    $start = $data['start'] ?? null;
    $end   = $data['end'] ?? null;

    if (!$start || !$end) {
        return $this->json(['error' => 'Missing start or end date'], 400);
    }

    $calendarId = $_ENV['GOOGLE_CALENDAR_ID'];

    $created = $google->createEvent($calendarId, $title, $start, $end);

    return $this->json($created);
}


 #[Route('/rag/query', name: 'app_rag_query')]
    public function queryRag(): Response
    {
        $documentPath = $this->getParameter('documents_path');
        $result = $this->ragRunner->run($documentPath, 'Quel est le contenu du brief stratégique ?');

        return new Response(nl2br($result));
    }



}
