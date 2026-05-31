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
        if (($conversation['main_sector'] ?? '') === 'tlc') {
            return $this->buildSerenAI($number, $conversation, $quotes, $data);
        }
        $lines = [
            'Ciao Bisped, arrivo dal sito.',
            '',
            'Settore: ' . ($conversation['main_sector'] ?: 'da verificare'),
            'Agente: ' . ucfirst((string)($data['active_agent'] ?? 'da verificare')),
            'Telefono cliente: ' . ($conversation['customer_phone'] ?: 'non indicato'),
            'Cliente: ' . ($conversation['customer_name'] ?: 'non indicato'),
            'Urgenza: ' . ($conversation['urgency'] ?: 'non indicata'),
            'Esigenza: ' . ($data['need_summary'] ?? 'da approfondire'),
            '',
            'Vorrei continuare con una verifica umana senza ricominciare da zero.',
        ];
        $text = implode("\n", $lines);

        return ['summary' => $text, 'url' => 'https://wa.me/' . preg_replace('/\D+/', '', $number) . '?text=' . rawurlencode($text)];
    }

    private function buildSerenAI(string $number, array $conversation, array $quotes, array $data): array
    {
        $lines = [
            'Ciao Bisped, arrivo dal sito.',
            '',
            'Settore: internet / connessione',
            'Agente: SerenAI',
            'Telefono cliente: ' . ($conversation['customer_phone'] ?: 'non indicato'),
            'Urgenza: ' . ($conversation['urgency'] ?: 'non indicata'),
            '',
            'Richiesta:',
            $data['need_summary'] ?? 'Connessione da verificare.',
            '',
            'Dati raccolti:',
            '- Operatore attuale: ' . ($data['operator'] ?? 'non indicato'),
            '- Tecnologia: ' . ($data['access_type'] ?? 'da verificare'),
            '- Problema: ' . (!empty($data['usage_context']['gaming']) ? 'velocità/stabilità per giocare' : 'prestazioni connessione'),
            '- Nota: ' . (($conversation['urgency'] ?? '') === 'alta' ? 'il cliente dice che la situazione lo sta bloccando' : 'richiesta da approfondire'),
            '',
            'Vorrei continuare da qui senza ricominciare da zero.',
        ];
        $text = mb_substr(implode("\n", $lines), 0, 1800, 'UTF-8');

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
            'Tipo cliente: ' . ($conversation['customer_type'] ?: 'non indicato'),
            'Telefono cliente: ' . ($conversation['customer_phone'] ?: 'non indicato'),
            'Urgenza: ' . ($conversation['urgency'] ?: 'non indicata'),
            'Motivo: ' . ($data['trigger'] ?? 'da approfondire'),
            'Luce/Gas/Pratica: ' . ($data['commodity'] ?? 'da verificare'),
            'Abitazione: ' . ($data['home_type'] ?? 'non indicata') . ' - ' . ($data['family_size'] ?? '?') . ' persone',
            'Dispositivi rilevanti: ' . ($devices ? implode(', ', $devices) : 'nessuno segnalato'),
            'Costo attuale: ' . $cost,
            'Proposta ricevuta: ' . ($data['offer_type'] ?? 'da verificare'),
            'Obiettivo: ' . ($data['primary_goal'] ?? 'da approfondire'),
            'Rischi da controllare: ' . (!empty($data['risk_flags']) ? implode(', ', $data['risk_flags']) : 'nessuno segnalato'),
            '',
            'Vorrei continuare da qui senza ricominciare da zero.',
        ];
        $text = mb_substr(implode("\n", $lines), 0, 1800, 'UTF-8');

        return ['summary' => $text, 'url' => 'https://wa.me/' . preg_replace('/\D+/', '', $number) . '?text=' . rawurlencode($text)];
    }
}
