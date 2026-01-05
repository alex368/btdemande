<?php

namespace App\AI;

use App\AI\Store\QdrantStore; // Ton store perso
use Symfony\AI\Store\Document\Loader\InMemoryLoader;
use Symfony\AI\Store\Document\Vectorizer;
use Symfony\AI\Store\Document\Transformer\TextSplitTransformer;
use Symfony\AI\Store\Indexer;

use Symfony\AI\Platform\Bridge\OpenAi\PlatformFactory as OpenAiPlatformFactory;
use Symfony\AI\Agent\Agent;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Store\Document\VectorDocument;

class RagRunner
{
    public function __construct(
        private readonly QdrantStore $store,        // Injecté automatiquement
        private readonly string $openAiKey,         // Injecté depuis paramètres
    ) {}

    public function run(string $documentDir, string $question): string
    {
        $debugLogPath = __DIR__ . '/../../var/log/rag-debug.txt';
        file_put_contents($debugLogPath, "=== RAG Debug Log ===\n");

        // 1. Charger les documents
        $loader = new DocumentLoader();
        $documents = $loader->load($documentDir);

        if (empty($documents)) {
            file_put_contents($debugLogPath, "Aucun document chargé.\n", FILE_APPEND);
            return "Aucun document valide trouvé dans « {$documentDir} ».";
        }

        file_put_contents($debugLogPath, count($documents) . " documents chargés.\n", FILE_APPEND);

        // 2. Split en chunks
        $transformer = new TextSplitTransformer(chunkSize: 1000, overlap: 200);
        $documents = iterator_to_array($transformer->transform($documents));
        file_put_contents($debugLogPath, count($documents) . " chunks générés après transformation.\n", FILE_APPEND);

        // 3. Initialiser OpenAI + vectorizer
        $platform = OpenAiPlatformFactory::create($this->openAiKey);
        $vectorizer = new Vectorizer($platform, 'text-embedding-3-small');

        // 4. Indexation dans le store vectoriel
        $indexer = new Indexer(new InMemoryLoader($documents), $vectorizer, $this->store);
        $indexer->index($documents);
        file_put_contents($debugLogPath, "Indexation terminée.\n", FILE_APPEND);

        // 5. Recherche vectorielle
        $queryVector = $vectorizer->vectorize($question);
        $results = $this->store->query($queryVector, ['limit' => 5]);

        if (empty($results)) {
            file_put_contents($debugLogPath, "Aucun résultat trouvé pour la requête.\n", FILE_APPEND);
            return "Aucun document trouvé sur ce sujet : « {$question} »";
        }

        // 6. Construction du contexte (corrigé avec VectorDocument)
        $context = '';
foreach ($results as $vectorDoc) {
    $doc = $vectorDoc->getDocument(); // Le vrai TextDocument

    $id = $doc->getId(); // ✅ Correct ici
    $filename = $doc->getMetadata()['filename'] ?? 'inconnu';
    $content = trim($doc->getContent());

    if ($content !== '') {
        $context .= "=== [{$id}] Extrait de : {$filename} ===\n";
        $context .= $content . "\n\n";
    }
}


        file_put_contents($debugLogPath, "\n=== CONTEXTE ===\n" . $context, FILE_APPEND);

        if (trim($context) === '') {
            file_put_contents($debugLogPath, "\n⚠️ Aucun contenu exploitable dans les documents trouvés.\n", FILE_APPEND);
            return "Aucun contenu pertinent trouvé à partir des documents indexés.";
        }

        // 7. Appel de l’agent OpenAI
        $agent = new Agent($platform, 'gpt-4o-mini');

        $messages = new MessageBag(
            Message::forSystem("Tu dois faire un résumé clair et structuré uniquement à partir des extraits suivants. Ne complète rien si ce n’est pas explicitement dans le texte."),
            Message::ofUser($context)
        );

        $response = $agent->call($messages);

        return $response->getContent();
    }
}
