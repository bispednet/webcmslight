<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class NeedClassifier
{
    public function classify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        foreach ([
            'tlc' => ['fibra', 'internet', 'sim', 'mobile', 'telefono', 'copertura', 'modem', 'operatore'],
            'energia_amministrativo' => ['luce', 'gas', 'bolletta', 'voltura', 'subentro', 'energia', 'fornitore'],
            'informatica' => ['pc', 'computer', 'notebook', 'gaming', 'smartphone', 'wifi', 'virus', 'backup', 'ripar'],
            'business' => ['azienda', 'ufficio', 'business', 'partita iva', 'negozio'],
        ] as $sector => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return $sector;
                }
            }
        }

        return 'guidance';
    }
}
