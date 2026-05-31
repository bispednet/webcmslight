<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class CommercialReportBuilder
{
    public function buildReport(ConversationMemory $memory, array $transcript = []): string
    {
        $agentName = $this->agentDisplayName($memory->activeAgent);
        $sector = $this->sectorLabel($memory->mainSector);
        $temp = $this->leadTemperature($memory);
        $score = (new HandoffDecisionEngine())->score($memory);

        $lines = [
            "Lead {$sector} — " . $this->needLabel($memory),
            "Score: {$score} | Temperatura: {$temp}",
            "Agente: {$agentName}",
            '',
            '--- Sintesi ---',
            $this->buildSynthesis($memory),
            '',
            '--- Dati raccolti ---',
        ];

        foreach ($this->collectFacts($memory) as $label => $value) {
            $lines[] = "- {$label}: {$value}";
        }

        $lines[] = '';
        $lines[] = '--- Leva commerciale ---';
        $lines[] = $this->commercialLever($memory);

        $crossSell = $this->crossSell($memory);
        if ($crossSell) {
            $lines[] = '';
            $lines[] = '--- Cross-sell sensato ---';
            foreach ($crossSell as $cs) {
                $lines[] = "- {$cs}";
            }
        }

        $missing = $this->missingForHuman($memory);
        if ($missing) {
            $lines[] = '';
            $lines[] = '--- Dati mancanti per operatore ---';
            foreach ($missing as $m) {
                $lines[] = "- {$m}";
            }
        }

        $lines[] = '';
        $lines[] = '--- Prossima azione consigliata ---';
        $lines[] = $this->nextAction($memory);

        if ($memory->handoffReason) {
            $lines[] = '';
            $lines[] = 'Handoff reason: ' . $memory->handoffReason;
        }

        return implode("\n", $lines);
    }

    public function buildAnalytics(ConversationMemory $memory): array
    {
        return [
            'lead_temperature' => $this->leadTemperature($memory),
            'commercial_intent' => $this->commercialIntent($memory),
            'sales_angle' => $this->salesAngle($memory),
            'handoff_reason' => $memory->handoffReason,
            'missing_for_human' => $this->missingForHuman($memory),
            'recommended_next_action' => $this->nextAction($memory),
            'cross_sell' => $this->crossSell($memory),
            'sector' => $memory->mainSector,
            'active_agent' => $memory->activeAgent,
            'urgency' => $memory->urgency,
            'customer_emotion' => $memory->customerEmotion,
            'gaming_context' => $memory->getGamingContext(),
        ];
    }

    private function leadTemperature(ConversationMemory $memory): string
    {
        if ($memory->isUrgent() && $memory->has('phone')) {
            return 'hot';
        }
        if ($memory->hasSectorAndNeed() || $memory->has('phone')) {
            return 'warm';
        }

        return 'cold';
    }

    private function buildSynthesis(ConversationMemory $memory): string
    {
        $parts = [];
        if ($memory->needSummary) {
            $parts[] = $memory->needSummary;
        }
        if ($memory->has('operator')) {
            $parts[] = 'Operatore: ' . $memory->facts['operator'];
        }
        if ($memory->has('access_type')) {
            $parts[] = 'Tecnologia: ' . $memory->facts['access_type'];
        }
        if ($memory->isUrgent()) {
            $parts[] = 'Urgenza alta dichiarata.';
        }
        if ($memory->customerType === 'business') {
            $parts[] = 'Cliente business.';
        }

        return $parts ? implode(' ', $parts) : 'Situazione da approfondire.';
    }

    private function collectFacts(ConversationMemory $memory): array
    {
        $facts = [];
        if ($memory->has('operator')) {
            $facts['Operatore'] = $memory->facts['operator'];
        }
        if ($memory->has('access_type')) {
            $facts['Tecnologia'] = $memory->facts['access_type'];
        }
        if ($memory->getGamingContext()) {
            $facts['Uso'] = 'gaming online';
        }
        if (!empty($memory->facts['commodity'])) {
            $facts['Ambito energia'] = $memory->facts['commodity'];
        }
        if (!empty($memory->facts['trigger'])) {
            $facts['Motivo dichiarato'] = $memory->facts['trigger'];
        }
        if (!empty($memory->facts['service_kind'])) {
            $facts['Tipo servizio'] = $memory->facts['service_kind'];
        }
        if (!empty($memory->painPoints)) {
            $facts['Pain points'] = implode(', ', array_keys(array_filter($memory->painPoints)));
        }
        if ($memory->isUrgent()) {
            $facts['Urgenza'] = 'alta';
        }
        if ($memory->customerType) {
            $facts['Tipo cliente'] = $memory->customerType;
        }
        if ($memory->has('phone')) {
            $facts['Telefono'] = $memory->phone;
        }

        return $facts;
    }

    private function commercialLever(ConversationMemory $memory): string
    {
        if ($memory->mainSector === 'tlc') {
            if ($memory->getGamingContext() && !empty($memory->facts['access_type']) && $memory->facts['access_type'] === 'FWA') {
                return 'Non vendere solo velocità nominale. Puntare su stabilità, ping, verifica copertura fibra o alternativa più affidabile.';
            }
            if ($memory->getGamingContext()) {
                return 'Puntare su stabilità e latenza bassa, non sulla velocità di picco. Verificare se esiste un\'alternativa a minor latenza.';
            }

            return 'Verificare copertura e alternativa più stabile prima di proporre una nuova offerta.';
        }
        if ($memory->mainSector === 'energia_amministrativo') {
            if ($memory->customerType === 'business') {
                return 'Cliente business con costi alti: proporre analisi bollette e confronto fornitore. Non inventare risparmi prima della verifica.';
            }

            return 'Analizzare situazione reale prima di proporre cambio fornitore. Non promettere risparmi senza verifica.';
        }
        if ($memory->mainSector === 'informatica') {
            return 'Capire prima se è problema software, hardware o configurazione prima di proporre intervento o acquisto.';
        }

        return 'Approfondire la situazione prima di proporre una soluzione.';
    }

    private function crossSell(ConversationMemory $memory): array
    {
        $cs = [];
        if ($memory->mainSector === 'tlc') {
            if ($memory->getGamingContext()) {
                $cs[] = 'Controllo modem/router';
                $cs[] = 'Verifica Wi-Fi interno';
                if (!empty($memory->secondarySectors) && in_array('andreai', $memory->secondarySectors, true)) {
                    $cs[] = 'Supporto tecnico AndreAI per hardware gaming';
                }
            } else {
                $cs[] = 'Verifica copertura fibra';
            }
        }
        if ($memory->mainSector === 'energia_amministrativo' && $memory->customerType === 'business') {
            $cs[] = 'Verifica contratti multipli (luce + gas)';
            $cs[] = 'Gestione pratiche voltura/subentro';
        }

        return $cs;
    }

    private function missingForHuman(ConversationMemory $memory): array
    {
        $missing = [];
        if ($memory->mainSector === 'tlc') {
            if (!$memory->has('operator')) {
                $missing[] = 'Operatore attuale';
            }
            if (!$memory->has('access_type')) {
                $missing[] = 'Tipo linea (fibra/FWA/ADSL)';
            }
            $missing[] = 'Indirizzo/zona per verifica copertura';
        }
        if ($memory->mainSector === 'energia_amministrativo') {
            if (empty($memory->facts['commodity'])) {
                $missing[] = 'Luce, gas o entrambi';
            }
            if (!$memory->has('phone')) {
                $missing[] = 'Recapito telefonico';
            }
        }
        if (!$memory->has('phone') && !in_array('Recapito telefonico', $missing, true)) {
            $missing[] = 'Recapito telefonico';
        }

        return $missing;
    }

    private function nextAction(ConversationMemory $memory): string
    {
        if ($memory->mainSector === 'tlc') {
            return 'Rispondere subito su WhatsApp o chiamare. Prima domanda umana: indirizzo/zona per verifica copertura.';
        }
        if ($memory->mainSector === 'energia_amministrativo') {
            return 'Contattare per analisi bollette. Verificare tipo contratto e fornitore attuale.';
        }
        if ($memory->mainSector === 'informatica') {
            return 'Capire entità del problema. Fissare appuntamento o teleassistenza.';
        }

        return 'Contattare e approfondire la situazione.';
    }

    private function commercialIntent(ConversationMemory $memory): string
    {
        if ($memory->isUrgent()) {
            return 'solve_blocking_problem';
        }
        if (!empty($memory->facts['trigger']) && $memory->facts['trigger'] === 'costo_alto') {
            return 'reduce_costs';
        }
        if (!empty($memory->facts['request_type']) && $memory->facts['request_type'] === 'new_line') {
            return 'new_service';
        }

        return 'improve_service';
    }

    private function salesAngle(ConversationMemory $memory): string
    {
        if ($memory->mainSector === 'tlc' && $memory->getGamingContext()) {
            return 'stability_not_price';
        }
        if ($memory->mainSector === 'energia_amministrativo') {
            return 'cost_reduction';
        }

        return 'service_improvement';
    }

    private function agentDisplayName(string $key): string
    {
        return match ($key) {
            'serenai' => 'SerenAI',
            'andreai' => 'AndreAI',
            default => 'SarAI',
        };
    }

    private function sectorLabel(?string $sector): string
    {
        return match ($sector) {
            'tlc' => 'TLC',
            'informatica' => 'IT',
            'energia_amministrativo' => 'Energia',
            default => 'Orientamento',
        };
    }

    private function needLabel(ConversationMemory $memory): string
    {
        if ($memory->getGamingContext() && $memory->mainSector === 'tlc') {
            return 'gaming / connessione';
        }
        if (!empty($memory->facts['trigger']) && $memory->facts['trigger'] === 'costo_alto') {
            return 'costi alti';
        }

        return mb_strtolower(mb_substr($memory->needSummary ?? 'da verificare', 0, 40, 'UTF-8'), 'UTF-8');
    }
}
