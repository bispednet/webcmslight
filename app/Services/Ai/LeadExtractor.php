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
        $text = mb_strtolower($message, 'UTF-8');
        $data = $known;
        $sector = $this->classifier->classify($text);
        if ($sector !== 'guidance') {
            $data['detected_sector'] = $sector;
        }
        $data['need'] = trim(($data['need'] ?? '') . ' ' . $message);

        if (preg_match('/\b(luce|gas|energia|bollett)/u', $text)) {
            $data['commodity'] = str_contains($text, 'gas') && str_contains($text, 'luce') ? 'luce_gas' : (str_contains($text, 'gas') ? 'gas' : 'luce');
        }
        if (preg_match('/voltura|subentro|pratica/u', $text)) {
            $data['commodity'] = 'pratica';
            $data['trigger'] = 'voltura_subentro';
        } elseif (preg_match('/chiamat|telefon|devo cambiare|subito/u', $text)) {
            $data['trigger'] = 'chiamata_commerciale';
            $data['has_competing_offer'] = true;
            $data['offer_type'] = 'telefonica';
            $data['risk_flags'] = array_values(array_unique(array_merge((array)($data['risk_flags'] ?? []), ['solo_chiamata', 'nessuna_proposta_scritta'])));
        } elseif (preg_match('/proposta scritta|preventivo|offerta scritta/u', $text)) {
            $data['trigger'] = 'proposta_scritta';
            $data['has_competing_offer'] = true;
            $data['offer_type'] = 'scritta';
            $data['offer_has_written_terms'] = true;
        } elseif (preg_match('/aument|pago troppo|risparmi/u', $text)) {
            $data['trigger'] = 'aumento_bolletta';
        }

        if (preg_match('/\b(appartamento|villetta|ufficio|negozio|attivit[àa])\b/u', $text, $match)) {
            $data['home_type'] = $match[1];
        }
        if (preg_match('/(?:siamo|viviamo|famiglia(?: di)?|in casa siamo)\s+(?:in\s+)?(\d{1,2})/u', $text, $match)) {
            $data['family_size'] = (int)$match[1];
        }
        foreach (['has_air_conditioning' => 'climatizz|condizion', 'has_heat_pump' => 'pompa di calore', 'has_induction' => 'induzion', 'has_ev' => 'auto elettrica', 'has_smart_home' => 'casa smart|domotic'] as $field => $pattern) {
            if (preg_match('/' . $pattern . '/u', $text)) {
                $data[$field] = true;
                $data['devices_profile_declared'] = true;
            }
        }
        if (preg_match('/(?:pago|spendo|bolletta(?: da| di)?|circa)\s*(?:€\s*)?(\d+(?:[.,]\d{1,2})?)/u', $text, $match)) {
            $data['current_cost_amount'] = (float)str_replace(',', '.', $match[1]);
            $data['current_cost_period'] = preg_match('/(?:due mesi|bimestr)/u', $text) ? 'bimestre' : (preg_match('/trimestr|tre mesi/u', $text) ? 'trimestre' : 'mese');
        } elseif (preg_match('/non (?:lo )?so|non ricordo/u', $text)) {
            $data['current_cost_unknown_declared'] = true;
        }
        if (preg_match('/nessuna proposta|non mi hanno (?:fatto|chiamato)|non ho (?:una )?proposta/u', $text)) {
            $data['has_competing_offer'] = false;
        }
        if (preg_match('/pagare meno|risparmi/u', $text)) {
            $data['primary_goal'] = 'risparmio';
        } elseif (preg_match('/seria|freg|controll|capire/u', $text)) {
            $data['primary_goal'] = 'verifica_proposta';
        } elseif (preg_match('/stabilizz/u', $text)) {
            $data['primary_goal'] = 'stabilita';
        }

        return $data;
    }
}
