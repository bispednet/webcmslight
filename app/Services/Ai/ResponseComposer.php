<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class ResponseComposer
{
    private ResponseStyleGuard $guard;

    public function __construct()
    {
        $this->guard = new ResponseStyleGuard();
    }

    public function compose(ConversationMemory $memory, array $plan): string
    {
        $response = match ($plan['action']) {
            'repair'           => $this->composeRepair($memory, $plan),
            'handoff'          => $this->composeHandoff($memory, $plan),
            'ask_one_question' => $this->composeQuestion($memory, $plan),
            'clarify'          => $this->composeClarify($memory, $plan),
            'answer_and_ask'   => $this->composeQuestion($memory, $plan),
            default            => $this->composeFallback($memory),
        };

        if ($this->guard->violatesPublicTone($response)) {
            $cleaned = $this->guard->cleanCustomerMessage($response);
            if ($this->guard->violatesPublicTone($cleaned)) {
                return $this->guard->fallback($memory->mainSector ?? 'guidance');
            }

            return $cleaned;
        }

        return $response;
    }

    // ── SerenAI (TLC) ────────────────────────────────────────────────────────
    public function composeSerenAI(ConversationMemory $memory, array $plan): string
    {
        $intent = $plan['question_intent'] ?? '';
        $slot   = $plan['slot'] ?? '';

        if ($intent === 'collect_tlc_context') {
            if ($memory->getGamingContext()) {
                return 'Ok, per giocare non guarderei solo la velocità: contano stabilità e ping. Con che operatore sei e che linea hai, fibra o FWA?';
            }
            if (!empty($memory->facts['request_type']) && $memory->facts['request_type'] === 'change_offer') {
                return 'Ok. Stai guardando per la linea di casa, per il telefono o per entrambi?';
            }

            return 'Ok. Con che operatore sei adesso e che tipo di linea hai?';
        }

        if ($intent === 'collect_impact_level') {
            $operator = $memory->facts['operator'] ?? null;
            $access   = $memory->facts['access_type'] ?? null;
            if ($access === 'FWA' && $memory->getGamingContext()) {
                return "Chiaro. FWA e gaming spesso non vanno d'accordo, soprattutto se il segnale o la cella non sono stabili. Ti succede ogni tanto o ti sta proprio bloccando?";
            }
            if ($operator) {
                return "Capito. Questo problema è fastidioso ogni tanto o ti sta bloccando davvero?";
            }
            if (!empty($memory->facts['request_type']) && $memory->facts['request_type'] === 'change_offer') {
                $op = $operator ? " con {$operator}" : '';

                return "Ok{$op}. Vuoi un'offerta più economica, più veloce o cerchi qualcosa di specifico?";
            }

            return 'È un problema costante o ti capita ogni tanto?';
        }

        if ($slot === 'phone' || str_contains($intent, 'callback') || str_contains($intent, 'whatsapp')) {
            if ($memory->isUrgent()) {
                return 'Ok, allora lo tratto come urgente. Mi serve solo un numero WhatsApp e ti passo al negozio con il riepilogo già pronto.';
            }

            return 'Ok. Mi lasci un numero e ti passo al negozio con il riepilogo, così non ripeti tutto?';
        }

        return $this->composeFallback($memory);
    }

    // ── AndreAI (IT) ─────────────────────────────────────────────────────────
    public function composeAndreAI(ConversationMemory $memory, array $plan): string
    {
        $intent = $plan['question_intent'] ?? '';
        $slot   = $plan['slot'] ?? '';
        $itReq  = $memory->facts['it_request'] ?? null;

        if ($intent === 'collect_it_problem') {
            $needSummary = mb_strtolower($memory->needSummary ?? '', 'UTF-8');

            // Cambio dispositivo
            if ($itReq === 'new_device' || str_contains($needSummary, 'cellulare') || str_contains($needSummary, 'telefono') || str_contains($needSummary, 'smartphone')) {
                return 'Ok. Hai già in mente il modello o vuoi che ti consigliamo noi qualcosa in base all\'uso che ne fai?';
            }

            // Upgrade componente
            if ($itReq === 'upgrade_component' || str_contains($needSummary, 'gpu') || str_contains($needSummary, 'scheda video')) {
                return 'Ok. Lo porti in negozio per l\'installazione o cerchi solo la scheda da montare tu?';
            }

            // Riparazione
            if ($itReq === 'repair' || str_contains($needSummary, 'ripara') || str_contains($needSummary, 'rotto') || str_contains($needSummary, 'non funziona')) {
                return 'Ok. Che modello è e cosa non funziona esattamente?';
            }

            return 'Ok, capito. È una cosa da portare in negozio o stai cercando un consiglio su cosa acquistare?';
        }

        if ($intent === 'collect_repair_context') {
            if ($itReq === 'upgrade_component' || !empty($memory->facts['gpu_request'])) {
                return 'Ok. Lo porti in negozio per l\'installazione o cerchi solo la scheda da montare tu?';
            }
            if ($itReq === 'repair') {
                return 'Ok. Che modello è e cosa non funziona esattamente?';
            }
            if ($itReq === 'new_pc') {
                return 'Ok. Stai cercando qualcosa per lavoro, gaming o uso generico? Hai un budget in mente?';
            }

            return 'Ok. Lo porti da noi o cerchi un consiglio su cosa comprare?';
        }

        if ($intent === 'collect_device_preference') {
            $devType = $memory->facts['device_type'] ?? null;
            if ($devType === 'foldable') {
                return 'Bene, Samsung Galaxy Z Fold è una scelta interessante. Hai un budget in mente? E preferisci acquisto diretto o ti interessa valutare un piano operatore abbinato?';
            }
            if ($devType === 'foldable_flip') {
                return 'Bene, Samsung Galaxy Z Flip. Hai un budget in mente? E preferisci acquisto diretto o con operatore?';
            }
            if (!empty($memory->facts['device_brand'])) {
                return 'Ok. Hai un budget in mente? E preferisci acquisto diretto o ti interessa valutare un piano operatore?';
            }

            return 'Ok. Hai già in mente un modello o un brand? E hai un budget di massima?';
        }

        if ($intent === 'collect_budget') {
            $devType = $memory->facts['device_type'] ?? null;
            if ($devType === 'foldable') {
                return 'Il Z Fold parte intorno ai 1700€ in acquisto diretto, ma con operatori si scende parecchio. Hai un budget in mente?';
            }

            return 'Hai un budget in mente? Così ti guido meglio.';
        }

        if ($intent === 'collect_purchase_method') {
            $budget = $memory->facts['budget_eur'] ?? null;
            $budgetStr = $budget ? " con {$budget}€ di budget" : '';

            return "Ok{$budgetStr}. Lo preferisci in acquisto diretto o ti interessa abbinarlo a un piano operatore? Spesso il costo scende parecchio.";
        }

        if ($slot === 'phone' || str_contains($intent, 'callback') || str_contains($intent, 'whatsapp')) {
            if ($memory->isUrgent()) {
                return 'Ok, capito il problema. Mi serve solo un numero e ti passo al negozio con il riepilogo.';
            }
            // Enrich with collected context
            $devType = $memory->facts['device_type'] ?? null;
            if ($devType !== null) {
                return 'Ok, ho tutto quello che serve. Mi lasci un numero e ti passo al negozio con il riepilogo già pronto.';
            }

            return 'Ok. Mi lasci un numero e ti passo al negozio con il riepilogo, così parte già dal punto giusto?';
        }

        return $this->composeFallback($memory);
    }

    // ── SarAI (Energia) ──────────────────────────────────────────────────────
    public function composeSarAI(ConversationMemory $memory, array $plan): string
    {
        $intent = $plan['question_intent'] ?? '';
        $slot   = $plan['slot'] ?? '';

        if ($slot === 'commodity' || $intent === 'collect_energy_type') {
            if ($memory->customerType === 'business' && $memory->has('phone')) {
                return 'Ok, ho già il numero. Prima di passarlo al negozio: parliamo di luce, gas o entrambi?';
            }
            if ($memory->customerType === 'business') {
                return 'Ok. Parliamo di costi luce, gas o entrambi per l\'azienda?';
            }

            return 'Ok. È una questione di luce, gas o entrambi?';
        }

        if ($slot === 'phone' || str_contains($intent, 'callback') || str_contains($intent, 'whatsapp')) {
            if ($memory->isUrgent()) {
                return 'Ok, allora non la allungo. Mi serve solo un numero e ti passo al negozio con quello che c\'è già.';
            }

            return 'Ok. Mi lasci un numero e ti passo al negozio con il riepilogo?';
        }

        return $this->composeFallback($memory);
    }

    // ── Repair ───────────────────────────────────────────────────────────────
    public function composeRepair(ConversationMemory $memory, array $plan): string
    {
        return 'Hai ragione, me l\'avevi già detto. Vado al punto.';
    }

    // ── Handoff ──────────────────────────────────────────────────────────────
    public function composeHandoff(ConversationMemory $memory, array $plan): string
    {
        $reason = $plan['handoff_reason'] ?? '';

        if ($reason === 'customer_requested') {
            return 'Va bene. Ti apro WhatsApp, aggiungo solo una riga per far capire da dove arrivi.';
        }
        if ($memory->has('phone')) {
            return $memory->isUrgent()
                ? 'Perfetto. Ti apro WhatsApp con il riepilogo pronto, così parti già dal punto giusto.'
                : 'Ok, basta così. Ti apro WhatsApp con il riepilogo già pronto.';
        }

        return 'Ok, basta così. Ti apro WhatsApp con quello che c\'è già.';
    }

    // ── Clarify ──────────────────────────────────────────────────────────────
    private function composeClarify(ConversationMemory $memory, array $plan): string
    {
        if (($plan['slot'] ?? '') === 'sector') {
            return 'Ok. Una cosa sola: stai cercando qualcosa per telefono/internet, per luce/gas o è un problema tecnico su un dispositivo?';
        }

        return 'Dimmi pure in due parole cosa ti serve, poi ti faccio solo le domande che contano.';
    }

    // ── Routing ──────────────────────────────────────────────────────────────
    private function composeQuestion(ConversationMemory $memory, array $plan): string
    {
        return match ($memory->activeAgent) {
            'serenai' => $this->composeSerenAI($memory, $plan),
            'andreai' => $this->composeAndreAI($memory, $plan),
            default   => $this->composeSarAI($memory, $plan),
        };
    }

    private function composeFallback(ConversationMemory $memory): string
    {
        return $this->guard->fallback($memory->mainSector ?? 'guidance');
    }
}
