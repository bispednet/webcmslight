<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class AgentSwarmRouter
{
    private AgentPersonaRegistry $registry;

    public function __construct()
    {
        $this->registry = new AgentPersonaRegistry();
    }

    public function route(ConversationMemory $memory, array $evidence = []): array
    {
        $sector = $this->resolveSector($memory, $evidence);
        $agent = $this->registry->forSector($sector);
        $secondaryAgents = $this->resolveSecondaryAgents($memory, $sector);
        $reason = $this->buildReason($memory, $sector, $evidence);

        return [
            'active_agent' => $agent['key'],
            'main_sector' => $sector === 'guidance' ? null : $sector,
            'secondary_agents' => $secondaryAgents,
            'agent' => $agent,
            'routing_reason' => $reason,
        ];
    }

    private function resolveSector(ConversationMemory $memory, array $evidence): string
    {
        // Honor explicit sector if already confident
        if ($memory->mainSector !== null && $memory->mainSector !== 'guidance') {
            // Allow override only if new evidence is stronger and different
            $evidenceSector = (string)($evidence['sector'] ?? 'guidance');
            if ($evidenceSector !== 'guidance' && $evidenceSector !== $memory->mainSector) {
                $conf = (float)($evidence['confidence'] ?? 0);
                if ($conf >= 0.85) {
                    return $evidenceSector;
                }
            }

            return $memory->mainSector;
        }

        $detected = (string)($evidence['sector'] ?? $memory->mainSector ?? 'guidance');

        return $detected ?: 'guidance';
    }

    private function resolveSecondaryAgents(ConversationMemory $memory, string $primarySector): array
    {
        $secondary = [];

        // Gaming on TLC: if there are also hardware signals, AndreAI is secondary
        if ($primarySector === 'tlc' && $memory->getGamingContext()) {
            // Only add AndreAI as secondary if hardware keywords detected too
            if (!empty($memory->facts['hardware_signals'])) {
                $secondary[] = 'andreai';
            }
        }

        // IT + connectivity issue: SerenAI secondary
        if ($primarySector === 'informatica' && !empty($memory->facts['connectivity_signals'])) {
            $secondary[] = 'serenai';
        }

        return $secondary;
    }

    private function buildReason(ConversationMemory $memory, string $sector, array $evidence): string
    {
        $parts = [];
        if ($sector === 'tlc') {
            if ($memory->getGamingContext()) {
                $parts[] = 'gaming + connessione';
            }
            if (!empty($memory->facts['operator'])) {
                $parts[] = 'operatore: ' . $memory->facts['operator'];
            }
            if (!empty($memory->facts['access_type'])) {
                $parts[] = $memory->facts['access_type'];
            }
        }
        if ($sector === 'informatica') {
            $parts[] = 'dispositivo/hardware';
        }
        if ($sector === 'energia_amministrativo') {
            $parts[] = 'energia/pratiche';
            if ($memory->customerType === 'business') {
                $parts[] = 'cliente business';
            }
        }
        if (empty($parts)) {
            $parts[] = $evidence['routing_reason'] ?? 'segnali generici';
        }

        return implode(', ', $parts);
    }
}
