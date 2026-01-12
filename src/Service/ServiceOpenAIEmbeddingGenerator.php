<?php

namespace App\Service;

use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use LLPhant\Embeddings\Document;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ServiceOpenAIEmbeddingGenerator implements EmbeddingGeneratorInterface
{
    private const EMBEDDING_LENGTH = 3072; // text-embedding-3-large

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiKey,
        private string $baseUrl = 'https://api.openai.com/v1',
        private string $model = 'text-embedding-3-large'
    ) {}

    /* =====================
       REQUIRED BY INTERFACE
       ===================== */

    public function embedText(string $text): array
    {
        return $this->callOpenAI($text);
    }

    public function embedDocument(Document $document): Document
    {
        $document->embedding = $this->callOpenAI($document->content);
        return $document;
    }

    public function getEmbeddingLength(): int
    {
        return self::EMBEDDING_LENGTH;
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

    private function callOpenAI(string $input): array
    {
        $response = $this->httpClient->request(
            'POST',
            $this->baseUrl . '/embeddings',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'input' => $input,
                ],
            ]
        );

        $data = $response->toArray();

        if (!isset($data['data'][0]['embedding'])) {
            throw new \RuntimeException('Réponse OpenAI invalide (embedding manquant)');
        }

        return $data['data'][0]['embedding'];
    }
}
