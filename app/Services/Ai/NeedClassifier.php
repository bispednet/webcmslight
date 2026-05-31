<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class NeedClassifier
{
    public function classify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        foreach ([
            'tlc' => ['connessione', 'internet', 'lenta', 'velocizz', 'fibra', 'fwa', 'ping', 'lag', 'gaming', 'gioco', 'giocare', 'partite online', 'vodafone', 'windtre', 'wind tre', 'w3', 'fastweb', 'tim', 'iliad', 'sky wifi', 'eolo', 'linkem', 'opnet', 'modem', 'router', 'sim', 'mobile', 'copertura'],
            'energia_amministrativo' => ['luce', 'gas', 'bolletta', 'voltura', 'subentro', 'energia', 'fornitore'],
            'informatica' => ['pc', 'computer', 'notebook', 'smartphone', 'telefono rotto', 'schermo rotto', 'virus', 'backup', 'recupero dati', 'stampante', 'configur', 'teleassistenza', 'ripar', 'non si accende'],
        ] as $sector => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return $sector;
                }
            }
        }
        if (preg_match('/spendo troppo|costi? (?:troppo )?alt[oi]|aument/u', $text)) {
            return 'energia_amministrativo';
        }

        return 'guidance';
    }
}
