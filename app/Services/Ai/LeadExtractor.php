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

        // Detect if this message is ONLY a phone number (mobile or landline)
        // If so, we don't update need_summary or raw_need with it
        $isPhoneOnlyMessage = $this->isPhoneOnlyMessage($text);

        $evidence = $this->classifier->evidence($text);
        if (!$isPhoneOnlyMessage && $evidence['sector'] !== 'guidance') {
            $data['detected_sector'] = $evidence['sector'];
            $data['routing_confidence'] = $evidence['confidence'];
        }

        // Accumulate raw_need only for meaningful messages (not phone-only)
        if (!$isPhoneOnlyMessage) {
            $data['raw_need'] = trim(implode("\n", array_filter([$data['raw_need'] ?? '', $message])));
        }
        $data['last_user_intent'] = trim($message);

        // Mobile phone: Italian number with spaces, dots, dashes, +39 prefix
        if (preg_match('/(?:\+39[\s.\-]*)?(3\d{2}[\s.\-]?\d{3}[\s.\-]?\d{3,4})/u', $text, $match)) {
            $phone = preg_replace('/\D+/', '', $match[1]);
            if (empty($data['phone'])) {
                $data['phone'] = $phone;
            }
        }

        // Italian landline: 0x format, 9-11 digits total
        if (empty($data['phone']) && preg_match('/\b(0\d[\s.\-]?\d{3,4}[\s.\-]?\d{3,5})\b/u', $text, $match)) {
            $phone = preg_replace('/\D+/', '', $match[1]);
            if (strlen($phone) >= 9 && strlen($phone) <= 11) {
                $data['phone'] = $phone;
            }
        }

        // Email
        if (preg_match('/[\w.+-]+@[\w.-]+\.[a-z]{2,}/ui', $message, $match)) {
            $data['email'] = strtolower($match[0]);
        }

        // Name: "sono Marco", "mi chiamo Marco"
        if (preg_match('/\b(?:sono|mi chiamo|mi chiamano|chiamami)\s+([A-ZÀÈÌÒÙ][a-zàèìòùA-ZÀÈÌÒÙ]{2,})/u', $message, $match)) {
            $data['name'] = $match[1];
        }

        // Operators (TLC)
        foreach ([
            'vodafone' => 'Vodafone', 'windtre' => 'WindTre', 'wind tre' => 'WindTre', 'w3' => 'WindTre',
            'fastweb' => 'Fastweb', 'tim' => 'TIM', 'iliad' => 'Iliad',
            'sky wifi' => 'Sky Wifi', 'eolo' => 'Eolo', 'linkem' => 'Linkem', 'opnet' => 'OpNet',
        ] as $needle => $label) {
            if (str_contains($text, $needle)) {
                $data['operator'] = $label;
                break;
            }
        }

        // Access type
        if (preg_match('/\b(fwa|wireless|radio)\b/u', $text)) {
            $data['access_type'] = 'FWA';
        } elseif (preg_match('/\bfibra\b/u', $text)) {
            $data['access_type'] = 'fibra';
        } elseif (preg_match('/\badsl\b/u', $text)) {
            $data['access_type'] = 'ADSL';
        }

        // Mobile/data signals
        if (preg_match('/giga|sim|rete mobile|dati mobil/u', $text)) {
            $data['service_kind'] = 'mobile_data';
        }

        // Symptoms
        if (preg_match('/non (?:mi )?va (?:il )?(?:cell|telefono)|telefono non va|cellulare non va/u', $text)) {
            $data['symptoms']['mobile_not_working'] = true;
        }
        if (preg_match('/finit[oi] i giga|giga finit|non so se ho finit/u', $text)) {
            $data['symptoms']['data_allowance_uncertain'] = true;
        }

        // Request type
        if (preg_match('/cambiar(?:e|ei) offerta|offerta nuova|nuova offerta|offerte? (?:buone?|migliore?|più convenienti?)/u', $text)) {
            $data['request_type'] = 'change_offer';
        }
        if (preg_match('/linea (?:internet )?nuova|nuova linea|attivare (?:una )?linea/u', $text)) {
            $data['request_type'] = 'new_line';
        }

        // IT request type
        if (preg_match('/cambi(?:are?|o) (?:il |la |lo )?(?:telefono|cellulare|smartphone)|nuovo (?:telefono|cellulare|smartphone)|cerca(?:re|ndo) (?:un )?(?:telefono|cellulare|smartphone)|consiglio.*(?:telefono|cellulare|smartphone)/u', $text)) {
            $data['it_request'] = 'new_device';
        } elseif (preg_match('/cambi(?:are?|o) (?:il |la |lo )?(?:gpu|scheda video|ram|ssd|hard disk)|upgrade/u', $text)) {
            $data['it_request'] = 'upgrade_component';
        } elseif (preg_match('/ripar|assist|non funziona|rotto|schermo rotto/u', $text)) {
            $data['it_request'] = 'repair';
        } elseif (preg_match('/nuovo (?:pc|computer|notebook|laptop)|acquistare? (?:un )?(?:pc|computer|notebook)/u', $text)) {
            $data['it_request'] = 'new_pc';
        }

        // Device preference (brand/model keywords)
        $this->extractDevicePreference($text, $data);

        // Gaming context (positive)
        if (preg_match('/\b(gioc[aoe]|gaming|ping|lag|\bms\b|partite online)\b/u', $text)) {
            $data['usage_context']['gaming'] = true;
            $data['pain_points']['stabilita_ping'] = true;
        }

        // Gaming correction (explicit denial)
        if (preg_match('/chi ti ha detto che gioco|non (?:ho detto|gioco)|niente gaming|non ho parlato di gaming/u', $text)) {
            unset($data['usage_context']['gaming'], $data['pain_points']['stabilita_ping']);
            if (isset($data['usage_context']) && empty($data['usage_context'])) {
                unset($data['usage_context']);
            }
        }

        // Topic correction: "non ho parlato di X, ti ho chiesto Y"
        if (preg_match('/non ho (?:detto|parlato di|chiesto)|non ho mai (?:detto|menzionato)|ti ho chiesto/u', $text)) {
            $data['topic_correction'] = true;
        }

        // Budget detection: "budget di 1000", "ho 500 euro", "spendo al massimo 200€", "1200€"
        if (preg_match('/budget\s+(?:di\s+)?(\d{3,5})|(\d{3,5})\s*(?:euro|€)|spendo[^\d]{0,15}(\d{3,5})|al massimo\s+(\d{3,5})/ui', $message, $bm)) {
            $matches = array_filter(array_slice($bm, 1), static fn($v) => $v !== '' && $v !== null);
            if ($matches) {
                $budgetVal = (int)reset($matches);
                if ($budgetVal >= 50 && $budgetVal <= 10000) {
                    $data['budget_eur'] = $budgetVal;
                }
            }
        }

        // Purchase method preference
        if (preg_match('/con operatore|abbinato a (?:un )?(?:piano|offerta|contratto)|con (?:il )?piano/u', $text)) {
            $data['purchase_method'] = 'with_operator';
        } elseif (preg_match('/acquisto diretto|senza operatore|acquisto solo|solo acquisto|sbloccat/u', $text)) {
            $data['purchase_method'] = 'direct_purchase';
        }

        // Trade-in / permuta
        if (preg_match('/permuta|ritiro vecchio|vecchio telefono|cambio con il mio vecchio/u', $text)) {
            $data['trade_in'] = true;
        }

        // Hardware signals
        if (preg_match('/\b(scheda video|fps|driver|windows|temperatura|scalda|surriscalda|notebook|laptop|gpu|ram|ssd)\b/u', $text)) {
            $data['hardware_signals'] = true;
        }

        // Connectivity signals
        if (preg_match('/\b(connessione|internet|fibra|fwa|modem|ping|lag|operatore|vodafone|fastweb|tim|windtre|linea)\b/u', $text)) {
            $data['connectivity_signals'] = true;
        }

        // Pain points
        if (preg_match('/lent[aoe]|si blocca|instabil/u', $text)) {
            $data['pain_points']['lentezza'] = true;
        }

        // Urgency
        if (preg_match('/mi sta bloccando|non riesco a lavorare|urgent|subito|oggi|bloccat|mi serve subito|sta bloccando|mi blocca/u', $text)) {
            $data['urgency'] = 'alta';
        }

        // Customer type — "negozio" only counts when self-describing, not when saying "porto in negozio"
        if (preg_match('/azienda|attivit[àa]|ufficio|piva|partita iva/u', $text)) {
            $data['customer_type'] = 'business';
        } elseif (preg_match('/(?:il mio|nostra?|un[ao]?) negozio|ho (?:un[ao]?) negozio|gestisco|il negozio(?! bisped| vostro)/u', $text)) {
            $data['customer_type'] = 'business';
        }

        // Callback request
        if (preg_match('/chiamatemi|richiamatemi|sentiamoci|chiamate(?:mi)?|contattatemi/u', $text)) {
            $data['callback_requested'] = true;
        }

        // Direct WhatsApp request
        if (preg_match('/whatsapp|posso scriverti|scrivo io|voglio (?:parlare|scrivere)|parlare con qualcuno|operatore umano/u', $text)) {
            $data['handoff_requested'] = true;
        }

        // Competitor/offer received
        if (preg_match('/mi hanno chiamat|telefonat|offerta scritta|proposta scritta/u', $text)) {
            $data['trigger'] = $data['trigger'] ?? 'chiamata_commerciale';
        }

        // Customer emotion
        if (preg_match("/te l.ho già detto|me l.hai già chiesto|già detto|già chiesto|quante volte|basta|smettila/ui", $text)) {
            $data['customer_emotion'] = 'frustrated';
        } elseif (!empty($data['urgency']) && $data['urgency'] === 'alta') {
            $data['customer_emotion'] = $data['customer_emotion'] ?? 'urgent';
        }

        $this->extractEnergy($text, $data);

        // Only update need_summary if this is not a phone-only message
        if (!$isPhoneOnlyMessage) {
            $newSummary = $this->summarize($data, $message);
            // Only overwrite if the new summary is more specific than what we have
            if ($this->isBetterSummary($newSummary, $data['need_summary'] ?? '')) {
                $data['need_summary'] = $newSummary;
            } elseif (empty($data['need_summary'])) {
                $data['need_summary'] = $newSummary;
            }
        }

        $data['facts'] = $this->facts($data);

        return $data;
    }

    private function extractDevicePreference(string $text, array &$data): void
    {
        // Foldable phones
        if (preg_match('/\bfold\b|z fold|galaxy fold|pieghevole|foldable/u', $text)) {
            $data['device_type']  = 'foldable';
            $data['device_brand'] = $data['device_brand'] ?? 'Samsung';
            $data['it_request']   = $data['it_request'] ?? 'new_device';
        }
        if (preg_match('/\bflip\b|z flip|galaxy flip/u', $text)) {
            $data['device_type']  = 'foldable_flip';
            $data['device_brand'] = $data['device_brand'] ?? 'Samsung';
            $data['it_request']   = $data['it_request'] ?? 'new_device';
        }

        // Brands
        foreach ([
            'samsung' => 'Samsung', 'apple' => 'Apple', 'iphone' => 'Apple',
            'xiaomi' => 'Xiaomi', 'motorola' => 'Motorola', 'oppo' => 'Oppo',
            'oneplus' => 'OnePlus', 'google pixel' => 'Google',
        ] as $needle => $brand) {
            if (str_contains($text, $needle) && empty($data['device_brand'])) {
                $data['device_brand'] = $brand;
                break;
            }
        }

        // PC/notebook brands
        foreach (['acer', 'asus', 'lenovo', 'hp ', ' hp', 'dell', 'msi', 'razer'] as $needle) {
            if (str_contains($text, $needle) && empty($data['pc_brand'])) {
                $data['pc_brand'] = ucfirst(trim($needle));
                break;
            }
        }

        // GPU brands/models
        if (preg_match('/rtx\s*\d{3,4}|gtx\s*\d{3,4}|rx\s*\d{3,4}|radeon|geforce|rtx|nvidia|amd gpu/u', $text)) {
            $data['gpu_request'] = true;
            $data['it_request']  = $data['it_request'] ?? 'upgrade_component';
        }

        // Use case signals
        if (preg_match('/fotografi[ae]|foto|camera|selfie/u', $text)) {
            $data['use_case']['photography'] = true;
        }
        if (preg_match('/lavoro|professionale|business/u', $text)) {
            $data['use_case']['work'] = true;
        }
        if (preg_match('/gaming|gioc|fps|frame rate/u', $text) && empty($data['usage_context']['gaming'])) {
            $data['use_case']['gaming'] = true;
        }
    }

    /**
     * Returns true if the message contains ONLY a phone number (mobile or landline),
     * possibly with spaces/dashes, and nothing else meaningful.
     */
    private function isPhoneOnlyMessage(string $text): bool
    {
        $clean = preg_replace('/[\s.\-+]/', '', $text) ?? '';
        // Mobile: 3xx xxxxxxx
        if (preg_match('/^39?3\d{8,9}$/', $clean)) {
            return true;
        }
        // Italian mobile without prefix
        if (preg_match('/^3\d{8,9}$/', $clean)) {
            return true;
        }
        // Landline: 0x... 9-11 digits
        if (preg_match('/^0\d{8,10}$/', $clean)) {
            return true;
        }
        // Generic number-only message (just digits and spaces)
        if (preg_match('/^\d[\d\s.\-]{6,14}$/', $text)) {
            return true;
        }

        return false;
    }

    private function isBetterSummary(string $new, string $existing): bool
    {
        if ($existing === '' || $existing === 'Richiesta da approfondire' || $existing === 'Da approfondire') {
            return true;
        }
        // Don't replace a good summary with a generic one
        $generic = ['Assistenza tecnica dispositivo', 'Problema connessione internet', 'Verifica energia o pratica amministrativa'];
        if (in_array($new, $generic, true) && !in_array($existing, $generic, true) && strlen($existing) > strlen($new)) {
            return false;
        }

        return strlen($new) >= strlen($existing);
    }

    private function extractEnergy(string $text, array &$data): void
    {
        if (preg_match('/\b(luce|gas|energia|bollett)/u', $text)) {
            $data['commodity'] = str_contains($text, 'gas') && str_contains($text, 'luce') ? 'luce_gas' : (str_contains($text, 'gas') ? 'gas' : 'luce');
        }
        if (preg_match('/voltura|subentro|pratica/u', $text)) {
            $data['trigger'] = 'voltura_subentro';
        } elseif (preg_match('/mi hanno chiamat|telefonat|devo cambiare/u', $text)) {
            $data['trigger'] = $data['trigger'] ?? 'chiamata_commerciale';
        } elseif (preg_match('/proposta scritta|preventivo|offerta scritta/u', $text)) {
            $data['trigger'] = 'proposta_scritta';
        } elseif (preg_match('/aument|pago troppo|spendo troppo|risparmi/u', $text)) {
            $data['trigger'] = 'costo_alto';
        }
    }

    private function summarize(array $data, string $rawMessage): string
    {
        // Use main_sector as fallback when detected_sector is not in current data
        // (main_sector is persisted to DB; detected_sector is per-message)
        $sector = $data['detected_sector'] ?? $data['main_sector'] ?? '';

        if ($sector === 'tlc') {
            $parts = [];
            if (!empty($data['operator'])) {
                $parts[] = $data['operator'];
            }
            if (!empty($data['access_type'])) {
                $parts[] = $data['access_type'];
            }
            if (!empty($data['usage_context']['gaming'])) {
                $parts[] = 'gaming online';
            }
            if (!empty($data['pain_points']['lentezza'])) {
                $parts[] = 'lentezza';
            }
            if (!empty($data['pain_points']['stabilita_ping'])) {
                $parts[] = 'stabilità/ping';
            }
            if ($parts) {
                $base = ($data['request_type'] ?? '') === 'new_line' ? 'Richiesta nuova linea' : 'Connessione';

                return $base . ' ' . implode(', ', $parts);
            }
            if (($data['service_kind'] ?? '') === 'mobile_data') {
                return 'Verifica linea mobile o traffico dati';
            }
            if (($data['request_type'] ?? '') === 'change_offer') {
                return 'Richiesta confronto offerte' . (!empty($data['operator']) ? ' (attuale: ' . $data['operator'] . ')' : '');
            }

            return ($data['request_type'] ?? '') === 'new_line' ? 'Richiesta nuova linea internet' : 'Problema connessione internet';
        }

        if ($sector === 'energia_amministrativo') {
            $business = ($data['customer_type'] ?? '') === 'business';

            return $business ? 'Verifica costi energia aziendali' : 'Verifica energia o pratica amministrativa';
        }

        if ($sector === 'informatica') {
            $itReq = $data['it_request'] ?? '';
            // Build specific summary if we have device details
            if (!empty($data['device_type'])) {
                $label = match ($data['device_type']) {
                    'foldable'      => 'Samsung Galaxy Z Fold',
                    'foldable_flip' => 'Samsung Galaxy Z Flip',
                    default         => 'smartphone foldable',
                };
                $suffix = !empty($data['budget_eur']) ? " — budget {$data['budget_eur']}€" : '';

                return "Acquisto {$label}{$suffix}";
            }
            if (!empty($data['device_brand']) && $itReq === 'new_device') {
                return 'Acquisto smartphone ' . $data['device_brand'];
            }
            if ($itReq === 'new_device') {
                return 'Acquisto nuovo smartphone/dispositivo';
            }
            if ($itReq === 'upgrade_component') {
                $comp = !empty($data['gpu_request']) ? 'GPU/scheda video' : 'componente PC';

                return "Upgrade {$comp}";
            }
            if ($itReq === 'repair') {
                return 'Riparazione o assistenza dispositivo';
            }
            if ($itReq === 'new_pc') {
                return 'Acquisto nuovo PC/notebook';
            }

            return 'Assistenza tecnica dispositivo';
        }

        // Fallback: use raw_need if available and meaningful, otherwise rawMessage
        $rawNeed = trim((string)($data['raw_need'] ?? ''));
        if ($rawNeed !== '') {
            return mb_substr($rawNeed, 0, 180, 'UTF-8');
        }

        return mb_substr(trim($rawMessage), 0, 120, 'UTF-8');
    }

    private function facts(array $data): array
    {
        return array_filter([
            'operator'             => $data['operator'] ?? null,
            'access_type'          => $data['access_type'] ?? null,
            'service_kind'         => $data['service_kind'] ?? null,
            'request_type'         => $data['request_type'] ?? null,
            'it_request'           => $data['it_request'] ?? null,
            'symptoms'             => $data['symptoms'] ?? null,
            'usage_context'        => $data['usage_context'] ?? null,
            'pain_points'          => $data['pain_points'] ?? null,
            'urgency'              => $data['urgency'] ?? null,
            'phone'                => $data['phone'] ?? null,
            'customer_type'        => $data['customer_type'] ?? null,
            'trigger'              => $data['trigger'] ?? null,
            'commodity'            => $data['commodity'] ?? null,
            'hardware_signals'     => $data['hardware_signals'] ?? null,
            'connectivity_signals' => $data['connectivity_signals'] ?? null,
            'device_type'          => $data['device_type'] ?? null,
            'device_brand'         => $data['device_brand'] ?? null,
            'pc_brand'             => $data['pc_brand'] ?? null,
            'gpu_request'          => $data['gpu_request'] ?? null,
            'use_case'             => $data['use_case'] ?? null,
            'budget_eur'           => $data['budget_eur'] ?? null,
            'purchase_method'      => $data['purchase_method'] ?? null,
            'trade_in'             => $data['trade_in'] ?? null,
        ], static fn (mixed $v): bool => $v !== null && $v !== []);
    }
}
