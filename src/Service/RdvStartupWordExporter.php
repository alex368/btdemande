<?php

namespace App\Service;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\ListItem;

final class RdvStartupWordExporter
{
    /**
     * @param array<string,string> $answers
     */
    public function export(array $answers, string $project, string $outputPath): string
    {
        $phpWord = new PhpWord();

        // Métadonnées
        $props = $phpWord->getDocInfo();
        $props->setCreator('Fidelissimo / RAG');
        $props->setTitle('RDV START-UP');
        $props->setDescription('Trame RDV START-UP générée via RAG strict');
        $props->setCategory('RDV START-UP');

        // Styles globaux
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        // Styles
        $phpWord->addTitleStyle(1, ['bold' => true, 'size' => 18], ['spaceAfter' => 240]);
        $phpWord->addTitleStyle(2, ['bold' => true, 'size' => 14], ['spaceBefore' => 240, 'spaceAfter' => 120]);

        $section = $phpWord->addSection([
            'marginTop' => 900,
            'marginBottom' => 900,
            'marginLeft' => 1000,
            'marginRight' => 1000,
        ]);

        // Header
        $section->addTitle('RDV START-UP', 1);
        $section->addText("Projet : {$project}", ['bold' => true]);
        $section->addTextBreak(1);

        // Helpers
        $get = static function (array $answers, string $k): string {
            $v = trim((string)($answers[$k] ?? ''));
            return $v !== '' ? $v : '—';
        };

        $addField = static function ($section, string $label, string $value): void {
            $run = $section->addTextRun(['spaceAfter' => 80]);
            $run->addText($label . ' : ', ['bold' => true]);
            $run->addText($value);
        };

        // Bloc identité
        $addField($section, 'Date', $get($answers, 'Date'));
        $addField($section, 'Nom du projet', $get($answers, 'Nom du projet'));
        $addField($section, 'Nom du porteur', $get($answers, 'Nom du porteur'));
        $addField($section, 'Coordonnées tel & mail', $get($answers, 'Coordonnées tel & mail'));
        $addField($section, 'Date de création (ou envisagée)', $get($answers, 'Date de création (ou envisagée)'));
        $addField($section, 'Lieu du siège ou de la commercialisation', $get($answers, 'Lieu du siège ou de la commercialisation'));
        $section->addTextBreak(1);

        // Sections trame
        $this->addBullets($section, 'Projet', [
            'Descriptif du projet' => $get($answers, 'Descriptif du projet'),
            'Secteur' => $get($answers, 'Secteur'),
            'Stade d’avancement' => $get($answers, 'Stade d’avancement'),
            'Equipe & Actionnariat' => $get($answers, 'Equipe & Actionnariat'),
            'Incubation ou accompagnement' => $get($answers, 'Incubation ou accompagnement'),
        ], $phpWord);

        $this->addBullets($section, 'Innovation', [
            'Type d’Innovation & domaine' => $get($answers, 'Type d’Innovation & domaine'),
            'Stade d’avancement de l’innovation' => $get($answers, 'Stade d’avancement de l’innovation'),
            'Version à venir' => $get($answers, 'Version à venir'),
            'Propriété intellectuelle' => $get($answers, 'Propriété intellectuelle'),
        ], $phpWord);

        $this->addBullets($section, 'Marché', [
            'Business model' => $get($answers, 'Business model'),
            'Prix' => $get($answers, 'Prix'),
            'Concurrence & avantage concurrentiel' => $get($answers, 'Concurrence & avantage concurrentiel'),
            'Stratégie commerciale' => $get($answers, 'Stratégie commerciale'),
            'Stratégie marketing & communication' => $get($answers, 'Stratégie marketing & communication'),
        ], $phpWord);

        $this->addBullets($section, 'Financement', [
            'Besoin de financement' => $get($answers, 'Besoin de financement'),
            'Fonds propres et capital social apportés' => $get($answers, 'Fonds propres et capital social apportés'),
            'Financement déjà obtenu' => $get($answers, 'Financement déjà obtenu'),
            'Prévision financière de CA' => $get($answers, 'Prévision financière de CA'),
        ], $phpWord);

        $this->addBullets($section, 'Compléments', [
            'Besoins généraux' => $get($answers, 'Besoins généraux'),
            'Mise en relation potentielle' => $get($answers, 'Mise en relation potentielle'),
            'Infos à noter' => $get($answers, 'Infos à noter'),
        ], $phpWord);

        // Présentation entreprise
        $section->addTextBreak(1);
        $section->addTitle('Présentation de l’entreprise', 2);

        $section->addText(
            "Accompagnement à la recherche de financement, publics et privés.\n" .
            "Spécialiste des aides et des subventions publiques.\n" .
            "Cartographie des dispositifs, montage des dossiers (subventions, bancaire, levée de fonds).\n" .
            "Stratégie d’effet de levier, veille et inscription concours/AAP.\n" .
            "Accompagnement création → développement.",
            ['size' => 11],
            ['alignment' => Jc::START, 'spaceAfter' => 160]
        );

        $section->addText("Accompagnement sous le modèle suivant :", ['bold' => true]);
        $section->addListItem(
            "Forfait de démarrage de 2000€ HT : mise en place de la stratégie + cartographie des dispositifs.",
            0,
            ['size' => 11],
            ListItem::TYPE_BULLET_FILLED
        );
        $section->addListItem(
            "Rémunération de 10% des subventions obtenues et versées sur le compte de la start-up.",
            0,
            ['size' => 11],
            ListItem::TYPE_BULLET_FILLED
        );

        // Écriture fichier
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($outputPath);

        return $outputPath;
    }

    /**
     * @param array<string,string> $items
     */
    private function addBullets($section, string $title, array $items, PhpWord $phpWord): void
    {
        $section->addTitle($title, 2);

        foreach ($items as $label => $value) {
            $text = $label . ' : ' . ($value !== '' ? $value : '—');
            $section->addListItem($text, 0, ['size' => 11], ListItem::TYPE_BULLET_FILLED);
        }

        $section->addTextBreak(1);
    }
}
