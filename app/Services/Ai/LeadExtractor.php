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
        $evidence = $this->classifier->evidence($text);
        if ($evidence['sector'] !== 'guidance') {
            $data['detected_sector'] = $evidence['sector'];
            $data['routing_confidence'] = $evidence['confidence'];
        }
        $data['raw_need'] = trim(implode("\n", array_filter([$data['raw_need'] ?? '', $message])));
        $data['last_user_intent'] = trim($message);

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
        if (preg_match('/giga|sim|rete mobile|dati mobil/u', $text)) {
            $data['service_kind'] = 'mobile_data';
        }
        if (preg_match('/non (?:mi )?va (?:il )?(?:cell|telefono)|telefono non va|cellulare non va/u', $text)) {
            $data['symptoms']['mobile_not_working'] = true;
        }
        if (preg_match('/finit[oi] i giga|giga finit|non so se ho finit/u', $text)) {
            $data['symptoms']['data_allowance_uncertain'] = true;
        }
        if (preg_match('/cambiar(?:e|ei) offerta|offerta nuova|nuova offerta/u', $text)) {
            $data['request_type'] = 'change_offer';
        }
        if (preg_match('/linea (?:internet )?nuova|nuova linea|attivare (?:una )?linea/u', $text)) {
            $data['request_type'] = 'new_line';
        }
        if (preg_match('/gioco|giocare|gaming|ping|lag|\bms\b|partite online/u', $text)) {
            $data['usage_context']['gaming'] = true;
            $data['pain_points']['stabilita_ping'] = true;
        }
        if (preg_match('/chi ti ha detto che gioco|non (?:ho detto|gioco)|niente gaming/u', $text)) {
            unset($data['usage_context']['gaming'], $data['pain_points']['stabilita_ping']);
        }
        if (preg_match('/lent[aoe]|si blocca|instabil/u', $text)) {
            $data['pain_points']['lentezza'] = true;
        }
        if (preg_match('/mi sta bloccando|non riesco a lavorare|urgent|subito|oggi|bloccat|mi serve subito/u', $text)) {
            $data['urgency'] = 'alta';
        }
        if (preg_match('/azienda|attivit[àa]|ufficio|negozio|piva|partita iva/u', $text)) {
            $data['customer_type'] = 'business';
        }
        if (preg_match('/chiamatemi|richiamatemi|sentiamoci/u', $text)) {
            $data['callback_requested'] = true;
        }
        if (preg_match('/whatsapp|posso scriverti|scrivo io/u', $text)) {
            $data['handoff_requested'] = true;
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
            $data['trigger'] = 'voltura_subentro';
        } elseif (preg_match('/mi hanno chiamat|telefonat|devo cambiare/u', $text)) {
            $data['trigger'] = 'chiamata_commerciale';
        } elseif (preg_match('/proposta scritta|preventivo|offerta scritta/u', $text)) {
            $data['trigger'] = 'proposta_scritta';
        } elseif (preg_match('/aument|pago troppo|spendo troppo|risparmi/u', $text)) {
            $data['trigger'] = 'costo_alto';
        }
    }

    private function summarize(array $data): string
    {
        if (($data['detected_sector'] ?? '') === 'tlc') {
            if (($data['service_kind'] ?? '') === 'mobile_data') {
                return 'Verifica linea mobile o traffico dati' . (($data['request_type'] ?? '') === 'change_offer' ? ' e possibile cambio offerta' : '');
            }
            return ($data['request_type'] ?? '') === 'new_line' ? 'Richiesta nuova linea internet' : 'Verifica connessione internet';
        }
        if (($data['detected_sector'] ?? '') === 'energia_amministrativo') {
            return ($data['customer_type'] ?? '') === 'business' ? 'Verifica costi energia aziendali' : 'Verifica energia o pratica amministrativa';
        }
        if (($data['detected_sector'] ?? '') === 'informatica') {
            return 'Assistenza tecnica dispositivo';
        }

        return mb_substr(trim((string)($data['raw_need'] ?? 'Richiesta da approfondire')), 0, 240, 'UTF-8');
    }

    private function facts(array $data): array
    {
        return array_filter([
            'operator' => $data['operator'] ?? null,
            'access_type' => $data['access_type'] ?? null,
            'service_kind' => $data['service_kind'] ?? null,
            'request_type' => $data['request_type'] ?? null,
            'symptoms' => $data['symptoms'] ?? null,
            'usage_context' => $data['usage_context'] ?? null,
            'pain_points' => $data['pain_points'] ?? null,
            'urgency' => $data['urgency'] ?? null,
            'phone' => $data['phone'] ?? null,
            'customer_type' => $data['customer_type'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
