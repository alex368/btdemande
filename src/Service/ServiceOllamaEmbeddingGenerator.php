<?php

namespace App\Service;

use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use LLPhant\Embeddings\Document;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ServiceOllamaEmbeddingGenerator implements EmbeddingGeneratorInterface
{
    /**
     * Ollama embedding vector size depends on the model.
     * Example: nomic-embed-text -> 768
     * If you use another model, set $embeddingLength accordingly.
     */
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $baseUrl = 'http://127.0.0.1:11434',
        private string $model = 'nomic-embed-text',
        private int $embeddingLength = 768
    ) {}

    /* =====================
       REQUIRED BY INTERFACE
       ===================== */

    public function embedText(string $text): array
    {
        return $this->callOllamaEmbedding($text);
    }

    public function embedDocument(Document $document): Document
    {
        $document->embedding = $this->callOllamaEmbedding($document->content);
        return $document;
    }

    public function getEmbeddingLength(): int
    {
        return $this->embeddingLength;
    }

    /* =====================
       OPTIONAL CONVENIENCE
       ===================== */

    public function embedDocuments(array $documents): array
    {
        foreach ($documents as $document) {
            $this->embedDocument($document);
        }

        return $documents;
    }

    public function embedQuery(string $query): array
    {
        return $this->embedText($query);
    }

    /* =====================
       INTERNAL
       ===================== */

    private function callOllamaEmbedding(string $input): array
    {
        $input = trim($input);
        if ($input === '') {
            // Avoid calling Ollama with empty prompt
            return array_fill(0, $this->embeddingLength, 0.0);
        }

        // Ollama classic embeddings endpoint:
        // POST /api/embeddings  { "model": "...", "prompt": "..." }
        $response = $this->httpClient->request('POST', rtrim($this->baseUrl, '/') . '/api/embeddings', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model'  => $this->model,
                'prompt' => $input,
            ],
            // optional timeouts
            'timeout' => 60,
        ]);

        $data = $response->toArray(false);

        if (!isset($data['embedding']) || !is_array($data['embedding'])) {
            throw new \RuntimeException('Réponse Ollama invalide (embedding manquant).');
        }

        // If embedding length wasn’t configured correctly, auto-sync it once.
        if ($this->embeddingLength <= 0) {
            $this->embeddingLength = count($data['embedding']);
        }

        return $data['embedding'];
    }
}
