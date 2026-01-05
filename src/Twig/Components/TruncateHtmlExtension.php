<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TruncateHtmlExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('truncate_html', [$this, 'truncateHtml'], ['is_safe' => ['html']]),
        ];
    }

    public function truncateHtml(string $html, int $length = 150, string $suffix = '...'): string
    {
        // On supprime les balises HTML pour tronquer uniquement le texte
        $text = strip_tags($html);

        // Si le texte est plus court que la limite, on retourne le HTML original
        if (mb_strlen($text) <= $length) {
            return $html;
        }

        // Tronquer le texte proprement
        $truncatedText = mb_substr($text, 0, $length) . $suffix;

        // Retourner une version safe avec un tooltip contenant le texte complet
        return sprintf(
            '<span title="%s">%s</span>',
            htmlspecialchars($text, ENT_QUOTES),
            htmlspecialchars($truncatedText, ENT_QUOTES)
        );
    }
}
