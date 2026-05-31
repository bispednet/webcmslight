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
            2 => ['ping', 'lag', 'mobile', 'telefonia', 'lentezza', 'lenta', 'lento', 'instabil'],
        ]);
        $this->score($scores, 'informatica', $text, [
            5 => ['schermo rotto', 'telefono rotto', 'non si accende', 'virus', 'recupero dati', 'backup', 'teleassistenza', 'ripar'],
            3 => ['pc', 'computer', 'notebook', 'stampante', 'configur', 'smartphone', 'cellulare'],
            2 => ['scheda video', 'fps', 'driver', 'windows', 'temperatura', 'scalda', 'surriscalda'],
        ]);
        $this->score($scores, 'energia_amministrativo', $text, [
            5 => ['luce', 'gas', 'bolletta', 'voltura', 'subentro', 'pratica', 'energia', 'fornitore'],
            3 => ['spendo troppo', 'costi alti', 'costo alto', 'aumento'],
        ]);

        // Gaming context resolution:
        // gaming + TLC signals → boost TLC (gaming is a connectivity concern first)
        // gaming + hardware signals → boost IT
        $hasGaming = preg_match('/\b(gioc|gaming|giocare|partite online)\b/u', $text) === 1;
        if ($hasGaming) {
            $hasTlcSignal = $scores['tlc'] >= 2
                || preg_match('/\b(connessione|internet|fibra|fwa|modem|ping|lag|operatore|vodafone|fastweb|tim|windtre|linea)\b/u', $text) === 1;
            $hasHardwareSignal = preg_match('/\b(pc|computer|notebook|scheda video|fps|driver|hardware|temperatura|windows)\b/u', $text) === 1;

            if ($hasTlcSignal && !$hasHardwareSignal) {
                $scores['tlc'] += 4;
            } elseif ($hasHardwareSignal && !$hasTlcSignal) {
                $scores['informatica'] += 4;
            } elseif ($hasTlcSignal && $hasHardwareSignal) {
                // Both: slight TLC bias for connectivity concern
                $scores['tlc'] += 2;
                $scores['informatica'] += 2;
            } else {
                // Gaming alone, no clear signal: slight TLC bias since connectivity is the most common concern
                $scores['tlc'] += 2;
            }
        }

        arsort($scores);
        $sector = (string)array_key_first($scores);
        $top = (int)$scores[$sector];
        $runnerUp = (int)(array_values($scores)[1] ?? 0);

        return [
            'sector' => $top >= 3 && $top > $runnerUp ? $sector : 'guidance',
            'confidence' => $top === 0 ? 0 : min(100, 45 + ($top * 8) - ($runnerUp * 4)),
            'scores' => $scores,
            'routing_reason' => $this->buildReason($sector, $top, $runnerUp, $hasGaming),
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

    private function buildReason(string $sector, int $top, int $runnerUp, bool $hasGaming): string
    {
        if ($top < 3 || $top <= $runnerUp) {
            return 'guidance: segnali ambigui o insufficienti';
        }
        $reasons = [
            'tlc' => $hasGaming ? 'gaming + segnali TLC → SerenAI' : 'segnali connettività',
            'informatica' => $hasGaming ? 'gaming + segnali hardware → AndreAI' : 'segnali dispositivo/hardware',
            'energia_amministrativo' => 'segnali energia/pratiche → SarAI',
        ];

        return $reasons[$sector] ?? $sector;
    }
}
