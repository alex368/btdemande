<?php

namespace App\Service;

use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Link;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpPresentation\IOFactory as PresentationIOFactory;
use PhpOffice\PhpPresentation\Shape\RichText;

class DocumentTextExtractor
{
    public function extract(string $absolutePath): string
    {
        if (!is_file($absolutePath)) {
            throw new \RuntimeException("Fichier introuvable : $absolutePath");
        }

        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf'   => $this->fromPdf($absolutePath),
            'docx'  => $this->fromWord($absolutePath),
            'xlsx',
            'xls',
            'csv'   => $this->fromSpreadsheet($absolutePath),
            'pptx'  => $this->fromPresentation($absolutePath),
            'txt'   => $this->normalize(file_get_contents($absolutePath)),
            'jpg',
            'jpeg',
            'png',
            'webp'  => $this->fromImage($absolutePath),
            default => throw new \RuntimeException("Format non supporté : .$extension"),
        };
    }

    /* =======================
       PDF
       ======================= */

   private function fromPdf(string $path): string
{
    $parser = new PdfParser();
    $pdf = $parser->parseFile($path);

    $text = trim($pdf->getText());

    // Si le texte est trop court → PDF probablement scanné
    if (mb_strlen($text) < 500) {
        $ocrText = $this->ocrPdf($path);
        $text .= "\n" . $ocrText;
    }

    return $this->normalize($text);
}

private function ocrPdf(string $path): string
{
    // Utilise pdftoppm + tesseract (standard prod)
    $tmpDir = sys_get_temp_dir() . '/pdf_ocr_' . uniqid();
    mkdir($tmpDir);

    // Convertit chaque page en image
    $cmdImages = sprintf(
        'pdftoppm -png %s %s/page',
        escapeshellarg($path),
        escapeshellarg($tmpDir)
    );
    shell_exec($cmdImages);

    $text = '';

    foreach (glob($tmpDir . '/*.png') as $image) {
        $cmdOcr = sprintf(
            'tesseract %s stdout 2>/dev/null',
            escapeshellarg($image)
        );
        $text .= shell_exec($cmdOcr) . "\n";
        unlink($image);
    }

    rmdir($tmpDir);

    return $text;
}



    /* =======================
       WORD (.docx)
       ======================= */

    private function fromWord(string $path): string
    {
        $phpWord = WordIOFactory::load($path);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {

                // Texte simple
                if ($element instanceof Text) {
                    $text .= $element->getText() . "\n";
                }

                // Groupe de textes (paragraphes)
                if ($element instanceof TextRun) {
                    foreach ($element->getElements() as $child) {
                        if ($child instanceof Text) {
                            $text .= $child->getText() . ' ';
                        }
                        if ($child instanceof Link) {
                            $text .= $child->getText() . ' ';
                        }
                    }
                    $text .= "\n";
                }
            }
        }

        return $this->normalize($text);
    }

    /* =======================
       EXCEL / CSV
       ======================= */

    private function fromSpreadsheet(string $path): string
    {
        $spreadsheet = SpreadsheetIOFactory::load($path);
        $text = '';

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            foreach ($sheet->toArray(null, true, true, true) as $row) {
                $text .= implode(' ', array_filter($row)) . "\n";
            }
        }

        return $this->normalize($text);
    }

    /* =======================
       POWERPOINT (.pptx)
       ======================= */

    private function fromPresentation(string $path): string
    {
        $presentation = PresentationIOFactory::load($path);
        $text = '';

        foreach ($presentation->getAllSlides() as $slide) {
            foreach ($slide->getShapeCollection() as $shape) {

                if ($shape instanceof RichText) {
                    foreach ($shape->getParagraphs() as $paragraph) {
                        foreach ($paragraph->getRichTextElements() as $element) {
                            $text .= $element->getText();
                        }
                        $text .= "\n";
                    }
                }
            }
        }

        return $this->normalize($text);
    }

    /* =======================
       IMAGES (OCR)
       ======================= */

    private function fromImage(string $path): string
    {
        $cmd = sprintf('tesseract %s stdout 2>/dev/null', escapeshellarg($path));
        $output = shell_exec($cmd);

        if (!$output) {
            throw new \RuntimeException('OCR échoué ou tesseract non installé');
        }

        return $this->normalize($output);
    }

    /* =======================
       NORMALISATION
       ======================= */

    private function normalize(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}
