<?php

namespace App\Service;

use App\Entity\Document;
use App\Entity\DocumentRagChunk;
use Doctrine\ORM\EntityManagerInterface;

use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class DocumentRagStrictQaService
{
    private const REFUSAL = "Je ne sais pas à partir des documents fournis.";

    // --- Réglages strict doc-only (ajustables) ---
    private const LEX_BOOST = 0.22;                 // petit boost lexical (limite faux positifs)
    private const LEX_SELECT_THRESHOLD = 0.12;      // sélection chunks: lex >=
    private const NEIGHBOR_RADIUS = 1;              // voisins ±1
    private const MIN_FINAL_SCORE = 0.05;           // filtre sur score final (cos + boost)
    private const COS_MIN_BEST = 0.35;              // anti hors-sujet (trop haut => faux refus)
    private const DF_RATIO_MAX_FOR_2ND = 0.20;      // co-occurrence: ignore tokens présents dans >20% des chunks
    private const GROUNDING_MIN_OVERLAP = 0.10;     // overlap réponse vs contexte (anti connaissances générales)
    private const MAX_CITATIONS = 5;                // limite citations demandées au modèle
    private const POOL_MULTIPLIER = 3;              // pool = max(30, topK*POOL_MULTIPLIER)

    // IMPORTANT: test hors-sujet (ex "tacos") => doit REFUSER
    // => exige AU MOINS 1 ancre "non-générique" ET "rare" (faible DF) présente dans le corpus.
    private const REQUIRE_RARE_ANCHOR = true;
    private const RARE_ANCHOR_MAX_DF_RATIO = 0.12;  // <=12% des chunks (augmente si doc petit)

    // Citations: si le modèle oublie, on fallback extractif (mais seulement si anchors OK)
    private const REQUIRE_MODEL_CITATIONS = false;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EmbeddingGeneratorInterface $embedder,
        private readonly HttpClientInterface $httpClient,
        private readonly string $ollamaBaseUrl = 'http://host.docker.internal:11434',
        private readonly string $ollamaChatModel = 'ministral-3:3b',
    ) {}

    public function ask(int $documentId, string $question, int $topK = 8, float $minScore = self::MIN_FINAL_SCORE): array
    {
        $q0 = trim($question);
        if ($q0 === '') {
            throw new \InvalidArgumentException('Question vide.');
        }

        // 1) Charge chunks
        $documentRef = $this->em->getReference(Document::class, $documentId);

        /** @var DocumentRagChunk[] $chunks */
        $chunks = $this->em->getRepository(DocumentRagChunk::class)->findBy(
            ['document' => $documentRef],
            ['chunkIndex' => 'ASC']
        );

        if (!$chunks) {
            return [
                'answer' => self::REFUSAL,
                'sources' => [],
                'used_chunk_ids' => [],
                'used_pages' => [],
            ];
        }

        // 2) DF + ancres question-only
        $df = $this->computeDocFreq($chunks);
        $nChunks = max(1, count($chunks));

        $anchorsAll = $this->questionAnchors($q0);

        // Ancres "contenu": présentes dans le corpus et pas trop fréquentes
        $anchorsContent = $this->filterContentAnchors($anchorsAll, $df, $nChunks, self::RARE_ANCHOR_MAX_DF_RATIO);

        // Hors-sujet global:
        // - si aucune ancre "contenu" => ex: "tacos" => refus immédiat
        if (self::REQUIRE_RARE_ANCHOR && !$anchorsContent) {
            return [
                'answer' => self::REFUSAL,
                'sources' => [],
                'used_chunk_ids' => [],
                'used_pages' => [],
            ];
        }

        // 3) Keywords 2nd degré (co-occurrence filtrée DF)
        $keywords2nd = $this->buildSecondDegreeKeywordsFromCorpusFiltered(
            $chunks,
            $q0,
            $df,
            self::DF_RATIO_MAX_FOR_2ND,
            8
        );

        // 4) Réécriture doc-only (seulement pour questions de type "définis")
        $q = $this->rewriteQuestionForDocOnly($q0, $keywords2nd);

        // 5) Embedding question
        $qEmb = $this->embedder->embedText($q);

        // 6) Score hybride (cosine + lexical boost)
        $scored = [];
        foreach ($chunks as $c) {
            $emb = $c->getEmbedding();
            if (!is_array($emb) || $emb === []) continue;

            $cos = $this->cosine($qEmb, $emb);
            if ($cos < -0.5) continue;

            $lex = $this->lexicalScore((string)$c->getContent(), $keywords2nd);
            $score = $cos + (self::LEX_BOOST * $lex);

            $scored[] = ['chunk' => $c, 'score' => $score, 'cos' => $cos, 'lex' => $lex];
        }

        if (!$scored) {
            return [
                'answer' => self::REFUSAL,
                'sources' => [],
                'used_chunk_ids' => [],
                'used_pages' => [],
            ];
        }

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        // 7) Pool large
        $pool = array_slice($scored, 0, max(30, $topK * self::POOL_MULTIPLIER));

        $debugTop = array_map(
            fn($x) => ['chunk' => $x['chunk'], 'score' => (float)($x['score'] ?? 0.0)],
            array_slice($pool, 0, 10)
        );

        $bestCos = (float)($pool[0]['cos'] ?? -1.0);
        if ($bestCos < self::COS_MIN_BEST) {
            return [
                'answer' => self::REFUSAL,
                'sources' => $this->sourcesPayload($debugTop),
                'used_chunk_ids' => [],
                'used_pages' => [],
            ];
        }

        // 8) Sélection: priorité lexical, sinon fallback pool
        $selected = array_values(array_filter($pool, fn($x) => ($x['lex'] ?? 0.0) >= self::LEX_SELECT_THRESHOLD));
        if (!$selected) {
            $selected = array_slice($pool, 0, max(1, $topK));
        }

        $selected = array_slice($selected, 0, max(1, $topK));
        $selected = array_values(array_filter($selected, fn($x) => (float)$x['score'] >= $minScore));

        if (!$selected) {
            return [
                'answer' => self::REFUSAL,
                'sources' => $this->sourcesPayload($debugTop),
                'used_chunk_ids' => [],
                'used_pages' => [],
            ];
        }

        // 9) Ajoute voisins
        $selectedChunks = array_map(fn($x) => $x['chunk'], $selected);
        $selectedChunks = $this->expandWithNeighbors($selectedChunks, $chunks, self::NEIGHBOR_RADIUS);

        // Garde-fou: au moins 1 ancre "contenu" doit apparaître dans le contexte sélectionné
        if ($this->maxAnchorHitsInChunks($selectedChunks, $anchorsContent) < 1) {
            return [
                'answer' => self::REFUSAL,
                'sources' => $this->sourcesPayload($debugTop),
                'used_chunk_ids' => [],
                'used_pages' => [],
            ];
        }

        // 10) Rebuild top (sources/validation)
        $scoreById = [];
        foreach ($scored as $row) {
            $scoreById[$row['chunk']->getId()] = (float)$row['score'];
        }

        $top = [];
        foreach ($selectedChunks as $c) {
            $top[] = ['chunk' => $c, 'score' => $scoreById[$c->getId()] ?? 0.0];
        }

        // Map chunks autorisés
        $chunkById = [];
        $allowedIds = [];
        foreach ($top as $item) {
            /** @var DocumentRagChunk $c */
            $c = $item['chunk'];
            $chunkById[$c->getId()] = $c;
            $allowedIds[] = $c->getId();
        }

        // 11) Contexte ordonné
        usort($selectedChunks, fn($a, $b) => $a->getChunkIndex() <=> $b->getChunkIndex());

        $context = '';
        foreach ($selectedChunks as $c) {
            $page = $c->getPageNumber();
            $pageTxt = $page ? "page={$page}" : "page=?";
            $context .= "=== CHUNK [chunk:{$c->getId()}] (index={$c->getChunkIndex()}, {$pageTxt}) ===\n";
            $context .= (string)$c->getContent() . "\n\n";
        }

        // 12) Prompt strict document-only
        $prompt = <<<PROMPT
INSTRUCTIONS STRICTES (DOCUMENT-ONLY)
- Tu réponds UNIQUEMENT à partir du CONTEXTE ci-dessous.
- Interdiction d'ajouter des informations non présentes dans le CONTEXTE (pas de connaissances générales).
- Si la QUESTION est hors sujet par rapport au CONTEXTE, ou si le CONTEXTE ne permet pas de répondre avec des faits concrets, réponds exactement :
Je ne sais pas à partir des documents fournis.

FORMAT OBLIGATOIRE (2 blocs)
RÉPONSE:
<réponse factuelle et contextualisée, sans citations entre crochets>

CITATIONS:
- 1 à {self::MAX_CITATIONS} éléments max.
- Chaque élément doit venir du CONTEXTE.
- Format: [chunk:ID p:PAGE] (ou [chunk:ID] si page inconnue)

CONTEXTE:
{$context}

QUESTION:
{$q}
PROMPT;

        $raw = $this->ollamaGenerate($prompt);
        [$naturalAnswer, $citBlock] = $this->splitAnswerAndCitations($raw);

        // 13) Citations + fallback extractif (mais seulement si anchors ok, ce qui est déjà le cas)
        $citations = $this->extractChunkCitations($citBlock);
        $usedIds = array_keys($citations);
        $isModelRefusal = $this->isRefusal($naturalAnswer);

        if ($isModelRefusal || (!self::REQUIRE_MODEL_CITATIONS && !$usedIds)) {
            // Fallback extractif: 100% doc-only
            [$fallbackAnswer, $fallbackUsedIds] = $this->extractiveAnswerFallback(
                $selectedChunks,
                $anchorsContent,
                $keywords2nd,
                6
            );

            if ($fallbackAnswer === '' || !$fallbackUsedIds) {
                return [
                    'answer' => self::REFUSAL,
                    'sources' => $this->sourcesPayload($top),
                    'used_chunk_ids' => [],
                    'used_pages' => [],
                ];
            }

            $naturalAnswer = $fallbackAnswer;

            $citations = [];
            foreach ($fallbackUsedIds as $id) {
                $citations[(int)$id] = null;
            }
            $usedIds = array_keys($citations);
        }

        // Filtre citations -> seulement IDs autorisés
        $usedIds = array_values(array_intersect($usedIds, $allowedIds));
        if (!$usedIds) {
            return [
                'answer' => self::REFUSAL,
                'sources' => $this->sourcesPayload($top),
                'used_chunk_ids' => [],
                'used_pages' => [],
            ];
        }

        // 14) Guardrail anti “réponse générique” : overlap réponse vs contexte
        $final = trim((string)$naturalAnswer);
        if ($this->isRefusal($final)) {
            return [
                'answer' => self::REFUSAL,
                'sources' => $this->sourcesPayload($top),
                'used_chunk_ids' => [],
                'used_pages' => [],
            ];
        }

        $overlap = $this->groundingOverlapRatio($final, $context);
        if ($overlap < self::GROUNDING_MIN_OVERLAP) {
            return [
                'answer' => self::REFUSAL,
                'sources' => $this->sourcesPayload($top),
                'used_chunk_ids' => [],
                'used_pages' => [],
            ];
        }

        // Pages: reconstruit depuis DB si p manquante/erronée
        $citations = array_intersect_key($citations, array_flip($usedIds));
        $usedPages = $this->pagesFromCitations($citations, $chunkById);

        if ($usedPages) {
            $final .= "\n\nSources : d’après les pages " . implode(', ', array_map(fn($p) => "p.$p", $usedPages)) . ".";
        } else {
            $final .= "\n\nSources : d’après les chunks " . implode(', ', array_map(fn($id) => "chunk:$id", $usedIds)) . ".";
        }

        return [
            'answer' => $final,
            'sources' => $this->sourcesPayload($top),
            'used_chunk_ids' => $usedIds,
            'used_pages' => $usedPages,
        ];
    }

    // ---------------------------
    // Ollama
    // ---------------------------
    private function ollamaGenerate(string $prompt): string
    {
        $res = $this->httpClient->request('POST', rtrim($this->ollamaBaseUrl, '/') . '/api/generate', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'model' => $this->ollamaChatModel,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'temperature' => 0.0,
                    'num_predict' => 350,
                ],
            ],
            'timeout' => 600,
            'max_duration' => 600,
        ]);

        $data = $res->toArray(false);
        return trim((string)($data['response'] ?? ''));
    }

    // ---------------------------
    // Parsing / citations
    // ---------------------------
    private function extractChunkCitations(string $text): array
    {
        preg_match_all('~\[\s*chunk\s*:\s*(\d+)(?:\s+p\s*:\s*(\d+))?\s*\]~iu', (string)$text, $m, PREG_SET_ORDER);

        $out = [];
        foreach ($m as $row) {
            $id = (int)($row[1] ?? 0);
            if ($id <= 0) continue;

            $p = (isset($row[2]) && $row[2] !== '') ? (int)$row[2] : null;
            $out[$id] = $p;
        }
        return $out;
    }

    private function splitAnswerAndCitations(string $raw): array
    {
        $raw = trim((string)$raw);
        if ($raw === '') return ['', ''];
        if ($this->isRefusal($raw)) return [self::REFUSAL, ''];

        $answer = $raw;
        $cits = '';

        $pattern = '~(?:^|\R)\s*R[ÉE]PONSE\s*:\s*(.*?)\s*(?:\R\s*(?:CITATIONS?|SOURCES?)\s*:\s*)(.*)\s*$~usi';
        if (preg_match($pattern, $raw, $m)) {
            $answer = trim($m[1] ?? '');
            $cits   = trim($m[2] ?? '');
        }

        return [$answer, $cits];
    }

    private function isRefusal(string $text): bool
    {
        $t = trim((string)$text);
        if ($t === '') return true;
        return (bool)preg_match('~^\s*Je ne sais pas à partir des documents fournis\.\s*$~u', $t);
    }

    // ---------------------------
    // Sources payload
    // ---------------------------
    private function sourcesPayload(array $top): array
    {
        return array_map(static function ($item) {
            /** @var BookRagChunk $c */
            $c = $item['chunk'];

            return [
                'id' => (int)$c->getId(),
                'chunkIndex' => (int)$c->getChunkIndex(),
                'pageNumber' => method_exists($c, 'getPageNumber') ? $c->getPageNumber() : null,
                'score' => (float)($item['score'] ?? 0.0),
                'preview' => mb_substr((string)$c->getContent(), 0, 280),
            ];
        }, $top);
    }

    // ---------------------------
    // Cosine
    // ---------------------------
    private function cosine(array $a, array $b): float
    {
        $na = count($a);
        $nb = count($b);
        if ($na === 0 || $na !== $nb) return -1.0;

        $dot = 0.0; $aa = 0.0; $bb = 0.0;
        for ($i = 0; $i < $na; $i++) {
            $x = (float)$a[$i];
            $y = (float)$b[$i];
            $dot += $x * $y;
            $aa += $x * $x;
            $bb += $y * $y;
        }
        if ($aa <= 0.0 || $bb <= 0.0) return -1.0;
        return $dot / (sqrt($aa) * sqrt($bb));
    }

    // ---------------------------
    // Pages from citations (souple)
    // ---------------------------
    private function pagesFromCitations(array $citations, array $chunkById): array
    {
        $pages = [];

        foreach ($citations as $id => $p) {
            $id = (int)$id;
            if ($id <= 0) continue;
            if (!isset($chunkById[$id])) continue;

            $real = $chunkById[$id]->getPageNumber();

            if ($p !== null) {
                $p = (int)$p;
                if ($real !== null && $p !== (int)$real) $p = (int)$real;
                if ($p > 0) $pages[] = $p;
                continue;
            }

            if ($real !== null && (int)$real > 0) $pages[] = (int)$real;
        }

        $pages = array_values(array_unique($pages));
        sort($pages);
        return $pages;
    }

    // ---------------------------
    // Doc-only question rewrite
    // ---------------------------
    private function rewriteQuestionForDocOnly(string $q, array $keywords): string
    {
        $q = trim($q);
        if ($q === '') return $q;

        $qq = mb_strtolower($q);

        $isDefinition = (bool)preg_match(
            '~^\s*(qu[\' ]?est[- ]ce que|c[\' ]?est quoi|définis|définition de|explique)\b~iu',
            $qq
        );

        if (!$isDefinition) return $q;

        $hint = '';
        if (!empty($keywords)) {
            $hint = implode(', ', array_slice(array_values($keywords), 0, 10));
        }

        $out =
            "D’après le document, que dit-il sur : {$q} ? " .
            "Réponds uniquement avec des éléments présents dans le document (faits, dispositifs, démarches, procédures, dates/numéros s’ils existent). " .
            "Si le document ne contient rien de concret lié, réponds exactement : " . self::REFUSAL . " ";

        if ($hint !== '') $out .= "Mots-clés (si présents dans le document): {$hint}.";

        return $out;
    }

    // ---------------------------
    // Guardrail (grounding)
    // ---------------------------
    private function tokenSetForOverlap(string $text): array
    {
        $text = mb_strtolower($text);
        $tokens = preg_split('~[^\p{L}\p{N}]+~u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $out = [];
        foreach ($tokens as $t) {
            if (mb_strlen($t) < 4) continue;
            $out[$t] = true;
        }
        return $out;
    }

    private function groundingOverlapRatio(string $answer, string $context): float
    {
        $a = $this->tokenSetForOverlap($answer);
        $c = $this->tokenSetForOverlap($context);
        if (!$a || !$c) return 0.0;

        $inter = 0;
        foreach ($a as $tok => $_) {
            if (isset($c[$tok])) $inter++;
        }
        return $inter / max(1, count($a));
    }

    // ---------------------------
    // Tokens / keywords
    // ---------------------------
    private function keywordsBaseTokens(string $text): array
    {
        $text = mb_strtolower($text);
        $text = str_replace(["’", "‘"], "'", $text);

        $tokens = preg_split('~[^\p{L}\p{N}]+~u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        static $stop = [
            'que','quoi','est','ce','cest','dans','les','des','une','un','du','de','la','le','et','ou',
            'a','à','au','aux','pour','par','sur','avec','sans','plus','moins','ne','pas','qui','dont',
            'se','sa','son','ses','leur','leurs','d','l','en','y','il','elle','ils','elles','cela','ça',
            'cet','cette','ces','mes','tes','nos','vos','leurs','comme','ainsi','donc'
        ];

        $out = [];
        foreach ($tokens as $t) {
            $t = trim($t);
            if ($t === '') continue;
            if (in_array($t, $stop, true)) continue;
            if (mb_strlen($t) < 3) continue;

            $t = $this->lightStemFr($t);
            $out[] = $t;
        }

        return array_values(array_unique($out));
    }

    private function lightStemFr(string $t): string
    {
        foreach (['ments','ment','ations','ation','ités','ité','euses','euse','eaux','aux','eurs','eur','es','s'] as $suf) {
            if (mb_strlen($t) > 6 && str_ends_with($t, $suf)) {
                return mb_substr($t, 0, -mb_strlen($suf));
            }
        }
        return $t;
    }

    private function extractBigrams(string $text): array
    {
        $toks = $this->keywordsBaseTokens($text);
        $bigrams = [];
        for ($i = 0; $i < count($toks) - 1; $i++) {
            $bigrams[] = $toks[$i] . ' ' . $toks[$i + 1];
        }
        return $bigrams;
    }

    private function lexicalScore(string $content, array $keywords): float
    {
        $txt = mb_strtolower($content);
        if ($txt === '' || empty($keywords)) return 0.0;

        $hits = 0;
        foreach ($keywords as $kw) {
            $kw = trim(mb_strtolower((string)$kw));
            if ($kw === '') continue;

            if (str_contains($kw, ' ')) {
                if (str_contains($txt, $kw)) $hits++;
            } else {
                if (preg_match('~\b' . preg_quote($kw, '~') . '\b~u', $txt)) $hits++;
            }
        }

        if ($hits === 0) return 0.0;
        $den = max(10, count($keywords));
        return min(1.0, $hits / $den);
    }

    private function computeDocFreq(array $chunks): array
    {
        $df = [];
        foreach ($chunks as $c) {
            $toks = $this->keywordsBaseTokens((string)$c->getContent());
            if (!$toks) continue;

            $seen = [];
            foreach ($toks as $t) $seen[$t] = true;
            foreach ($seen as $t => $_) $df[$t] = ($df[$t] ?? 0) + 1;
        }
        return $df;
    }

    private function buildSecondDegreeKeywordsFromCorpusFiltered(
        array $chunks,
        string $question,
        array $df,
        float $dfRatioMax,
        int $maxAssociatesPerToken
    ): array {
        $qTokens = $this->keywordsBaseTokens($question);
        if (!$qTokens) return [];

        $n = max(1, count($chunks));

        $chunkTokens = [];
        foreach ($chunks as $c) {
            $toks = $this->keywordsBaseTokens((string)$c->getContent());
            if ($toks) $chunkTokens[] = $toks;
        }

        $assoc = [];
        foreach ($qTokens as $qt) $assoc[$qt] = [];

        foreach ($chunkTokens as $toks) {
            $set = array_flip($toks);

            foreach ($qTokens as $qt) {
                if (!isset($set[$qt])) continue;

                foreach ($toks as $other) {
                    if ($other === $qt) continue;

                    $ratio = (($df[$other] ?? 0) / $n);
                    if ($ratio > $dfRatioMax) continue;

                    $assoc[$qt][$other] = ($assoc[$qt][$other] ?? 0) + 1;
                }
            }
        }

        $expanded = $qTokens;

        foreach ($assoc as $qt => $counts) {
            if (!$counts) continue;
            arsort($counts);

            $picked = 0;
            foreach ($counts as $term => $cnt) {
                if ($cnt < 2) continue;
                if (mb_strlen($term) < 4) continue;
                $expanded[] = $term;
                if (++$picked >= $maxAssociatesPerToken) break;
            }
        }

        $expanded = array_merge($expanded, $this->extractBigrams($question));
        $expanded = array_values(array_unique(array_map(fn($s) => trim(mb_strtolower((string)$s)), $expanded)));
        return array_values(array_filter($expanded, fn($s) => $s !== ''));
    }

    // ---------------------------
    // Neighbors
    // ---------------------------
    private function expandWithNeighbors(array $selected, array $allChunks, int $radius): array
    {
        if ($radius <= 0) return $selected;

        $byIndex = [];
        foreach ($allChunks as $c) {
            $byIndex[(int)$c->getChunkIndex()] = $c;
        }

        $out = [];
        foreach ($selected as $c) {
            $idx = (int)$c->getChunkIndex();
            for ($d = -$radius; $d <= $radius; $d++) {
                $i = $idx + $d;
                if (isset($byIndex[$i])) {
                    $out[$byIndex[$i]->getId()] = $byIndex[$i];
                }
            }
        }
        return array_values($out);
    }

    // ---------------------------
    // Anchors
    // ---------------------------
    private function questionAnchors(string $question): array
    {
        // Tokens stemmés
        $tokens = $this->keywordsBaseTokens($question);

        // Tokens bruts (sans stem) => permet de garder "inpi", "tacos", etc.
        $raw = preg_split('~[^\p{L}\p{N}]+~u', mb_strtolower($question), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($raw as $t) {
            $t = trim($t);
            if (mb_strlen($t) >= 3) $tokens[] = $t;
        }

        $tokens = array_values(array_unique(array_filter($tokens)));

        // Bannis génériques (sinon "comment", "faire" matchent partout => faux positifs)
        $ban = [
            'comment','faire','fais','fait','faire','peux','peut','peut-on',
            'recette','simple','classique','préparer','preparer',
            'définition','defin','définis','expliquer','explique',
            'quest','qu','estce','quoi'
        ];

        $tokens = array_values(array_filter($tokens, function ($t) use ($ban) {
            $t = trim(mb_strtolower((string)$t));
            if ($t === '') return false;
            if (in_array($t, $ban, true)) return false;
            if (mb_strlen($t) < 4) return false; // clé: évite "in", "pi", etc.
            return true;
        }));

        return $tokens;
    }

    private function filterContentAnchors(array $anchors, array $df, int $nChunks, float $maxDfRatio): array
    {
        if (!$anchors) return [];

        $out = [];
        foreach ($anchors as $a) {
            $a = trim(mb_strtolower((string)$a));
            if ($a === '') continue;

            $freq = (int)($df[$a] ?? 0);
            if ($freq <= 0) continue;

            $ratio = $freq / max(1, $nChunks);
            if ($ratio <= $maxDfRatio) {
                $out[] = $a;
            }
        }

        // Si aucune "rare", on renvoie vide => hors-sujet (strict)
        return array_values(array_unique($out));
    }

    private function anchorHitsInText(string $text, array $anchors): int
    {
        $txt = mb_strtolower($text);
        $hits = 0;

        foreach ($anchors as $a) {
            $a = trim(mb_strtolower((string)$a));
            if ($a === '') continue;

            if (preg_match('~\b' . preg_quote($a, '~') . '\b~u', $txt)) {
                $hits++;
            }
        }
        return $hits;
    }

    private function maxAnchorHitsInChunks(array $selectedChunks, array $anchors): int
    {
        $max = 0;
        foreach ($selectedChunks as $c) {
            $h = $this->anchorHitsInText((string)$c->getContent(), $anchors);
            if ($h > $max) $max = $h;
        }
        return $max;
    }

    // ---------------------------
    // Fallback extractif 100% doc-only
    // ---------------------------
    private function extractiveAnswerFallback(array $chunks, array $anchors, array $keywords, int $maxSentences = 6): array
    {
        $ranked = [];
        foreach ($chunks as $c) {
            $txt = (string)$c->getContent();
            $aHits = $this->anchorCoverageHits($txt, $anchors);
            $kHits = $this->keywordHits($txt, $keywords);
            $rank = ($aHits * 3) + $kHits;

            if ($rank > 0) {
                $ranked[] = ['chunk' => $c, 'rank' => $rank];
            }
        }

        if (!$ranked) return ['', []];

        usort($ranked, fn($x, $y) => $y['rank'] <=> $x['rank']);
        $ranked = array_slice($ranked, 0, 3);

        $sentences = [];
        $usedIds = [];

        foreach ($ranked as $row) {
            /** @var BookRagChunk $c */
            $c = $row['chunk'];
            $usedIds[] = (int)$c->getId();

            foreach ($this->extractSentences((string)$c->getContent()) as $s) {
                $sClean = trim($s);
                if ($sClean === '') continue;

                $a = $this->anchorCoverageHits($sClean, $anchors);
                $k = $this->keywordHits($sClean, $keywords);

                if ($a >= 1 || $k >= 2) {
                    $sentences[] = $this->trimSentence($sClean);
                    if (count($sentences) >= $maxSentences) break 2;
                }
            }
        }

        $sentences = array_values(array_unique($sentences));
        if (!$sentences) return ['', []];

        $answer = "D’après le document :\n- " . implode("\n- ", $sentences);

        return [$answer, array_values(array_unique($usedIds))];
    }

    private function extractSentences(string $text): array
    {
        $t = preg_replace("~\s+~u", " ", $text);
        if ($t === null) $t = $text;

        $parts = preg_split('~(?<=[\.\?\!])\s+~u', $t) ?: [];
        return array_values(array_filter($parts, fn($x) => trim((string)$x) !== ''));
    }

    private function trimSentence(string $s, int $maxLen = 260): string
    {
        $s = trim($s);
        if (mb_strlen($s) <= $maxLen) return $s;
        return mb_substr($s, 0, $maxLen - 1) . '…';
    }

    private function keywordHits(string $content, array $keywords): int
    {
        $txt = mb_strtolower($content);
        $hits = 0;

        foreach ($keywords as $kw) {
            $kw = trim(mb_strtolower((string)$kw));
            if ($kw === '') continue;

            if (str_contains($kw, ' ')) {
                if (str_contains($txt, $kw)) $hits++;
            } else {
                if (preg_match('~\b' . preg_quote($kw, '~') . '\b~u', $txt)) $hits++;
            }
        }

        return $hits;
    }

    private function anchorCoverageHits(string $content, array $anchors): int
    {
        $txt = mb_strtolower($content);
        $hits = 0;
        foreach ($anchors as $a) {
            $a = trim(mb_strtolower((string)$a));
            if ($a === '') continue;

            if (preg_match('~\b' . preg_quote($a, '~') . '\b~u', $txt)) {
                $hits++;
            }
        }
        return $hits;
    }
}
