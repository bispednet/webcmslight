<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class AgentPersonaRegistry
{
    public function forSector(string $sector): array
    {
        return match ($sector) {
            'informatica' => ['key' => 'andreai', 'name' => 'AndreAI', 'label' => 'tecnologia e assistenza'],
            'tlc' => ['key' => 'serenai', 'name' => 'SerenAI', 'label' => 'fibra, mobile e telefonia'],
            'energia_amministrativo' => ['key' => 'sarai', 'name' => 'SarAI', 'label' => 'energia e pratiche'],
            'business' => ['key' => 'router', 'name' => 'Team AI Bisped', 'label' => 'soluzioni business'],
            default => ['key' => 'router', 'name' => 'Team AI Bisped', 'label' => 'orientamento iniziale'],
        };
    }
}
