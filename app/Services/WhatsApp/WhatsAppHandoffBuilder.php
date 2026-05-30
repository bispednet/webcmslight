<?php
declare(strict_types=1);

namespace App\Services\WhatsApp;

final class WhatsAppHandoffBuilder
{
    public function build(string $number, array $conversation, array $quotes): array
    {
        $data = json_decode((string)($conversation['structured_data'] ?? '{}'), true) ?: [];
        if (($conversation['main_sector'] ?? '') === 'energia_amministrativo') {
            return $this->buildSarAI($number, $conversation, $quotes, $data);
        }
        $lines = [
            'Ciao bisp&d, ho completato il percorso Team AI Bisped.',
            '',
            'Settore: ' . ($conversation['main_sector'] ?: 'da verificare'),
            'Cliente: ' . ($conversation['customer_name'] ?: 'non indicato'),
            'Urgenza: ' . ($conversation['urgency'] ?: 'non indicata'),
            'Esigenza: ' . ($data['need'] ?? 'da approfondire'),
            '',
            'Percorsi valutati:',
        ];
        foreach ($quotes as $quote) {
            $lines[] = '- ' . $quote['title'] . ': ' . $quote['summary'];
        }
        $lines[] = '';
        $lines[] = 'Vorrei continuare con una verifica umana.';
        $text = implode("\n", $lines);

        return ['summary' => $text, 'url' => 'https://wa.me/' . preg_replace('/\D+/', '', $number) . '?text=' . rawurlencode($text)];
    }

    private function buildSarAI(string $number, array $conversation, array $quotes, array $data): array
    {
        $devices = [];
        foreach (['has_air_conditioning' => 'climatizzatori', 'has_heat_pump' => 'pompa di calore', 'has_induction' => 'induzione', 'has_ev' => 'auto elettrica', 'has_smart_home' => 'casa smart'] as $field => $label) {
            if (!empty($data[$field])) {
                $devices[] = $label;
            }
        }
        $cost = isset($data['current_cost_amount'])
            ? $data['current_cost_amount'] . ' euro / ' . ($data['current_cost_period'] ?? 'periodo da verificare')
            : 'non ricordato dal cliente';
        $lines = [
            'Ciao Bisped, arrivo dal sito con riepilogo SarAI.',
            '',
            'Motivo: ' . ($data['trigger'] ?? 'da approfondire'),
            'Luce/Gas/Pratica: ' . ($data['commodity'] ?? 'da verificare'),
            'Abitazione: ' . ($data['home_type'] ?? 'non indicata') . ' - ' . ($data['family_size'] ?? '?') . ' persone',
            'Dispositivi rilevanti: ' . ($devices ? implode(', ', $devices) : 'nessuno segnalato'),
            'Costo attuale: ' . $cost,
            'Proposta ricevuta: ' . ($data['offer_type'] ?? 'da verificare'),
            'Obiettivo: ' . ($data['primary_goal'] ?? 'da approfondire'),
            'Rischi da controllare: ' . (!empty($data['risk_flags']) ? implode(', ', $data['risk_flags']) : 'nessuno segnalato'),
            '',
            '3 strade:',
        ];
        foreach ($quotes as $index => $quote) {
            $lines[] = ($index + 1) . '. ' . $quote['title'] . ': ' . $quote['summary'];
        }
        $conditions = array_values(array_unique(array_filter(array_map(
            static fn (array $quote): ?string => $quote['condition'] ?? $quote['special_condition'] ?? null,
            $quotes
        ))));
        if ($conditions) {
            $lines[] = '';
            $lines[] = 'Condizione Bisped: ' . $conditions[0];
        }
        $lines[] = '';
        $lines[] = 'Vorrei continuare da qui senza ricominciare da zero.';
        $text = mb_substr(implode("\n", $lines), 0, 1800, 'UTF-8');

        return ['summary' => $text, 'url' => 'https://wa.me/' . preg_replace('/\D+/', '', $number) . '?text=' . rawurlencode($text)];
    }
}
