<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class NeedClassifier
{
    public function classify(string $text): string
    {
        return $this->evidence($text)['sector'];
    }

    public function evidence(string $text): array
    {
        $text = mb_strtolower($text, 'UTF-8');
        $scores = ['tlc' => 0, 'informatica' => 0, 'energia_amministrativo' => 0];

        $this->score($scores, 'tlc', $text, [
            5 => ['giga', 'sim', 'portabil', 'fibra', 'fwa', 'copertura', 'offerta mobile', 'linea internet', 'rete mobile'],
            4 => ['connessione', 'internet', 'modem', 'router', 'vodafone', 'windtre', 'wind tre', 'fastweb', 'tim', 'iliad', 'sky wifi', 'eolo', 'linkem', 'opnet'],
            2 => ['ping', 'lag', 'mobile', 'telefonia'],
        ]);
        $this->score($scores, 'informatica', $text, [
            5 => ['schermo rotto', 'telefono rotto', 'non si accende', 'virus', 'recupero dati', 'backup', 'teleassistenza', 'ripar'],
            3 => ['pc', 'computer', 'notebook', 'stampante', 'configur', 'smartphone', 'cellulare'],
        ]);
        $this->score($scores, 'energia_amministrativo', $text, [
            5 => ['luce', 'gas', 'bolletta', 'voltura', 'subentro', 'pratica', 'energia', 'fornitore'],
            3 => ['spendo troppo', 'costi alti', 'costo alto', 'aumento'],
        ]);

        arsort($scores);
        $sector = (string)array_key_first($scores);
        $top = (int)$scores[$sector];
        $runnerUp = (int)(array_values($scores)[1] ?? 0);

        return [
            'sector' => $top >= 3 && $top > $runnerUp ? $sector : 'guidance',
            'confidence' => $top === 0 ? 0 : min(100, 45 + ($top * 8) - ($runnerUp * 4)),
            'scores' => $scores,
        ];
    }

    private function score(array &$scores, string $sector, string $text, array $weightedKeywords): void
    {
        foreach ($weightedKeywords as $weight => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    $scores[$sector] += $weight;
                }
            }
        }
    }
}
