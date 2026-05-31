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
            '',
            'Esigenza: ' . ($data['need_summary'] ?? 'da approfondire'),
        ];
        foreach (array_filter([
            !empty($conversation['customer_name']) ? 'Cliente: ' . $conversation['customer_name'] : null,
            !empty($conversation['customer_phone']) ? 'Telefono lasciato in chat: ' . $conversation['customer_phone'] : null,
            !empty($conversation['urgency']) ? 'Urgenza dichiarata: ' . $conversation['urgency'] : null,
        ]) as $detail) {
            $lines[] = $detail;
        }
        $lines[] = '';
        $lines[] = 'Vorrei continuare con una verifica umana senza ricominciare da zero.';
        $text = implode("\n", $lines);

        return ['summary' => $text, 'url' => 'https://wa.me/' . preg_replace('/\D+/', '', $number) . '?text=' . rawurlencode($text)];
    }

    private function buildSerenAI(string $number, array $conversation, array $quotes, array $data): array
    {
        $lines = [
            'Ciao Bisped, arrivo dal sito.',
            '',
            'Settore: telefonia / connessione',
            'Agente: SerenAI',
            '',
            'Richiesta:',
            $data['need_summary'] ?? 'Richiesta TLC da verificare.',
        ];
        $details = array_filter([
            !empty($data['operator']) ? '- Operatore citato: ' . $data['operator'] : null,
            !empty($data['access_type']) ? '- Tecnologia citata: ' . $data['access_type'] : null,
            ($data['service_kind'] ?? '') === 'mobile_data' ? '- Ambito: linea mobile / traffico dati' : null,
            ($data['request_type'] ?? '') === 'change_offer' ? '- Obiettivo dichiarato: valutare cambio offerta' : null,
            ($data['request_type'] ?? '') === 'new_line' ? '- Obiettivo dichiarato: attivare una nuova linea internet' : null,
            !empty($data['symptoms']['mobile_not_working']) ? '- Segnalazione: il telefono o la linea mobile non funziona come previsto' : null,
            !empty($data['symptoms']['data_allowance_uncertain']) ? '- Segnalazione: il cliente non sa se ha terminato i giga' : null,
            !empty($data['usage_context']['gaming']) ? '- Uso dichiarato: gaming online' : null,
            !empty($data['pain_points']['lentezza']) ? '- Segnalazione: lentezza o blocchi' : null,
            !empty($data['pain_points']['stabilita_ping']) ? '- Segnalazione: lag, ping o stabilità' : null,
            ($conversation['urgency'] ?? '') === 'alta' ? '- Urgenza dichiarata: alta' : null,
            !empty($conversation['customer_phone']) ? '- Telefono lasciato in chat: ' . $conversation['customer_phone'] : null,
        ]);
        if ($details) {
            $lines[] = '';
            $lines[] = 'Dati dichiarati:';
            array_push($lines, ...$details);
        }
        $lines[] = '';
        $lines[] = 'Vorrei continuare da qui senza ricominciare da zero.';
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
        $lines = [
            'Ciao Bisped, arrivo dal sito con riepilogo SarAI.',
            '',
            'Richiesta:',
            $data['need_summary'] ?? 'Situazione energia o pratica da verificare.',
        ];
        $details = array_filter([
            !empty($conversation['customer_type']) && $conversation['customer_type'] !== 'non_definito' ? '- Tipo cliente: ' . $conversation['customer_type'] : null,
            !empty($conversation['customer_phone']) ? '- Telefono lasciato in chat: ' . $conversation['customer_phone'] : null,
            !empty($conversation['urgency']) ? '- Urgenza dichiarata: ' . $conversation['urgency'] : null,
            !empty($data['trigger']) ? '- Motivo dichiarato: ' . $data['trigger'] : null,
            !empty($data['commodity']) ? '- Ambito: ' . $data['commodity'] : null,
            !empty($data['home_type']) ? '- Abitazione o attività: ' . $data['home_type'] : null,
            !empty($data['family_size']) ? '- Persone: ' . $data['family_size'] : null,
            $devices ? '- Dispositivi rilevanti citati: ' . implode(', ', $devices) : null,
            isset($data['current_cost_amount']) ? '- Costo dichiarato: ' . $data['current_cost_amount'] . ' euro' : null,
        ]);
        if ($details) {
            $lines[] = '';
            $lines[] = 'Dati dichiarati:';
            array_push($lines, ...$details);
        }
        $lines[] = '';
        $lines[] = 'Vorrei continuare da qui senza ricominciare da zero.';
        $text = mb_substr(implode("\n", $lines), 0, 1800, 'UTF-8');

        return ['summary' => $text, 'url' => 'https://wa.me/' . preg_replace('/\D+/', '', $number) . '?text=' . rawurlencode($text)];
    }
}
