<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Activity;
use App\Entity\User;
use App\Entity\Contact;
use App\Entity\Product;
use App\Entity\Event;
use App\Entity\EventCustomer;
use App\Entity\FundingMechanism;
use App\Entity\Opportunity;
use App\Entity\Partnership;
use Doctrine\ORM\EntityManagerInterface;

final class AiEntityKnowledgeService
{
    public function __construct(
        private EntityManagerInterface $em,
        private string $baseUrl = 'https://127.0.0.1:8000/'
    ) {}

    /**
     * Point d’entrée recommandé :
     * - Comprend une question naturelle
     * - Retourne une action: "open_directory" | "open_item" | "no_match"
     * - Retourne les liens et items trouvés
     */
    public function handleQuestion(string $question, int $limit = 8): array
    {
        $q = trim($question);

        if ($q === '') {
            return [
                'action' => 'no_match',
                'message' => 'Question vide.',
                'directory' => null,
                'resolved' => null,
                'matches' => [],
            ];
        }

        // 1) Si la question demande un répertoire (liste)
        $directory = $this->detectDirectoryIntent($q);
        if ($directory !== null) {
            // Bonus: proposer quelques éléments en aperçu
            $matches = $this->searchInIndex($q, $directory['type'], $limit);

            return [
                'action' => 'open_directory',
                'message' => $directory['message'],
                'directory' => $directory,   // {type, url}
                'resolved' => null,
                'matches' => $matches,
            ];
        }

        // 2) Résolution “directe” : id/slug demandé (si user a donné "contact 12")
        $resolved = $this->resolveLinkFromText($q);
        if ($resolved !== null) {
            return [
                'action' => 'open_item',
                'message' => 'Élément trouvé via ID/slug.',
                'directory' => null,
                'resolved' => $resolved,
                'matches' => [],
            ];
        }

        // 3) Recherche textuelle "humaine" : nom / prénom / projet / mot clé
        $matches = $this->searchInIndex($q, null, $limit);

        if (!empty($matches)) {
            // On prend le meilleur résultat comme "resolved"
            $best = $matches[0];

            return [
                'action' => 'open_item',
                'message' => 'Élément trouvé par recherche.',
                'directory' => null,
                'resolved' => $best,
                'matches' => $matches,
            ];
        }

        // 4) Rien trouvé
        return [
            'action' => 'no_match',
            'message' => "Aucun élément interne trouvé pour: {$q}",
            'directory' => null,
            'resolved' => null,
            'matches' => [],
        ];
    }

    /**
     * Retourne un index textuel exploitable par ton IA.
     * Chaque entrée contient: type, key, label, url, content, data
     */
    public function buildIndex(): array
    {
        $docs = [];

        // -------------------------
        // USERS
        // -------------------------
        foreach ($this->em->getRepository(User::class)->findAll() as $u) {
            $id = $this->getId($u);
            $label = $this->safeUserLabel($u);

            $docs[] = [
                'type' => 'user',
                'key' => (string) $id,
                'label' => $label,
                'url' => null,
                'content' => $this->normalizeText("User {$label} " . $this->getValue($u, 'getEmail')),
                'data' => [
                    'id' => $id,
                    'email' => $this->getValue($u, 'getEmail'),
                    'roles' => method_exists($u, 'getRoles') ? $u->getRoles() : [],
                ],
            ];
        }

        // -------------------------
        // CONTACTS (CUSTOMER)
        // -------------------------
        foreach ($this->em->getRepository(Contact::class)->findAll() as $c) {
            $id = $this->getId($c);
            $label = $this->safeContactLabel($c);

            $docs[] = [
                'type' => 'customer',
                'key' => (string) $id,
                'label' => $label,
                'url' => $this->contactUrl((int)$id),
                'content' => $this->normalizeText(
                    "Customer {$label} "
                    . $this->getValue($c, 'getEmail') . ' '
                    . $this->getValue($c, 'getPhone')
                ),
                'data' => [
                    'id' => $id,
                    'email' => $this->getValue($c, 'getEmail'),
                    'phone' => $this->getValue($c, 'getPhone'),
                ],
            ];
        }

        dump($docs);
        // -------------------------
        // PRODUCTS
        // -------------------------
        foreach ($this->em->getRepository(Product::class)->findAll() as $p) {
            $id = $this->getId($p);
            $label = $this->safeProductLabel($p);

            $docs[] = [
                'type' => 'product',
                'key' => (string) $id,
                'label' => $label,
                'url' => $this->productUrl((int)$id),
                'content' => $this->normalizeText("Product {$label}"),
                'data' => [
                    'id' => $id,
                ],
            ];
        }

        // -------------------------
        // EVENTS (IMPORTANT : Event, pas EventCustomer)
        // -------------------------
        foreach ($this->em->getRepository(EventCustomer::class)->findAll() as $e) {
            $slug = $this->getSlug($e);
            $label = $this->safeEventLabel($e);

            if (!$slug) {
                // fallback si pas de slug dispo
                $id = $this->getId($e);
                $slug = (string)$id;
            }

            $docs[] = [
                'type' => 'event',
                'key' => (string) $slug,
                'label' => $label,
                'url' => $this->eventUrl((string) $slug),
                'content' => $this->normalizeText("Event {$label} {$slug}"),
                'data' => [
                    'slug' => $slug,
                    'id' => $this->getId($e),
                ],
            ];
        }

        // -------------------------
        // FUNDING MECHANISM
        // -------------------------
        foreach ($this->em->getRepository(FundingMechanism::class)->findAll() as $f) {
            $id = $this->getId($f);
            $label = $this->safeFundingLabel($f);

            $docs[] = [
                'type' => 'financementMechanism',
                'key' => (string) $id,
                'label' => $label,
                'url' => $this->fundingUrl((int)$id),
                'content' => $this->normalizeText("FundingMechanism {$label}"),
                'data' => [
                    'id' => $id,
                ],
            ];
        }

        // -------------------------
        // PARTNERSHIP
        // -------------------------
        foreach ($this->em->getRepository(Partnership::class)->findAll() as $pa) {
            $id = $this->getId($pa);
            $label = $this->safePartnershipLabel($pa);

            $docs[] = [
                'type' => 'partnership',
                'key' => (string) $id,
                'label' => $label,
                'url' => $this->partnershipUrl((int)$id),
                'content' => $this->normalizeText("Partnership {$label}"),
                'data' => [
                    'id' => $id,
                ],
            ];
        }

        return $docs;
    }

    /**
     * Résout une demande si l'utilisateur a mis un ID/slug explicitement.
     */
    public function resolveLinkFromText(string $text): ?array
    {
        $text = $this->normalizeText($text);

        // customer/contact
        if (preg_match('/\b(customer|contact)\b.*\b(\d+)\b/u', $text, $m)) {
            $id = (int)$m[2];
            $entity = $this->em->getRepository(Contact::class)->find($id);

            return $entity ? [
                'type' => 'customer',
                'key' => (string)$id,
                'label' => $this->safeContactLabel($entity),
                'url' => $this->contactUrl($id),
                'data' => ['id' => $id],
            ] : null;
        }

        // product
        if (preg_match('/\b(product|produit)\b.*\b(\d+)\b/u', $text, $m)) {
            $id = (int)$m[2];
            $entity = $this->em->getRepository(Product::class)->find($id);

            return $entity ? [
                'type' => 'product',
                'key' => (string)$id,
                'label' => $this->safeProductLabel($entity),
                'url' => $this->productUrl($id),
                'data' => ['id' => $id],
            ] : null;
        }

        // funding / funder
        if (preg_match('/\b(funder|funding|financement|mechanism|mecanisme)\b.*\b(\d+)\b/u', $text, $m)) {
            $id = (int)$m[2];
            $entity = $this->em->getRepository(FundingMechanism::class)->find($id);

            return $entity ? [
                'type' => 'financementMechanism',
                'key' => (string)$id,
                'label' => $this->safeFundingLabel($entity),
                'url' => $this->fundingUrl($id),
                'data' => ['id' => $id],
            ] : null;
        }

        // partnership
        if (preg_match('/\b(partnership|partenariat)\b.*\b(\d+)\b/u', $text, $m)) {
            $id = (int)$m[2];
            $entity = $this->em->getRepository(Partnership::class)->find($id);

            return $entity ? [
                'type' => 'partnership',
                'key' => (string)$id,
                'label' => $this->safePartnershipLabel($entity),
                'url' => $this->partnershipUrl($id),
                'data' => ['id' => $id],
            ] : null;
        }

        // event by slug (si l'user met un slug)
        if (preg_match('/\b(event|événement|evenement)\b.*\b([a-z0-9\-_.]+)\b/u', $text, $m)) {
            $slug = (string)$m[2];

            // tente de trouver dans Event par slug
            $entity = $this->em->getRepository(EventCustomer::class)->findOneBy(['slug' => $slug]);

            return [
                'type' => 'event',
                'key' => $slug,
                'label' => $entity ? $this->safeEventLabel($entity) : "Event {$slug}",
                'url' => $this->eventUrl($slug),
                'data' => ['slug' => $slug],
            ];
        }

        return null;
    }

    /**
     * Recherche textuelle globale dans buildIndex()
     * - Pas besoin de connaître les champs Doctrine réels
     * - Fonctionne avec n'importe quel label/content
     */
    public function searchInIndex(string $query, ?string $typeFilter = null, int $limit = 8): array
    {
        $docs = $this->buildIndex();

        $qNorm = $this->normalizeText($query);
        if ($qNorm === '') {
            return [];
        }

        // On garde des "mots utiles" (stopwords simples FR)
        $terms = $this->extractTerms($qNorm);

        $scored = [];
        foreach ($docs as $d) {
            if ($typeFilter !== null && $d['type'] !== $typeFilter) {
                continue;
            }

            $hay = $this->normalizeText(($d['label'] ?? '') . ' ' . ($d['content'] ?? ''));

            $score = 0;

            // Score simple :
            // +10 si la query complète est contenue
            if (str_contains($hay, $qNorm)) {
                $score += 10;
            }

            // +2 par terme trouvé
            foreach ($terms as $t) {
                if ($t !== '' && str_contains($hay, $t)) {
                    $score += 2;
                }
            }

            // petit boost si label matche
            $label = $this->normalizeText((string)($d['label'] ?? ''));
            foreach ($terms as $t) {
                if ($t !== '' && str_contains($label, $t)) {
                    $score += 1;
                }
            }

            if ($score > 0) {
                $scored[] = [
                    'score' => $score,
                    'type' => $d['type'],
                    'key' => $d['key'],
                    'label' => $d['label'],
                    'url' => $d['url'] ?? null,
                    'data' => $d['data'] ?? [],
                ];
            }
        }

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        // garder top
        $top = array_slice($scored, 0, max(1, $limit));

        // enlever le champ score si tu veux
        foreach ($top as &$t) {
            unset($t['score']);
        }

        return $top;
    }

    /**
     * Détecte une intention "répertoire" :
     * ex: "quels sont les événements ?" -> /event
     * ex: "liste des clients" -> /contact
     */
    public function detectDirectoryIntent(string $question): ?array
    {
        $q = $this->normalizeText($question);

        // Events directory
        if ($this->containsAny($q, ['evenement', 'événement', 'events', 'event', 'liste des evenements', 'quels sont les evenements'])) {
            return [
                'type' => 'event',
                'url' => $this->eventDirectoryUrl(),
                'message' => 'Voici la page des événements.',
            ];
        }

        // Customers directory
        if ($this->containsAny($q, ['client', 'clients', 'customer', 'customers', 'contacts', 'contact'])) {
            // attention : "contact 12" est géré dans resolveLinkFromText avant
            // ici c'est une intention "liste"
            if (preg_match('/\b\d+\b/', $q)) {
                return null; // si un ID est présent, laisser resolveLinkFromText gérer
            }

            return [
                'type' => 'customer',
                'url' => $this->customerDirectoryUrl(),
                'message' => 'Voici la page des clients (contacts).',
            ];
        }

        // Products directory
        if ($this->containsAny($q, ['produit', 'produits', 'product', 'products'])) {
            if (preg_match('/\b\d+\b/', $q)) {
                return null;
            }

            return [
                'type' => 'product',
                'url' => $this->productDirectoryUrl(),
                'message' => 'Voici la page des produits.',
            ];
        }

        // Funding directory
        if ($this->containsAny($q, ['funder', 'funding', 'financement', 'mecanisme', 'mécanisme', 'bpifrance'])) {
            if (preg_match('/\b\d+\b/', $q)) {
                return null;
            }

            return [
                'type' => 'financementMechanism',
                'url' => $this->fundingDirectoryUrl(),
                'message' => 'Voici la page des mécanismes de financement.',
            ];
        }

        // Partnership directory
        if ($this->containsAny($q, ['partenariat', 'partnership', 'partenariats'])) {
            if (preg_match('/\b\d+\b/', $q)) {
                return null;
            }

            return [
                'type' => 'partnership',
                'url' => $this->partnershipDirectoryUrl(),
                'message' => 'Voici la page des partenariats.',
            ];
        }

        return null;
    }

    // ----------------------------------------------------------------------
    // Directory URLs (répertoires)
    // ----------------------------------------------------------------------

    public function customerDirectoryUrl(): string
    {
        return rtrim($this->baseUrl, '/') . '/contact';
    }

    public function productDirectoryUrl(): string
    {
        return rtrim($this->baseUrl, '/') . '/product';
    }

    public function eventDirectoryUrl(): string
    {
        return rtrim($this->baseUrl, '/') . '/event';
    }

    public function fundingDirectoryUrl(): string
    {
        // adapte si tu as une page list : /funder ou /funding
        return rtrim($this->baseUrl, '/') . '/funder';
    }

    public function partnershipDirectoryUrl(): string
    {
        // adapte si tu as un index /partnership
        return rtrim($this->baseUrl, '/') . '/partnership';
    }

    // ----------------------------------------------------------------------
    // Item URLs (fiche)
    // ----------------------------------------------------------------------

    private function contactUrl(int $id): string
    {
        return rtrim($this->baseUrl, '/') . "/contact/{$id}";
    }

    private function productUrl(int $id): string
    {
        return rtrim($this->baseUrl, '/') . "/product/{$id}";
    }

    private function eventUrl(string $slug): string
    {
        return rtrim($this->baseUrl, '/') . "/event/{$slug}";
    }

    private function fundingUrl(int $id): string
    {
        return rtrim($this->baseUrl, '/') . "/funder/show/{$id}";
    }

    private function partnershipUrl(int $id): string
    {
        return rtrim($this->baseUrl, '/') . "/partnership/{$id}";
    }

    // ----------------------------------------------------------------------
    // Labels (pour affichage IA)
    // ----------------------------------------------------------------------

    private function safeUserLabel(object $u): string
    {
        $parts = [];

        $fn = $this->getValue($u, 'getFirstname');
        $ln = $this->getValue($u, 'getLastname');
        $email = $this->getValue($u, 'getEmail');

        if ($fn) $parts[] = $fn;
        if ($ln) $parts[] = $ln;

        if (!$parts && $email) {
            return (string)$email;
        }

        return trim(implode(' ', $parts)) ?: 'User';
    }

    private function safeContactLabel(object $c): string
    {
        $parts = [];

        $fn = $this->getValue($c, 'getFirstname');
        $ln = $this->getValue($c, 'getLastname');
        $company = $this->getValue($c, 'getCompany');
        $email = $this->getValue($c, 'getEmail');

        if ($fn) $parts[] = $fn;
        if ($ln) $parts[] = $ln;

        $label = trim(implode(' ', $parts));

        if ($label === '' && $company) $label = (string)$company;
        if ($label === '' && $email) $label = (string)$email;

        return $label !== '' ? $label : 'Contact';
    }

    private function safeProductLabel(object $p): string
    {
        $name = $this->getValue($p, 'getName');
        return $name ?: 'Product';
    }

    private function safeEventLabel(object $e): string
    {
        $title = $this->getValue($e, 'getTitle');
        $name = $this->getValue($e, 'getName'); // fallback si ton entité s'appelle name
        return $title ?: ($name ?: 'Event');
    }

    private function safeFundingLabel(object $f): string
    {
        $name = $this->getValue($f, 'getName');
        $title = $this->getValue($f, 'getTitle');
        return $name ?: ($title ?: 'FundingMechanism');
    }

    private function safePartnershipLabel(object $p): string
    {
        $title = $this->getValue($p, 'getTitle');
        $name = $this->getValue($p, 'getName');
        return $title ?: ($name ?: 'Partnership');
    }

    // ----------------------------------------------------------------------
    // Utils
    // ----------------------------------------------------------------------

    private function getId(object $o): ?int
    {
        return method_exists($o, 'getId') ? (int)$o->getId() : null;
    }

    private function getSlug(object $o): ?string
    {
        if (method_exists($o, 'getSlug') && $o->getSlug()) {
            return (string)$o->getSlug();
        }
        return null;
    }

private function getValue(object $o, string $method): ?string
{
    if (!method_exists($o, $method)) {
        return null;
    }

    $v = $o->{$method}();

    if ($v === null) {
        return null;
    }

    // Scalar
    if (is_string($v)) {
        return trim($v);
    }
    if (is_int($v) || is_float($v)) {
        return (string) $v;
    }
    if (is_bool($v)) {
        return $v ? '1' : '0';
    }

    // Array -> join
    if (is_array($v)) {
        // ex: roles ["ROLE_ADMIN","ROLE_USER"]
        return implode(', ', array_map(fn($x) => is_scalar($x) ? (string)$x : json_encode($x), $v));
    }

    // DateTimeInterface
    if ($v instanceof \DateTimeInterface) {
        return $v->format('Y-m-d H:i');
    }

    // Doctrine Collection (PersistentCollection / ArrayCollection)
    if ($v instanceof \Traversable) {
        $items = [];
        foreach ($v as $item) {
            if (is_scalar($item)) {
                $items[] = (string) $item;
            } elseif (is_object($item) && method_exists($item, '__toString')) {
                $items[] = (string) $item;
            } elseif (is_object($item) && method_exists($item, 'getId')) {
                $items[] = '#' . $item->getId();
            } else {
                $items[] = '[item]';
            }
        }
        return implode(', ', $items);
    }

    // Object -> __toString
    if (is_object($v) && method_exists($v, '__toString')) {
        return trim((string) $v);
    }

    // Fallback: JSON
    return json_encode($v, JSON_UNESCAPED_UNICODE);
}


    private function normalizeText(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = preg_replace('/\s+/u', ' ', $s);
        return $s ?? '';
    }

    private function extractTerms(string $normalized): array
    {
        $words = preg_split('/\s+/u', $normalized) ?: [];

        $stop = [
            'le','la','les','un','une','des','du','de','d','a','à','au','aux',
            'et','ou','en','dans','sur','pour','par','avec','sans','ce','ces','cette',
            'quel','quelle','quels','quelles','liste','montre','donne','trouve','affiche',
            'est','sont','qui','quoi','où'
        ];

        $terms = [];
        foreach ($words as $w) {
            $w = trim($w);
            if ($w === '' || mb_strlen($w) < 2) continue;
            if (in_array($w, $stop, true)) continue;
            $terms[] = $w;
        }

        // dédup
        return array_values(array_unique($terms));
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $n) {
            $n = $this->normalizeText($n);
            if ($n !== '' && str_contains($haystack, $n)) {
                return true;
            }
        }
        return false;
    }

    

//     public function getCustomerDetails(int $contactId, int $limit = 30): ?array
// {
//     /** @var Contact|null $contact */
//     $contact = $this->em->getRepository(Contact::class)->find($contactId);
//     if (!$contact) {
//         return null;
//     }

//     $label = $this->safeContactLabel($contact);

//     // Activities
//     $activities = $this->em->getRepository(Activity::class)->findBy(
//         ['contact' => $contact],
//         ['activityDate' => 'DESC'],
//         $limit
//     );

//     // Opportunities
//     $opportunities = $this->em->getRepository(Opportunity::class)->findBy(
//         ['contact' => $contact],
//         ['createdAt' => 'DESC'],
//         $limit
//     );

//     // Mapper en tableau simple (JSON-friendly)
//     $activitiesArr = array_map(function (Activity $a) {
//         return [
//             'id' => $a->getId(),
//             'type' => $a->getType(),
//             'status' => $a->getStatus(),
//             'date' => $a->getActivityDate()?->format('Y-m-d H:i'),
//             'description' => $a->getDescription(),
//         ];
//     }, $activities);

//     $opportunitiesArr = array_map(function (Opportunity $o) {
//         return [
//             'id' => $o->getId(),
//             'leadSource' => $o->getLeadSource(),
//             'stage' => $o->getStage(),
//             'createdAt' => $o->getCreatedAt()?->format('Y-m-d H:i'),
//             'user' => $o->getUser() ? $this->safeUserLabel($o->getUser()) : null,
//             'userId' => $o->getUser()?->getId(),
//         ];
//     }, $opportunities);

//     return [
//         'customer' => [
//             'id' => $contact->getId(),
//             'label' => $label,
//             'url' => $this->contactUrl((int)$contact->getId()),
//             'email' => $this->getValue($contact, 'getEmail'),
//             'phone' => $this->getValue($contact, 'getPhone'),
//         ],
//         'activities' => [
//             'count' => count($activitiesArr),
//             'items' => $activitiesArr,
//         ],
//         'opportunities' => [
//             'count' => count($opportunitiesArr),
//             'items' => $opportunitiesArr,
//         ],
//     ];
// }

public function handleContactQuestion(string $question, int $limit = 8): array
{
    $q = trim($question);
    if ($q === '') {
        return [
            'action' => 'no_match',
            'message' => 'Question vide.',
            'directory' => null,
            'resolved' => null,
            'matches' => [],
        ];
    }

    // Intent "liste des clients"
    $qn = $this->normalizeText($q);
    if ($this->containsAny($qn, ['client', 'clients', 'customer', 'customers', 'contacts', 'contact'])) {
        // Si pas d'ID explicite -> répertoire
        if (!preg_match('/\b\d+\b/', $qn)) {
            return [
                'action' => 'open_directory',
                'message' => 'Voici la page des clients (contacts).',
                'directory' => [
                    'type' => 'customer',
                    'url' => $this->customerDirectoryUrl(),
                ],
                'resolved' => null,
                'matches' => $this->searchInIndex($q, 'customer', $limit),
            ];
        }
    }

    // Si l'utilisateur donne explicitement "contact 12"
    $resolved = $this->resolveContactLinkFromText($q);
    if ($resolved) {
        return [
            'action' => 'open_item',
            'message' => 'Contact trouvé via ID.',
            'directory' => null,
            'resolved' => $resolved,
            'matches' => [],
        ];
    }

    // Recherche texte (nom/prénom/email/phone, etc.) UNIQUEMENT sur customers
    $matches = $this->searchInIndex($q, 'customer', $limit);

    if (!empty($matches)) {
        return [
            'action' => 'open_item',
            'message' => 'Contact trouvé par recherche.',
            'directory' => null,
            'resolved' => $matches[0],
            'matches' => $matches,
        ];
    }

    return [
        'action' => 'no_match',
        'message' => 'Aucun contact trouvé.',
        'directory' => [
            'type' => 'customer',
            'url' => $this->customerDirectoryUrl(),
        ],
        'resolved' => null,
        'matches' => [],
    ];
}


public function resolveContactLinkFromText(string $text): ?array
{
    $text = $this->normalizeText($text);

    if (preg_match('/\b(customer|contact)\b.*\b(\d+)\b/u', $text, $m)) {
        $id = (int)$m[2];
        $entity = $this->em->getRepository(Contact::class)->find($id);

        return $entity ? [
            'type' => 'customer',
            'key' => (string)$id,
            'label' => $this->safeContactLabel($entity),
            'url' => $this->contactUrl($id),
            'data' => ['id' => $id],
        ] : null;
    }

    return null;
}
   public function getCustomerDetails(int $contactId, int $limit = 20): ?array
    {
        /** @var Contact|null $contact */
        $contact = $this->em->getRepository(Contact::class)->find($contactId);
        if (!$contact) return null;

        $activities = $this->em->getRepository(Activity::class)->findBy(
            ['contact' => $contact],
            ['activityDate' => 'DESC'],
            $limit
        );

        $opps = $this->em->getRepository(Opportunity::class)->findBy(
            ['contact' => $contact],
            ['createdAt' => 'DESC'],
            $limit
        );

        $activitiesArr = array_map(static fn(Activity $a) => [
            'id' => $a->getId(),
            'type' => $a->getType(),
            'status' => $a->getStatus(),
            'date' => $a->getActivityDate()?->format('Y-m-d H:i'),
            // si tu stockes parfois du HTML, on garde tel quel et on nettoie plus bas dans le journal
            'description' => $a->getDescription(),
        ], $activities);

        $oppsArr = array_map(fn(Opportunity $o) => [
            'id' => $o->getId(),
            'leadSource' => $o->getLeadSource(),
            'stage' => $o->getStage(),
            'createdAt' => $o->getCreatedAt()?->format('Y-m-d H:i'),
            'userId' => $o->getUser()?->getId(),
            'user' => $o->getUser() ? $this->getValue($o->getUser(), 'getEmail') : null,
        ], $opps);

        return [
            'customer' => [
                'id' => $contact->getId(),
                'label' => $this->safeContactLabel($contact),
                'url' => $this->contactUrl((int)$contact->getId()),
                'email' => $this->getValue($contact, 'getEmail'),
                'phone' => $this->getValue($contact, 'getPhone'),
            ],
            'activities' => $activitiesArr,
            'opportunities' => $oppsArr,
        ];
    }

    private function stripHtml(?string $s): string
    {
        $s = (string)($s ?? '');
        $s = trim(strip_tags($s));
        // petites normalisations
        $s = preg_replace('/\s+/', ' ', $s) ?: '';
        return $s;
    }

    public function buildCustomerJournal(array $customerDetails, int $maxItems = 8): string
    {
        $c = $customerDetails['customer'] ?? [];
        $activities = (array)($customerDetails['activities'] ?? []);
        $opps = (array)($customerDetails['opportunities'] ?? []);

        $out = [];
        $out[] = "CONTACT_LABEL: " . ($c['label'] ?? '-');
        $out[] = "EMAIL_CONTACT: " . (($c['email'] ?? '') !== '' ? $c['email'] : 'N/A');
        $out[] = "FICHE_CONTACT: " . (($c['url'] ?? '') !== '' ? $c['url'] : 'N/A');
        $out[] = "";

        $out[] = "ACTIVITES:";
        foreach (array_slice($activities, 0, $maxItems) as $a) {
            if (!is_array($a)) continue;
            $desc = $this->stripHtml($a['description'] ?? '');
            $out[] = "- [" . ($a['date'] ?? '-') . "] type=" . ($a['type'] ?? '-') . " statut=" . ($a['status'] ?? '-') . " | desc=" . ($desc !== '' ? $desc : 'N/A');
        }

        $out[] = "";
        $out[] = "OPPORTUNITES:";
        foreach (array_slice($opps, 0, $maxItems) as $o) {
            if (!is_array($o)) continue;
            $out[] = "- [" . ($o['createdAt'] ?? '-') . "] stage=" . ($o['stage'] ?? '-') . " lead=" . ($o['leadSource'] ?? '-') . " user=" . ($o['user'] ?? '-');
        }

        return trim(implode("\n", $out));
    }

    public function getLastActivity(int $contactId): ?array
{
    /** @var Contact|null $contact */
    $contact = $this->em->getRepository(Contact::class)->find($contactId);
    if (!$contact) return null;

    /** @var Activity|null $a */
    $a = $this->em->getRepository(Activity::class)->findOneBy(
        ['contact' => $contact],
        ['activityDate' => 'DESC']
    );

    if (!$a) return null;

    $desc = trim(strip_tags((string)($a->getDescription() ?? '')));
    $desc = preg_replace('/\s+/', ' ', $desc) ?: '';

    return [
        'id' => $a->getId(),
        'date' => $a->getActivityDate()?->format('Y-m-d H:i'),
        'type' => $a->getType(),
        'status' => $a->getStatus(),
        'description' => $desc !== '' ? $desc : null,
    ];
}
}
