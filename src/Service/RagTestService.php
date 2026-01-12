<?php

namespace App\Service;

use App\Repository\DocumentRepository;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;
use LLPhant\Embeddings\VectorStores\Memory\MemoryVectorStore;
use LLPhant\Query\SemanticSearch\QuestionAnswering;
use Smalot\PdfParser\Parser;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RagTestService
{
   public function __construct(
    private LlmService $llm,
    private DocumentRepository $documentRepo,
    private HttpClientInterface $httpClient,
    private string $docsDirectory,
    private string $openAiApiKey
) {}


    public function testFromPdf(
        int $id,
        string $questionOrInstruction,
        string $mode = 'qa',
        ?string $absolutePdfPath = null
    ): string {
        // 1️⃣ Résolution du PDF
        if ($absolutePdfPath === null) {
            $docEntity = $this->documentRepo->find($id);
            if (!$docEntity) {
                throw new \RuntimeException("Document introuvable (ID=$id)");
            }
            $absolutePdfPath = "public/uploads/documents" . '/' . $docEntity->getFilePath();
        }

        // 2️⃣ Lecture du PDF
        $parser = new Parser();
        $pdf = $parser->parseFile($absolutePdfPath);
        
        $text = trim($pdf->getText());

        if ($text === '') {
            throw new \RuntimeException('PDF vide ou non lisible');
        }

        // 3️⃣ Document LLPhant
        $document = new Document();
        $document->content = $text;

        // 4️⃣ Chunking
        $chunks = DocumentSplitter::splitDocuments([$document], 500);

        // 5️⃣ Embedder OpenAI
        $embedder = new ServiceOpenAIEmbeddingGenerator(
            httpClient: $this->httpClient,
            apiKey: $this->openAiApiKey
        );

        // 6️⃣ Vector store (POC mémoire)
        $vectorStore = new MemoryVectorStore();
        $vectorStore->addDocuments(
            $embedder->embedDocuments($chunks)
        );

        // 7️⃣ Question Answering (arguments positionnels)
        $qa = new QuestionAnswering(
            $vectorStore,
            $embedder,
            $this->llm->getChat()
        );

        // 8️⃣ Modes
        return match ($mode) {
            'summary' => $this->llm->generate(
                "Résume clairement le document suivant :\n\n$text"
            ),

            'quiz' => $this->llm->generate(<<<PROMPT
À partir du contenu suivant, crée un QCM de 5 questions.
Pour chaque question :
- 4 réponses possibles
- 1 seule réponse correcte
- indique la bonne réponse

Contenu :
$text
PROMPT
            ),

            'flashcards' => $this->llm->generate(
                "Crée des flashcards questions/réponses à partir de :\n\n$text"
            ),

            default => $qa->answerQuestion($questionOrInstruction),
        };
    }


    public function answerFromText(
    int $documentId,
    string $text,
    string $questionOrInstruction,
    string $mode = 'qa'
): string {
    $text = mb_substr($text, 0, 200_000);

    $document = new Document();
    $document->content = $text;

    $chunks = \LLPhant\Embeddings\DocumentSplitter\DocumentSplitter::splitDocuments(
        [$document],
        500
    );

    $embedder = new ServiceOpenAIEmbeddingGenerator(
        httpClient: $this->httpClient,
        apiKey: $this->openAiApiKey
    );

    $vectorStore = new \LLPhant\Embeddings\VectorStores\Memory\MemoryVectorStore();
    $vectorStore->addDocuments(
        $embedder->embedDocuments($chunks)
    );

    $qa = new \LLPhant\Query\SemanticSearch\QuestionAnswering(
        $vectorStore,
        $embedder,
        $this->llm->getChat()
    );

    return match ($mode) {
        'summary' => $this->llm->generate("Résume le document suivant :\n\n$text"),
        'quiz' => $this->llm->generate("Crée un QCM à partir de :\n\n$text"),
        'flashcards' => $this->llm->generate("Crée des flashcards à partir de :\n\n$text"),
        default => $qa->answerQuestion($questionOrInstruction),
    };
}

}
