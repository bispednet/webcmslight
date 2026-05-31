<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class AgentTurnPlanner
{
    public function plan(ConversationMemory $memory, bool $isRepair = false): array
    {
        if ($isRepair) {
            return $this->repairPlan($memory);
        }

        // Handoff esplicito richiesto dal cliente
        if ($memory->handoffExplicitlyRequested) {
            return $this->handoffPlan($memory, 'customer_requested');
        }

        // Telefono già presente + settore + esigenza → handoff
        if ($memory->has('phone') && $memory->hasSectorAndNeed()) {
            return $this->handoffPlan($memory, 'phone_and_context_ready');
        }

        // Cliente irritato + dati minimi → handoff
        if ($memory->isIrritated() && $memory->hasSectorAndNeed()) {
            if (!$memory->has('phone')) {
                return $this->askOnePlan('phone', 'collect_callback_irritated');
            }

            return $this->handoffPlan($memory, 'customer_irritated_sufficient_context');
        }

        // Settore sconosciuto → chiedi cosa serve
        if (!$memory->has('sector')) {
            return $this->clarifySectorPlan($memory);
        }

        // ── Logica per settore ────────────────────────────────────────────
        return match ($memory->mainSector) {
            'tlc'                     => $this->planTlc($memory),
            'informatica'             => $this->planIt($memory),
            'energia_amministrativo'  => $this->planEnergia($memory),
            default                   => $this->planGeneric($memory),
        };
    }

    // ── TLC ─────────────────────────────────────────────────────────────────
    private function planTlc(ConversationMemory $memory): array
    {
        // Handoff con telefono
        if ($memory->has('phone') && $memory->hasSectorAndNeed()) {
            return $this->handoffPlan($memory, 'phone_and_context_ready');
        }

        // Turno 1: raccogliere operatore e tipo linea se non li ha
        if ($memory->usefulTurnCount <= 1 && !$memory->has('operator') && !$memory->has('access_type')) {
            return $this->askOnePlan('operator_and_access', 'collect_tlc_context');
        }

        // Turno 2: capire l'impatto se non urgente
        if ($memory->usefulTurnCount <= 2 && !$memory->isUrgent()) {
            $hasEnoughContext = $memory->has('operator') || $memory->has('access_type') || !empty($memory->facts['request_type']);
            if ($hasEnoughContext) {
                return $this->askOnePlan('severity', 'collect_impact_level');
            }
        }

        // Urgente + no telefono → chiedi solo numero
        if ($memory->isUrgent() && !$memory->has('phone')) {
            return $this->askOnePlan('phone', 'collect_urgent_callback');
        }

        // Dopo 2+ turni → chiedi telefono
        if ($memory->usefulTurnCount >= 2 && !$memory->has('phone')) {
            return $this->askOnePlan('phone', 'collect_callback_or_direct_whatsapp');
        }

        // Handoff se callback richiesto con telefono
        if ($memory->callbackRequested && $memory->has('phone')) {
            return $this->handoffPlan($memory, 'callback_with_phone');
        }

        // Max turni
        if ($memory->usefulTurnCount >= 4) {
            return $this->handoffPlan($memory, 'max_turns');
        }

        return $this->clarifyPlan($memory);
    }

    // ── Informatica ──────────────────────────────────────────────────────────
    private function planIt(ConversationMemory $memory): array
    {
        // Handoff con telefono
        if ($memory->has('phone') && $memory->hasSectorAndNeed()) {
            return $this->handoffPlan($memory, 'phone_and_context_ready');
        }

        $itReq    = $memory->facts['it_request'] ?? null;
        $devType  = $memory->facts['device_type'] ?? null;
        $budget   = $memory->facts['budget_eur'] ?? null;
        $method   = $memory->facts['purchase_method'] ?? null;

        // Urgente → chiedi subito numero
        if ($memory->isUrgent() && !$memory->has('phone')) {
            return $this->askOnePlan('phone', 'collect_urgent_callback');
        }

        // Nessuna richiesta ancora chiara → chiedi cosa serve
        if ($itReq === null) {
            return $this->askOnePlan('it_request_detail', 'collect_it_problem');
        }

        // Nuovo dispositivo: percorso di qualifica commerciale
        if (in_array($itReq, ['new_device', 'new_pc'], true)) {
            // Nessun dettaglio dispositivo e budget → chiedi modello/budget insieme
            if ($devType === null && $budget === null) {
                return $this->askOnePlan('device_details', 'collect_device_preference');
            }
            // Abbiamo device ma non budget → chiedi budget
            if ($budget === null) {
                return $this->askOnePlan('budget', 'collect_budget');
            }
            // Abbiamo budget ma non metodo acquisto → chiedi diretto vs operatore
            if ($method === null && $itReq === 'new_device') {
                return $this->askOnePlan('purchase_method', 'collect_purchase_method');
            }
            // Tutto raccolto → chiedi telefono
            return $this->askOnePlan('phone', 'collect_callback_or_direct_whatsapp');
        }

        // Upgrade/riparazione: chiedi contesto al turno 1, poi telefono
        if (in_array($itReq, ['upgrade_component', 'repair', 'new_pc'], true)) {
            if ($memory->usefulTurnCount <= 1) {
                return $this->askOnePlan('it_repair_detail', 'collect_repair_context');
            }
            if (!$memory->has('phone')) {
                return $this->askOnePlan('phone', 'collect_callback_or_direct_whatsapp');
            }
        }

        // Max turni
        if ($memory->usefulTurnCount >= 4) {
            return $this->handoffPlan($memory, 'max_turns');
        }

        return $this->clarifyPlan($memory);
    }

    // ── Energia ──────────────────────────────────────────────────────────────
    private function planEnergia(ConversationMemory $memory): array
    {
        // Handoff con telefono
        if ($memory->has('phone') && $memory->hasSectorAndNeed()) {
            return $this->handoffPlan($memory, 'phone_and_context_ready');
        }

        // Turno 1: capire luce/gas se non specificato
        if ($memory->usefulTurnCount <= 1 && empty($memory->facts['commodity'])) {
            return $this->askOnePlan('commodity', 'collect_energy_type');
        }

        // Urgente → chiedi numero
        if ($memory->isUrgent() && !$memory->has('phone')) {
            return $this->askOnePlan('phone', 'collect_urgent_callback');
        }

        // Dopo 1+ turni → chiedi telefono
        if ($memory->usefulTurnCount >= 2 && !$memory->has('phone')) {
            return $this->askOnePlan('phone', 'collect_callback_or_direct_whatsapp');
        }

        if ($memory->usefulTurnCount >= 3) {
            return $this->handoffPlan($memory, 'max_turns');
        }

        return $this->clarifyPlan($memory);
    }

    // ── Generic / guidance ───────────────────────────────────────────────────
    private function planGeneric(ConversationMemory $memory): array
    {
        if ($memory->usefulTurnCount >= 2) {
            if ($memory->has('phone')) {
                return $this->handoffPlan($memory, 'sufficient_context');
            }

            return $this->askOnePlan('phone', 'collect_callback_or_direct_whatsapp');
        }

        return $this->clarifySectorPlan($memory);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────
    private function handoffPlan(ConversationMemory $memory, string $reason): array
    {
        return ['action' => 'handoff', 'slot' => null, 'question_intent' => null, 'handoff_ready' => true, 'handoff_reason' => $reason, 'customer_visible' => true];
    }

    private function askOnePlan(string $slot, string $intent): array
    {
        return ['action' => 'ask_one_question', 'slot' => $slot, 'question_intent' => $intent, 'handoff_ready' => false, 'customer_visible' => true];
    }

    private function repairPlan(ConversationMemory $memory): array
    {
        return ['action' => 'repair', 'slot' => null, 'question_intent' => 'acknowledge_correction', 'handoff_ready' => $memory->hasSectorAndNeed() && $memory->has('phone'), 'customer_visible' => true];
    }

    private function clarifySectorPlan(ConversationMemory $memory): array
    {
        return ['action' => 'clarify', 'slot' => 'sector', 'question_intent' => 'identify_sector', 'handoff_ready' => false, 'customer_visible' => true];
    }

    private function clarifyPlan(ConversationMemory $memory): array
    {
        return ['action' => 'clarify', 'slot' => 'need', 'question_intent' => 'understand_need', 'handoff_ready' => false, 'customer_visible' => true];
    }
}
