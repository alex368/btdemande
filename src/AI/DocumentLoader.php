<?php

namespace App\AI;

use Symfony\AI\Store\Document\TextDocument;
use Symfony\AI\Store\Document\Metadata;
use Symfony\Component\Uid\Uuid;

// Librairies externes à installer via Composer
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as ExcelIOFactory;
use PhpOffice\PhpPresentation\IOFactory as PowerPointIOFactory;

class DocumentLoader
{
    public function load(string $directory): array
    {
        $documents = [];
        $logPath = __DIR__ . '/../../var/log/loader-debug.txt';
        file_put_contents($logPath, "=== Loader Debug Log ===\n");

        foreach (scandir($directory) as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }

            $filePath = $directory . '/' . $file;
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $content = '';

            try {
                switch ($extension) {
                    case 'pdf':
                        $parser = new PdfParser();
                        $pdf = $parser->parseFile($filePath);
                        $content = $pdf->getText();
                        break;

                    case 'docx':
                        $phpWord = WordIOFactory::load($filePath);
                        foreach ($phpWord->getSections() as $section) {
                            foreach ($section->getElements() as $element) {
                                if (method_exists($element, 'getText')) {
                                    $content .= $element->getText() . "\n";
                                }
                            }
                        }
                        break;

                    case 'xlsx':
                        $spreadsheet = ExcelIOFactory::load($filePath);
                        foreach ($spreadsheet->getAllSheets() as $sheet) {
                            foreach ($sheet->getRowIterator() as $row) {
                                foreach ($row->getCellIterator() as $cell) {
                                    $content .= $cell->getValue() . " ";
                                }
                                $content .= "\n";
                            }
                        }
                        break;

                    case 'pptx':
                        $presentation = PowerPointIOFactory::load($filePath);
                        foreach ($presentation->getAllSlides() as $slide) {
                            foreach ($slide->getShapeCollection() as $shape) {
                                if ($shape instanceof \PhpOffice\PhpPresentation\Shape\RichText) {
                                    $content .= $shape->getPlainText() . "\n";
                                }
                            }
                        }
                        break;

                    default:
                        continue 2; // Fichier non supporté
                }

                // Nettoyage du texte
                $content = html_entity_decode($content);            // Décodage des entités HTML
                $content = preg_replace('/\s+/', ' ', $content);    // Normalisation des espaces
                $content = trim($content);                          // Trim final

                // Ajout du document si non vide
                if ($content !== '') {
                    $documents[] = new TextDocument(
                        id: Uuid::v4(),
                        content: $content,
                        metadata: new Metadata([
                            'filename' => $file,
                            'type' => $extension,
                        ])
                    );

                    file_put_contents($logPath, "=== {$file} ===\n{$content}\n\n", FILE_APPEND);
                } else {
                    file_put_contents($logPath, "⚠️ Aucun contenu lisible pour : {$file}\n", FILE_APPEND);
                }

            } catch (\Throwable $e) {
                file_put_contents($logPath, "❌ Erreur lecture {$file} : " . $e->getMessage() . "\n", FILE_APPEND);
                continue;
            }
        }

        return $documents;
    }
}
