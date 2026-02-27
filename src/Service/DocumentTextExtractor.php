<?php

namespace App\Service;

use Symfony\Component\Process\Process;

final class DocumentTextExtractor
{
    public function __construct(
        private ?string $allowedBaseDir = null,  // ex: %kernel.project_dir%/var/uploads
        private int $maxFileSizeMb = 40,
        private int $maxOutputChars = 2_000_000,
        private int $processTimeout = 120,

        // OCR settings
        private string $tesseractLang = 'fra+eng',
        private int $pdfRenderDpi = 400,
        private bool $enableImagePreprocess = true, // nécessite ImageMagick (convert) si true
        private int $minCharsBeforeOcr = 200        // si pdftotext < 200 chars => OCR
    ) {}

    public function extract(string $absolutePath): string
    {
        $real = realpath($absolutePath);
        if ($real === false || !is_file($real)) {
            throw new \RuntimeException("Fichier introuvable : $absolutePath");
        }

        if ($this->allowedBaseDir !== null) {
            $base = realpath($this->allowedBaseDir);
            if ($base !== false && !str_starts_with($real, $base)) {
                throw new \RuntimeException("Accès fichier interdit.");
            }
        }

        $size = filesize($real);
        if ($size !== false && $size > ($this->maxFileSizeMb * 1024 * 1024)) {
            throw new \RuntimeException("Fichier trop volumineux (> {$this->maxFileSizeMb}MB).");
        }

        $ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));

        $text = match ($ext) {
            'pdf'   => $this->fromPdfLowMemory($real),
            'pptx'  => $this->fromPptxLowMemory($real),
            'txt'   => (string) file_get_contents($real),
            'jpg', 'jpeg', 'png', 'webp' => $this->fromImageOcr($real),
            'docx'  => $this->fromDocxLowMemory($real),
            'xlsx', 'xls', 'csv' => $this->fromSpreadsheetLowMemory($real),
            default => throw new \RuntimeException("Format non supporté : .$ext"),
        };

        $text = $this->normalize($text);

        if (mb_strlen($text) > $this->maxOutputChars) {
            $text = mb_substr($text, 0, $this->maxOutputChars) . "\n\n[TRUNCATED]";
        }

        return $text;
    }

    /**
     * Retourne une liste de segments paginés.
     * @return array<int, array{page:int, text:string}>
     */
    public function extractPaged(string $absolutePath): array
    {
        $real = realpath($absolutePath);
        if ($real === false || !is_file($real)) {
            throw new \RuntimeException("Fichier introuvable : $absolutePath");
        }

        $ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => $this->fromPdfPaged($real),
            default => [['page' => 1, 'text' => $this->extract($real)]],
        };
    }

    // =======================
    // PDF (Poppler) + OCR scan (pdftoppm -> tesseract)
    // =======================

    private function fromPdfLowMemory(string $path): string
    {
        // Tentative extraction texte
        $text = $this->runCommand(
            ['pdftotext', $path, '-', '-enc', 'UTF-8'],
            allowFailure: true
        ) ?? '';

        // Si scanné / quasi vide => OCR page par page (robuste)
        if (mb_strlen(trim($text)) < $this->minCharsBeforeOcr) {
            $pages = $this->getPdfPageCount($path);

            // page count inconnu => fallback "tout le doc" (rendu toutes pages)
            if ($pages <= 0) {
                $ocrAll = $this->ocrPdfAllPages($path);
                return $ocrAll !== '' ? "[OCR]\n" . $ocrAll : $text;
            }

            $ocrText = '';
            for ($p = 1; $p <= $pages; $p++) {
                $pageOcr = trim($this->ocrPdfSinglePage($path, $p));
                if ($pageOcr !== '') {
                    $ocrText .= "[OCR_PAGE {$p}] " . $pageOcr . "\n";
                }
            }

            $ocrText = trim($ocrText);
            if ($ocrText !== '') {
                return "[OCR]\n" . $ocrText;
            }
        }

        return $text;
    }

    /**
     * @return array<int, array{page:int, text:string}>
     */
    private function fromPdfPaged(string $path): array
    {
        $pages = $this->getPdfPageCount($path);
        if ($pages <= 0) {
            return [['page' => 1, 'text' => $this->fromPdfLowMemory($path)]];
        }

        $segments = [];

        for ($p = 1; $p <= $pages; $p++) {
            // Texte de la page
            $txt = $this->runCommand(
                ['pdftotext', '-f', (string)$p, '-l', (string)$p, $path, '-', '-enc', 'UTF-8'],
                allowFailure: true
            ) ?? '';

            $txt = trim($txt);

            // Si page scannée / vide => OCR rendu page
            if (mb_strlen($txt) < $this->minCharsBeforeOcr) {
                $ocr = trim($this->ocrPdfSinglePage($path, $p));
                if ($ocr !== '') {
                    $txt = $ocr;
                }
            }

            $txt = $this->normalize($txt);
            if ($txt !== '') {
                $segments[] = ['page' => $p, 'text' => $txt];
            }
        }

        if (!$segments) {
            $segments[] = ['page' => 1, 'text' => $this->fromPdfLowMemory($path)];
        }

        return $segments;
    }

    private function getPdfPageCount(string $path): int
    {
        $out = $this->runCommand(['pdfinfo', $path], allowFailure: true);
        if (!$out) return 0;

        if (preg_match('/^Pages:\s+(\d+)/mi', $out, $m)) {
            return (int)$m[1];
        }

        return 0;
    }

    private function ocrPdfAllPages(string $path): string
    {
        $pages = $this->getPdfPageCount($path);
        if ($pages <= 0) {
            // on tente quand même page 1
            $one = trim($this->ocrPdfSinglePage($path, 1));
            return $one;
        }

        $text = '';
        for ($p = 1; $p <= $pages; $p++) {
            $pageOcr = trim($this->ocrPdfSinglePage($path, $p));
            if ($pageOcr !== '') {
                $text .= "[OCR_PAGE {$p}] " . $pageOcr . "\n";
            }
        }
        return trim($text);
    }

    /**
     * OCR robuste d’une page scannée :
     * - rendu image via pdftoppm (PNG, grayscale, DPI élevé)
     * - pré-traitement optionnel via ImageMagick (convert)
     * - tesseract LSTM + PSM “block of text”
     */
    private function ocrPdfSinglePage(string $path, int $page): string
{
    $tmpDir = $this->makeTmpDir('pdf_ocr_');

    try {
        $prefix = $tmpDir . '/page';

        // 1) Rendu image haute résolution (scan)
        $this->runCommand(
            [
                'pdftoppm',
                '-png',
                '-gray',
                '-r', '400',
                '-f', (string)$page,
                '-l', (string)$page,
                '-cropbox',
                $path,
                $prefix
            ],
            allowFailure: false
        );

        $images = glob($tmpDir . '/*.png') ?: [];
        if (!$images) return '';

        $img = $images[0];
        $prep = $tmpDir . '/prep.png';

        // 2) Pré-traitement (ImageMagick) : réduction bruit + contraste + binarisation légère
        // Ajuste le threshold (55-70%) selon tes scans.
        $this->runCommand(
            [
                'sh', '-lc',
                'if command -v magick >/dev/null 2>&1; then ' .
                'magick ' . escapeshellarg($img) . ' -colorspace Gray -deskew 40% -median 1 -normalize -contrast-stretch 0x15% -threshold 62% ' . escapeshellarg($prep) . '; ' .
                'elif command -v convert >/dev/null 2>&1; then ' .
                'convert ' . escapeshellarg($img) . ' -colorspace Gray -deskew 40% -median 1 -normalize -contrast-stretch 0x15% -threshold 62% ' . escapeshellarg($prep) . '; ' .
                'else cp ' . escapeshellarg($img) . ' ' . escapeshellarg($prep) . '; fi'
            ],
            allowFailure: false
        );

        // 3) OCR en 2 passes (selon layout, l’une sera souvent meilleure)
        $common = [
            'tesseract', $prep, 'stdout',
            '-l', $this->tesseractLang, // fra+eng
            '--oem', '1',
            '-c', 'preserve_interword_spaces=1'
        ];

        // Pass A: bloc de texte
        $txtA = $this->runCommand(array_merge($common, ['--psm', '6']), allowFailure: true) ?? '';
        $txtA = trim($txtA);

        // Pass B: texte en colonnes/lignes (souvent mieux sur docs administratifs)
        $txtB = $this->runCommand(array_merge($common, ['--psm', '4']), allowFailure: true) ?? '';
        $txtB = trim($txtB);

        @unlink($img);
        @unlink($prep);

        // 4) Choisir la meilleure sortie (heuristique simple : plus de lettres, moins de symboles)
        $scoreA = $this->ocrQualityScore($txtA);
        $scoreB = $this->ocrQualityScore($txtB);

        $best = $scoreB > $scoreA ? $txtB : $txtA;

        return trim($best);
    } finally {
        $this->removeDirRecursive($tmpDir);
    }
}

private function ocrQualityScore(string $text): int
{
    $text = trim($text);
    if ($text === '') return 0;

    // +1 par lettre, -1 par symboles type "<", ">", "_", etc.
    $letters = preg_match_all('/\p{L}/u', $text) ?: 0;
    $symbols = preg_match_all('/[<>{}\[\]_=|~`^]/u', $text) ?: 0;

    return (int)$letters - (int)$symbols;
}


    private function preprocessImageIfPossible(string $imgPath): void
    {
        // ImageMagick n’est pas installé ? => skip (sans casser)
        // Sur Alpine, la commande est généralement "convert" (imagemagick6) ou "magick" (imagemagick7).
        $cmd = 'if command -v magick >/dev/null 2>&1; then ' .
            'magick ' . escapeshellarg($imgPath) .
            ' -colorspace Gray -normalize -contrast-stretch 0x10% -threshold 60% ' . escapeshellarg($imgPath) .
            '; elif command -v convert >/dev/null 2>&1; then ' .
            'convert ' . escapeshellarg($imgPath) .
            ' -colorspace Gray -normalize -contrast-stretch 0x10% -threshold 60% ' . escapeshellarg($imgPath) .
            '; fi';

        $this->runCommand(['sh', '-lc', $cmd], allowFailure: true);
    }

private function makeTmpDir(string $prefix): string
{
    $baseTmp = __DIR__ . '/../../var/tmp';
    if (!is_dir($baseTmp) && !mkdir($baseTmp, 0777, true) && !is_dir($baseTmp)) {
        throw new \RuntimeException("Impossible de créer var/tmp");
    }

    $tmpDir = $baseTmp . '/' . $prefix . bin2hex(random_bytes(8));
    if (!mkdir($tmpDir, 0777, true) && !is_dir($tmpDir)) {
        throw new \RuntimeException("Impossible de créer le dossier temporaire.");
    }

    return $tmpDir;
}


    // =======================
    // PPTX low memory (Zip + XML)
    // =======================

    private function fromPptxLowMemory(string $path): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException("Impossible d'ouvrir le PPTX.");
        }

        try {
            $text = '';
            $slides = [];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (preg_match('#^ppt/slides/slide(\d+)\.xml$#', $name, $m)) {
                    $slides[(int)$m[1]] = $name;
                }
            }

            ksort($slides);

            foreach ($slides as $idx => $name) {
                $xml = $zip->getFromName($name);
                if (!$xml) continue;

                preg_match_all('/<a:t[^>]*>(.*?)<\/a:t>/s', $xml, $m);
                $slideText = implode(' ', array_map(
                    static fn($s) => html_entity_decode(strip_tags((string)$s), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    $m[1] ?? []
                ));

                $slideText = trim(preg_replace('/\s+/', ' ', $slideText));
                if ($slideText !== '') {
                    $text .= "[SLIDE {$idx}] {$slideText}\n";
                }
            }

            return $text;
        } finally {
            $zip->close();
        }
    }

    // =======================
    // DOCX low memory (Zip + XML)
    // =======================

    private function fromDocxLowMemory(string $path): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException("Impossible d'ouvrir le DOCX.");
        }

        try {
            $xml = $zip->getFromName('word/document.xml') ?: '';
        } finally {
            $zip->close();
        }

        if ($xml === '') {
            return '';
        }

        preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/s', $xml, $m);
        $text = implode(' ', array_map(
            static fn($s) => html_entity_decode(strip_tags((string)$s), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            $m[1] ?? []
        ));

        $text = trim(preg_replace('/\s+/', ' ', $text));
        return $text === '' ? '' : "[DOCX] " . $text;
    }

    // =======================
    // Spreadsheet low memory
    // =======================

    private function fromSpreadsheetLowMemory(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext === 'csv') {
            $content = file_get_contents($path) ?: '';
            return "[CSV]\n" . $content;
        }

        if ($ext !== 'xlsx') {
            throw new \RuntimeException("Format tableur .$ext non supporté en low-memory. Convertis en .xlsx ou .csv.");
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException("Impossible d'ouvrir le XLSX.");
        }

        try {
            $shared = [];
            $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($sharedXml) {
                preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $sharedXml, $m);
                $shared = array_map(
                    static fn($s) => html_entity_decode(strip_tags((string)$s), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    $m[1] ?? []
                );
            }

            $text = "[XLSX]\n";

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (!preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) continue;

                $xml = $zip->getFromName($name);
                if (!$xml) continue;

                $text .= "[SHEET {$name}]\n";

                preg_match_all('/<c[^>]*?(?:t="s")?[^>]*>.*?<v>(.*?)<\/v>.*?<\/c>/s', $xml, $cells);

                foreach ($cells[1] ?? [] as $v) {
                    $v = trim((string)$v);
                    if ($v === '') continue;

                    if (ctype_digit($v) && isset($shared[(int)$v])) {
                        $text .= $shared[(int)$v] . " ";
                    } else {
                        $text .= $v . " ";
                    }
                }

                $text .= "\n\n";
            }

            return $text;
        } finally {
            $zip->close();
        }
    }

    // =======================
    // Image OCR (Tesseract)
    // =======================

    private function fromImageOcr(string $path): string
    {
        $txt = $this->runCommand(
            [
                'tesseract',
                $path,
                'stdout',
                '-l', $this->tesseractLang,
                '--oem', '1',
                '--psm', '6'
            ],
            allowFailure: false
        ) ?? '';

        $txt = trim($txt);
        if ($txt === '') {
            throw new \RuntimeException('OCR échoué (résultat vide).');
        }

        return "[IMAGE_OCR]\n" . $txt;
    }

    // =======================
    // Process runner
    // =======================

    private function runCommand(array $command, bool $allowFailure = false): ?string
    {
        $process = new Process($command);
        $process->setTimeout($this->processTimeout);
        $process->run();

        if (!$process->isSuccessful()) {
            if ($allowFailure) {
                return null;
            }

            $cmd = implode(' ', array_map(static fn($p) => (string)$p, $command));
            $err = trim($process->getErrorOutput());
            $out = trim($process->getOutput());
            $code = $process->getExitCode();
            $codeText = $process->getExitCodeText();

            throw new \RuntimeException(
                "Commande échouée: {$cmd}\n" .
                "ExitCode: " . ($code === null ? 'null' : (string)$code) . " ({$codeText})\n" .
                "STDERR: " . ($err !== '' ? $err : '(empty)') . "\n" .
                "STDOUT: " . ($out !== '' ? mb_substr($out, 0, 500) : '(empty)')
            );
        }

        return $process->getOutput();
    }

    private function removeDirRecursive(string $dir): void
    {
        if (!is_dir($dir)) return;

        $items = scandir($dir);
        if ($items === false) return;

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->removeDirRecursive($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }

    private function normalize(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim((string)$text);
    }
}
