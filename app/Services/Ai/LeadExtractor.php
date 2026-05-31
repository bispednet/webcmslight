<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class LeadExtractor
{
    public function __construct(private NeedClassifier $classifier)
    {
    }

    public function extract(string $message, array $known = []): array
    {
        $text = mb_strtolower(trim($message), 'UTF-8');
        $data = $known;
        $sector = $this->classifier->classify($text);
        if ($sector !== 'guidance') {
            $data['detected_sector'] = $sector;
        }
        $data['raw_need'] = trim(implode("\n", array_filter([$data['raw_need'] ?? '', $message])));
        $data['last_user_intent'] = $message;

        if (preg_match('/(?:\+39[\s.-]*)?(3\d{2}[\s.-]?\d{3}[\s.-]?\d{3,4})/u', $text, $match)) {
            $data['phone'] = preg_replace('/\D+/', '', $match[1]);
        }
        if (preg_match('/[\w.+-]+@[\w.-]+\.[a-z]{2,}/ui', $message, $match)) {
            $data['email'] = strtolower($match[0]);
        }
        foreach (['vodafone' => 'Vodafone', 'windtre' => 'WindTre', 'wind tre' => 'WindTre', 'w3' => 'WindTre', 'fastweb' => 'Fastweb', 'tim' => 'TIM', 'iliad' => 'Iliad', 'sky wifi' => 'Sky Wifi', 'eolo' => 'Eolo', 'linkem' => 'Linkem', 'opnet' => 'OpNet'] as $needle => $label) {
            if (str_contains($text, $needle)) {
                $data['operator'] = $label;
                break;
            }
        }
        if (preg_match('/\b(fwa|wireless|radio)\b/u', $text)) {
            $data['access_type'] = 'FWA';
        } elseif (preg_match('/\bfibra\b/u', $text)) {
            $data['access_type'] = 'fibra';
        }
        if (preg_match('/gioco|giocare|gaming|ping|lag|\bms\b|partite online/u', $text)) {
            $data['usage_context']['gaming'] = true;
            $data['pain_points']['stabilita_ping'] = true;
        }
        if (preg_match('/lent[aoe]|velocizz|si blocca|instabil/u', $text)) {
            $data['pain_points']['lentezza'] = true;
        }
        if (preg_match('/mi sta bloccando|non riesco a lavorare|urgent|subito|oggi|bloccat/u', $text)) {
            $data['urgency'] = 'alta';
        }
        if (preg_match('/azienda|attivit[àa]|ufficio|negozio|piva|partita iva/u', $text)) {
            $data['customer_type'] = 'business';
        }
        if (preg_match('/chiamatemi|richiamatemi|sentiamoci|whatsapp/u', $text)) {
            $data['callback_requested'] = true;
        }
        if (preg_match('/(?:sono|abito|zona|comune)(?:\s+(?:a|di|in))?\s+([a-zà-ÿ][a-zà-ÿ\\s]{2,40})/ui', $text, $match)) {
            $data['location_hint'] = trim($match[1]);
        }

        $this->extractEnergy($text, $data);
        $data['need_summary'] = $this->summarize($data);
        $data['facts'] = $this->facts($data);

        return $data;
    }

    private function extractEnergy(string $text, array &$data): void
    {
        if (preg_match('/\b(luce|gas|energia|bollett)/u', $text)) {
            $data['commodity'] = str_contains($text, 'gas') && str_contains($text, 'luce') ? 'luce_gas' : (str_contains($text, 'gas') ? 'gas' : 'luce');
        }
        if (preg_match('/voltura|subentro|pratica/u', $text)) {
            $data['commodity'] = 'pratica';
            $data['trigger'] = 'voltura_subentro';
        } elseif (preg_match('/mi hanno chiamat|telefonat|devo cambiare/u', $text)) {
            $data['trigger'] = 'chiamata_commerciale';
            $data['has_competing_offer'] = true;
            $data['offer_type'] = 'telefonica';
        } elseif (preg_match('/proposta scritta|preventivo|offerta scritta/u', $text)) {
            $data['trigger'] = 'proposta_scritta';
            $data['has_competing_offer'] = true;
            $data['offer_type'] = 'scritta';
        } elseif (preg_match('/aument|pago troppo|spendo troppo|risparmi/u', $text)) {
            $data['trigger'] = 'costo_alto';
        }
        if (preg_match('/\b(appartamento|villetta|ufficio|negozio|attivit[àa]|azienda)\b/u', $text, $match)) {
            $data['home_type'] = $match[1];
        }
        if (preg_match('/(?:siamo|viviamo|famiglia(?: di)?|in casa siamo)\s+(?:in\s+)?(\d{1,2})/u', $text, $match)) {
            $data['family_size'] = (int)$match[1];
        }
        foreach (['has_air_conditioning' => 'climatizz|condizion', 'has_heat_pump' => 'pompa di calore', 'has_induction' => 'induzion', 'has_ev' => 'auto elettrica'] as $field => $pattern) {
            if (preg_match('/' . $pattern . '/u', $text)) {
                $data[$field] = true;
            }
        }
        if (preg_match('/(?:pago|spendo|bolletta(?: da| di)?|circa)\s*(?:€\s*)?(\d+(?:[.,]\d{1,2})?)/u', $text, $match)) {
            $data['current_cost_amount'] = (float)str_replace(',', '.', $match[1]);
        }
    }

    private function summarize(array $data): string
    {
        if (($data['detected_sector'] ?? '') === 'tlc') {
            $usage = !empty($data['usage_context']['gaming']) ? ' per gaming online' : '';
            return 'Connessione da verificare' . $usage . (!empty($data['pain_points']['lentezza']) ? ': velocità o stabilità insufficienti' : '');
        }
        if (($data['detected_sector'] ?? '') === 'energia_amministrativo') {
            return ($data['customer_type'] ?? '') === 'business' ? 'Costi aziendali da verificare' : 'Situazione energia o pratica da verificare';
        }

        return mb_substr(trim((string)($data['raw_need'] ?? 'Richiesta da approfondire')), 0, 240, 'UTF-8');
    }

    private function facts(array $data): array
    {
        return array_filter([
            'operator' => $data['operator'] ?? null, 'access_type' => $data['access_type'] ?? null,
            'usage_context' => $data['usage_context'] ?? null, 'pain_points' => $data['pain_points'] ?? null,
            'urgency' => $data['urgency'] ?? null, 'phone' => $data['phone'] ?? null,
            'customer_type' => $data['customer_type'] ?? null, 'location_hint' => $data['location_hint'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
