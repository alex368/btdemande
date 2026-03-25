<?php
// src/Service/LlmService.php
namespace App\Service;

use App\Model\LlmSettingDto;
use App\Model\LlmUserContext;
use LLPhant\Chat\OpenAIChat;
use LLPhant\OpenAIConfig;

class LlmService
{
    private OpenAIChat $chat;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $model,
    )
    {
        if ('' === trim($this->apiKey)) {
            return;
        }

        $config = new OpenAIConfig();
        $config->apiKey = $this->apiKey;
        $config->url = $this->baseUrl;
        $config->model = $this->model;

        $this->chat = new OpenAIChat($config);
    }
    public function generate(string $prompt, array $options = []): string
    {
        $this->assertConfigured();

        return $this->chat->generateText($prompt, $options);
    }


    public function getChat(): OpenAIChat
    {
        $this->assertConfigured();

        return $this->chat;
    }


    public function translateContent(string $instruction, string $textToTranslate): string
    {
        $prompt = <<<PROMPT
{$instruction}

Texte :
{$textToTranslate}
PROMPT;

        return $this->chat->generateText($prompt);
    }


    /**
     * 0) Génération d’un plan global (titres des fiches hors Intro/Conclusion)
     */
    public function generateRevisionPlan(string $context, array $historyTitles = []): array
    {
        // Historique pour éviter redondances
        $history = !empty($historyTitles)
            ? "Titres déjà générés :\n- " . implode("\n- ", $historyTitles)
            : "Aucun titre encore généré.";

        // Prompt envoyé au modèle
        $prompt = <<<PROMPT
Tu es un professeur universitaire expert en pédagogie.  
Ta mission : à partir des notes/documents ci-dessous, proposer un **plan structuré de fiches de révision** destiné à des étudiants.  

🎯 Objectif :  
Créer une liste de titres de fiches permettant de couvrir tout le contenu de manière claire, progressive et sans redondance.

⚡ Contraintes de sortie :  
- Retourne UNIQUEMENT une liste de titres, un par ligne.  
- Aucune phrase explicative avant/après.  
- Pas de numérotation, pas de puces, pas de tirets.  
- Ne propose pas "Introduction" ni "Conclusion" (elles seront ajoutées automatiquement).  
- Pas de doublons ni de reformulations synonymes → regroupe les thèmes proches.  
- Chaque titre doit être :  
  - autonome (un seul thème par fiche),  
  - clair, concis et révisable seul,  
  - style : court et nominal, ex. *Contrôle exclusif*, *Intégration fiscale*.  
- Organisation : progression pédagogique logique (bases → mécanismes → approfondissements → enjeux/risques).  
- Nombre de titres : 10 à 12 maximum.  
- Chaque plan doit compléter les autres déjà générés (pas de répétition inutile).

📘 Historique déjà généré :  
{$history}

📘 Notes/documents :
---
{$context}
---
PROMPT;

        // Génération brute
        $titlesRaw = $this->generate($prompt, [
            'max_tokens'   => 600,
            'temperature'  => 0.1,
        ]);

        // Transformation en tableau
        $titles = array_filter(array_map('trim', explode("\n", $titlesRaw)));

        // Nettoyage : suppression doublons insensibles à la casse
        $normalized = [];
        $uniqueTitles = [];

        foreach ($titles as $t) {
            $norm = mb_strtolower($t);
            if (!in_array($norm, $normalized) && !in_array($norm, ['introduction', 'conclusion'])) {
                $normalized[] = $norm;
                $uniqueTitles[] = $t; // garde la casse originale
            }
        }

        return array_values($uniqueTitles);
    }


    /**
     * 1) Génération d’une fiche de révision (hors Intro/Conclusion)
     */
    public function generateRevisionCard(string $title, string $context, array $historyCards = []): string
    {
        $history = !empty($historyCards)
            ? "Fiches déjà générées :\n" . implode("\n", array_map(
                fn($c) => "- " . $c['title'],
                $historyCards
            ))
            : "Aucune fiche encore générée.";

        $prompt = <<<PROMPT
Tu es un professeur universitaire expert. 
Rédige une **fiche de révision détaillée, claire et pédagogique** pour le thème "{$title}" en te basant sur le contexte fourni.

⚡ Contraintes :

Retourne uniquement du texte en HTML (aucun Markdown, aucun texte hors HTML).  
⚠️ NE PAS répéter le titre, il est déjà fourni séparément.

Structure OBLIGATOIRE pour chaque fiche :

<p><strong>Définition :</strong> … (2-3 phrases claires, introductives)</p>

<p><strong>Critères / Conditions :</strong></p>
<ul><li>…</li><li>…</li><li>…</li></ul>

<p><strong>Développement détaillé :</strong></p>
<p>… explications complètes, exemples, mise en contexte, comparaisons, approfondissements.</p>

<p><strong>Tableau récapitulatif :</strong></p> si pertinent →
<table><thead><tr><th>…</th><th>…</th></tr></thead><tbody><tr><td>…</td><td>…</td></tr></tbody></table>

<div class="exemple"><strong>Exemple :</strong> cas concret, chiffres, mini-étude de cas</div>

<div class="note"><strong>À retenir :</strong><ul><li>…</li><li>…</li><li>…</li><li>…</li></ul></div>

<p><strong>Exercice d'application :</strong> …</p>
<p><strong>Correction :</strong> …</p>

<p><strong>Question d'examen possible :</strong> …</p>

Si le contexte contient des formules → affiche-les en LaTeX entre $$ ... $$.

Longueur : 400–600 mots par fiche → phrases fluides, pédagogiques, avec profondeur universitaire.

Supprimer doublons et redites → privilégier une synthèse claire et unique.

Chaque fiche doit compléter les autres déjà générées (pas de répétition inutile).

📘 Historique :
{$history}

📘 Contexte :
---
{$context}
---
PROMPT;

        $card = $this->generate($prompt, [
            'max_tokens' => 3000, // ⚡ autorise beaucoup plus de texte
            'temperature' => 0.2,
        ]);

        return trim($card);
    }


    /**
     * 2) Génération de l’ensemble des fiches (Intro + fiches + Conclusion)
     */
    public function generateFullRevisionSet(string $context): string
    {
        // Étape 1 : générer le plan global
        $plan = $this->generateRevisionPlan($context);

        // Étape 2 : ajouter Intro et Conclusion
        $titles = array_merge(["Introduction"], $plan, ["Conclusion"]);
        $cards = [];

        // Étape 3 : générer chaque fiche
        foreach ($titles as $title) {
            $card = $this->generateRevisionCard($title, $context, $cards);
            //dd($card);
            $cards[] = [
                'title' => $title,
                'content' => $card,
            ];
        }

        return $this->assembleRevisionJson($cards);
    }


    /**
     * 3) Assemblage final en JSON [{title, content}]
     */
    public function assembleRevisionJson(array $cards): string
    {
        return json_encode($cards, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function assertConfigured(): void
    {
        if (!isset($this->chat)) {
            throw new \RuntimeException('APP_LLM_OPENAI_API_KEY is not configured.');
        }
    }





    // ... tout ton code déjà présent

    public function regenerateContent(string $prompt, string $currentContent): string
    {
        $finalPrompt = <<<PROMPT
Tu es un assistant pédagogique.  
Ta mission est de transformer le contenu fourni selon l'instruction utilisateur.  

Contenu initial :
---
{$currentContent}
---

Instruction utilisateur :
{$prompt}

Règles obligatoires :
1. La sortie doit être UNIQUEMENT du **HTML valide**.  
2. AUCUN texte en dehors des balises HTML n’est autorisé (pas de phrase explicative, pas d’introduction, pas de commentaire).  
3. AUCUN format interdit : pas de Markdown, pas de ```html, pas de CSS externe ou interne, pas de commentaires HTML <!-- -->.  
4. Balises autorisées exclusivement :  
   - <h1>, <h2>, <h3>  
   - <p>  
   - <ul><li>…</li></ul>, <ol><li>…</li></ol>  
   - <blockquote>  
   - <table>…</table>  
   - <div class="note">, <div class="exemple">, <div class="exercice">, <div class="correction">  
5. Si l’instruction demande une traduction, traduire l’intégralité du contenu dans la langue demandée.  
6. Le rendu doit être directement exploitable dans TinyMCE sans modification.  
7. Le résultat final doit contenir uniquement le HTML, rien d’autre.  

PROMPT;


        // Génération brute
        $output = $this->generate($finalPrompt, [
            'max_tokens' => 4000,
            'temperature' => 0.7,
        ]);

        // Nettoyage éventuel des parasites (Markdown, fences, titres ###)
        $output = preg_replace('/```.*```/sU', '', $output); // supprime les blocs ```...```
        $output = preg_replace('/^#+\s*/m', '', $output);   // supprime "##", "###" en début de ligne

        return trim($output);
    }








    /**
     * Génère une question pédagogique sur le cours.
     */
    public function generateFlashcardQuestion(string $courseContent, string $title, string $language, string $level): string
    {

        //  $userLanguage = $userContext->language;
        // $userLevel = $userContext->level;

        $prompt = <<<PROMPT

        ---
 **Langue utilisé pour la question:**
────────────────────────────
{$language}
────────────────────────────
Tu es un enseignant et tu dois crée une question sur ce cours {$title}.
En te basant uniquement sur le texte suivant, génère une **question pédagogique pertinente** destinée à un étudiant :

-----
{$courseContent}
-----

⚠️ Important :
- N’invente pas de faits, ne devine rien.
- Reste dans le contexte donné uniquement.
- Fournis seulement une question claire (pas de réponse, pas d’explication).

---
 **Langue utilisé pour la question:**
────────────────────────────
{$language}
────────────────────────────
PROMPT;

        $card = $this->generate($prompt, [
            'max_tokens' => 2000, // ⚡ autorise beaucoup plus de texte
            'temperature' => 0.2,
        ]);

        return trim($card);
    }

    /**
     * Génère une réponse pédagogique à partir d’une question et du contenu du cours.
     */
    public function generateFlashcardAnswer(string $courseContent, string $question, string $title, string $language, string $level): string
    {
        $prompt = <<<PROMPT
🎓 **Rôle :**
Tu es un enseignant expert qui crée des flashcards pédagogiques **visuelles, claires et compatibles avec Anki (version ≥ 23)** à partir d’un extrait du cours intitulé **« {$title} »**.

---

📘 **Extrait du cours :**
────────────────────────────
{$courseContent}
────────────────────────────

❓ **Question posée dans la langue suivante :** {$language}
────────────────────────────
{$question}
────────────────────────────

---

🧠 **Consignes pédagogiques à respecter impérativement :**

1. Réponds **clairement** et **uniquement** à la question posée, sans ajout d’informations extérieures.
2. Utilise **exclusivement** les informations du cours fourni.
3. Sois **pédagogique**, **structuré** et **agréable à lire** (format carte mémoire Anki).
4. La réponse doit être en **HTML pur**, sans Markdown ni balisage inutile (pas de ```html ou ```).
5. Pour les formules mathématiques :
   - Utilise **uniquement** le format LaTeX **MathJax** compatible avec Anki 25.x :
     - `\\( ... \\)` pour les formules *en ligne* ;
     - `\\[ ... \\]` pour les formules *centrées ou multilignes*.
   - ❌ **N’utilise jamais** les balises `[latex]...[/latex]` (obsolètes).
   - ❌ Ne place **aucune formule dans des balises HTML** (`<span>`, `<p>`, etc.).
   - ✅ **Si la formule est longue ou complexe**, affiche-la **en vertical (multilignes)** avec :
     \[
     \\begin{aligned}
     a &= b + c \\\\
     &+ d - e
     \\end{aligned}
     \]
     Cela doit être appliqué automatiquement pour toutes les formules de plusieurs termes.
6. ✅ **Mise en forme visuelle :**
   - Titres : `<p style="color:#805ad5;font-weight:bold;font-size:1.1em;">Titre :</p>`
   - Mots-clés : `<span style="color:#2b6cb0;font-weight:bold;">Mot</span>`
   - Définitions : `<span style="background-color:#ebf8ff;padding:3px 6px;border-radius:5px;">Texte</span>`
   - Exemples : `<span style="color:#38a169;">Exemple :</span>`
   - Structure avec `<p>`, `<ul>`, `<li>`, `<br>` pour la lisibilité.
7. Structure toujours ta réponse :
   - Un titre clair (Définition, Formule, Étapes, Exemple…)
   - Une explication concise
   - Une formule centrée et lisible (`\\[ ... \\]`)
8. Ne commence jamais par “Voici la réponse” ou une introduction générique.
9. Fournis **uniquement la réponse finale HTML + LaTeX (MathJax)** prête à être utilisée dans Anki.

---

🎨 **Exemple de réponse attendue :**

<p style="color:#805ad5;font-weight:bold;font-size:1.1em;">Formule :</p>
<p>La <span style="color:#2b6cb0;font-weight:bold;">Capacité d’autofinancement</span> (CAF) peut se calculer à partir de l’EBE :</p>

\\[
\\begin{aligned}
\\text{CAF} =\;& \\text{Excédent brut d’exploitation} \\\\
&+ \\text{Autres produits d’exploitation} \\\\
&- \\text{Autres charges d’exploitation} \\\\
&+ \\text{Produits financiers} \\\\
&- \\text{Charges financières} \\\\
&- \\text{Participation des salariés} \\\\
&- \\text{Impôt sur les sociétés}
\\end{aligned}
\\]

---

✅ **Ne renvoie que le HTML final (avec LaTeX vertical pour les longues formules).**
PROMPT;

        $card = $this->generate($prompt, [
            'max_tokens' => 2000,
            'temperature' => 0.2,
        ]);

        return trim($card);
    }



    /**
     * Génère une flashcard complète : question → réponse → format final.
     */
    public function generateFlashcard(string $courseContent, string $title, string $language = 'français', string $level = 'débutant'): array
    {
        // 1. Génère la question
        $question = $this->generateFlashcardQuestion($courseContent, $title, $language, $level);

        // 2. Génère la réponse
        $answer = $this->generateFlashcardAnswer($courseContent, $question, $title, $language, $level);

        // 3. Assemble la flashcard finale
        return $this->assembleFlashcard($question, $answer);
    }

    /**
     * Construit le format JSON final d’une flashcard.
     */
    public function assembleFlashcard(string $question, string $answer): array
    {
        // Nettoyage simple de doublons éventuels
        $cleanQuestion = trim($question);
        $cleanAnswer = trim($answer);

        if (stripos($cleanAnswer, $cleanQuestion) !== false) {
            $cleanAnswer = str_ireplace($cleanQuestion, '', $cleanAnswer);
            $cleanAnswer = trim($cleanAnswer);
        }

        return [
            'deckName' => 'AutoGen::Flashcards',
            'modelName' => 'Basic',
            'fields' => [
                'Front' => $cleanQuestion,
                'Back' => $cleanAnswer,
            ],
            'tags' => ['generated'],
        ];
    }


    /**
     * Génère une flashcard complète (question + réponse) et retourne le JSON final.
     */
    public function generateFlashcardtest(string $courseContent, string $title, string $language = 'français', string $level = 'débutant'): string
    {
        // Étape 1 : Génération de la question
        $question = $this->generateFlashcardQuestion($courseContent, $title, $language, $level);

        // Étape 2 : Génération de la réponse
        $answer = $this->generateFlashcardAnswer($courseContent, $question, $title, $language, $level);

        // Étape 3 : Assemblage de la flashcard
        $flashcard = $this->assembleFlashcard($question, $answer);

        // Étape 4 : Encodage JSON
        return json_encode($flashcard, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
