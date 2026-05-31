<?php
declare(strict_types=1);

namespace App\Services\WhatsApp;

final class WhatsAppHandoffBuilder
{
    public function build(string $number, array $conversation, array $quotes): array
    {
        $data = json_decode((string)($conversation['structured_data'] ?? '{}'), true) ?: [];
        $sector = (string)($conversation['main_sector'] ?? $data['main_sector'] ?? '');

        $lines = $this->buildLines($sector, $conversation, $data);
        $text = mb_substr(implode("\n", $lines), 0, 1600, 'UTF-8');

        return [
            'summary' => $text,
            'url' => 'https://wa.me/' . preg_replace('/\D+/', '', $number) . '?text=' . rawurlencode($text),
        ];
    }

    private function buildLines(string $sector, array $conversation, array $data): array
    {
        // Merge top-level + nested facts so we can access device_type etc. from either location
        $facts = array_merge($data, (array)($data['facts'] ?? []));
        $data  = $facts;
        $lines = ['Ciao Bisped, arrivo dal sito.', ''];

        // Need summary — concise, no generic fallback
        $need = $this->resolveNeedSummary($sector, $data, $conversation);
        if ($need) {
            $lines[] = 'Richiesta: ' . $need;
        }

        // TLC details
        if ($sector === 'tlc') {
            foreach (array_filter([
                !empty($data['operator']) ? 'Operatore attuale: ' . $data['operator'] : null,
                !empty($data['access_type']) ? 'Tecnologia: ' . $data['access_type'] : null,
                !empty($data['usage_context']['gaming']) ? 'Uso: gaming online' : null,
                !empty($data['pain_points']['lentezza']) ? 'Problema: lentezza/blocchi' : null,
                !empty($data['pain_points']['stabilita_ping']) ? 'Priorità: stabilità e ping' : null,
                ($data['service_kind'] ?? '') === 'mobile_data' ? 'Ambito: linea mobile' : null,
                ($data['request_type'] ?? '') === 'new_line' ? 'Obiettivo: nuova linea internet' : null,
            ]) as $line) {
                $lines[] = $line;
            }
        }

        // Energy details
        if ($sector === 'energia_amministrativo') {
            foreach (array_filter([
                !empty($data['commodity']) ? 'Ambito: ' . $data['commodity'] : null,
                !empty($data['trigger']) ? 'Motivo: ' . $this->triggerLabel($data['trigger']) : null,
            ]) as $line) {
                $lines[] = $line;
            }
        }

        // IT details
        if ($sector === 'informatica') {
            foreach (array_filter([
                !empty($data['device_type']) ? '- Dispositivo cercato: ' . $this->deviceTypeLabel($data['device_type'], $data['device_brand'] ?? null) : null,
                !empty($data['device_brand']) && empty($data['device_type']) ? '- Brand preferito: ' . $data['device_brand'] : null,
                !empty($data['it_request']) ? '- Tipo richiesta: ' . $this->itRequestLabel($data['it_request']) : null,
                !empty($data['budget_eur']) ? '- Budget dichiarato: ' . $data['budget_eur'] . '€' : null,
                !empty($data['purchase_method']) ? '- Preferenza acquisto: ' . ($data['purchase_method'] === 'with_operator' ? 'con operatore' : 'diretto') : null,
                !empty($data['trade_in']) ? '- Interesse permuta vecchio dispositivo' : null,
                !empty($data['use_case']) ? '- Uso principale: ' . implode(', ', array_keys(array_filter((array)$data['use_case']))) : null,
                !empty($data['gpu_request']) ? '- Cerca scheda video / upgrade GPU' : null,
                !empty($data['pc_brand']) ? '- Brand PC/notebook: ' . $data['pc_brand'] : null,
            ]) as $line) {
                $lines[] = $line;
            }
        }

        // Urgency
        $urgency = $conversation['urgency'] ?? $data['urgency'] ?? null;
        if ($urgency === 'alta') {
            $lines[] = 'Urgenza: alta';
        }

        // Customer type
        $customerType = $conversation['customer_type'] ?? $data['customer_type'] ?? null;
        if ($customerType && $customerType !== 'non_definito') {
            $lines[] = 'Tipo: ' . $customerType;
        }

        // Phone if provided
        $phone = $conversation['customer_phone'] ?? $data['phone'] ?? null;
        if ($phone) {
            $lines[] = 'Telefono: ' . $phone;
        }

        $lines[] = '';
        $lines[] = 'Vorrei continuare da qui senza rispiegare tutto.';

        return $lines;
    }

    private function resolveNeedSummary(string $sector, array $data, array $conversation): string
    {
        // Use memory need_summary if it's specific enough and not a bare phone number
        $summary = trim((string)($data['need_summary'] ?? ''));
        $isPhoneOnly = $summary !== '' && preg_match('/^[\d\s.\-+]{7,15}$/', $summary);
        if ($summary !== '' && !$isPhoneOnly && $summary !== 'Richiesta da approfondire' && $summary !== 'Da approfondire') {
            return $summary;
        }
        // Fallback to raw_need if available
        $rawNeed = trim((string)($data['raw_need'] ?? ''));
        if ($rawNeed !== '' && !preg_match('/^[\d\s.\-+]{7,15}$/', $rawNeed)) {
            return mb_substr($rawNeed, 0, 160, 'UTF-8');
        }

        // Build from facts
        if ($sector === 'tlc') {
            $parts = [];
            if (!empty($data['operator'])) {
                $parts[] = $data['operator'];
            }
            if (!empty($data['access_type'])) {
                $parts[] = $data['access_type'];
            }
            if (!empty($data['usage_context']['gaming'])) {
                $parts[] = 'gaming';
            }
            if (!empty($data['pain_points']['lentezza'])) {
                $parts[] = 'connessione lenta';
            }
            if ($parts) {
                return 'Connessione ' . implode(', ', $parts);
            }

            return 'Problema connessione internet';
        }

        if ($sector === 'energia_amministrativo') {
            return ($conversation['customer_type'] ?? '') === 'business'
                ? 'Costi energia aziendali da verificare'
                : 'Verifica fornitura energia';
        }

        if ($sector === 'informatica') {
            return 'Assistenza tecnica dispositivo';
        }

        return trim((string)($data['raw_need'] ?? 'Richiesta da verificare'));
    }

    private function deviceTypeLabel(string $type, ?string $brand): string
    {
        $label = match ($type) {
            'foldable'      => 'Smartphone foldable (Samsung Z Fold)',
            'foldable_flip' => 'Smartphone foldable flip (Samsung Z Flip)',
            default         => 'Smartphone/dispositivo',
        };
        if ($brand && !str_contains($label, $brand)) {
            $label .= " — {$brand}";
        }

        return $label;
    }

    private function itRequestLabel(string $req): string
    {
        return match ($req) {
            'new_device'        => 'acquisto nuovo smartphone/tablet',
            'upgrade_component' => 'upgrade componente PC',
            'repair'            => 'riparazione dispositivo',
            'new_pc'            => 'acquisto nuovo PC/notebook',
            default             => $req,
        };
    }

    private function triggerLabel(string $trigger): string
    {
        return match ($trigger) {
            'costo_alto' => 'costi troppo alti',
            'voltura_subentro' => 'voltura o subentro',
            'chiamata_commerciale' => 'chiamata commerciale ricevuta',
            'proposta_scritta' => 'proposta scritta ricevuta',
            default => $trigger,
        };
    }
}
