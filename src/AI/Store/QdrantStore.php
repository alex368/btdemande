<?php

namespace App\AI\Store;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\AI\Store\Document\VectorDocument;
use Symfony\AI\Store\StoreInterface;
use Symfony\AI\Platform\Vector\Vector;
use Traversable;

class QdrantStore implements StoreInterface
{
    private string $collection;
    private HttpClientInterface $http;

    public function __construct(HttpClientInterface $http, string $collection = 'rag_documents')
    {
        $this->http = $http;
        $this->collection = $collection;
    }

    public function save(VectorDocument $document): void
    {
        $this->add($document); // délègue à add()
    }

    public function add(VectorDocument ...$documents): void
    {
        $points = [];

        foreach ($documents as $doc) {
            $points[] = [
                'id' => (string) $doc->getId(),
                'vector' => $doc->getVector(),
                'payload' => [
                    'content' => $doc->getContent(),
                    'metadata' => $doc->getMetadata(),
                ],
            ];
        }

        $this->http->request('PUT', "http://localhost:6333/collections/{$this->collection}/points", [
            'json' => ['points' => $points],
        ]);
    }

    public function query(Vector $vector, array $options = []): array|Traversable
    {
        $limit = $options['limit'] ?? 5;

        $response = $this->http->request('POST', "http://localhost:6333/collections/{$this->collection}/points/search", [
            'json' => [
                'vector' => $vector->toArray(),
                'limit' => $limit,
                'with_payload' => true,
            ],
        ]);

        $results = $response->toArray()['result'];

        return array_map(fn ($hit) => new VectorDocument(
            id: (string) $hit['id'],
            vector: [], // facultatif ici
            content: $hit['payload']['content'] ?? '',
            metadata: $hit['payload']['metadata'] ?? []
        ), $results);
    }
}
