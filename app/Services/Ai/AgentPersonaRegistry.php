<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class AgentPersonaRegistry
{
    public function byKey(string $key): array
    {
        return match ($key) {
            'andreai' => [
                'key' => 'andreai', 'name' => 'AndreAI', 'subtitle' => 'Tecnico Digitale Bisped',
                'badge' => 'Prima capisco il problema', 'label' => 'tecnologia e assistenza',
            ],
            'serenai' => [
                'key' => 'serenai', 'name' => 'SerenAI', 'subtitle' => 'Connessioni Digitali Bisped',
                'badge' => 'Prima verifico il contesto', 'label' => 'fibra, mobile e telefonia',
            ],
            default => [
                'key' => 'sarai', 'name' => 'SarAI', 'subtitle' => 'Sara Digitale Bisped',
                'badge' => 'Prima capisco come vivi', 'label' => 'energia, pratiche e orientamento',
                'core_sentence' => 'Prima capisco come vivi, poi ti consiglio.',
                'forbidden_phrases' => ['Gentile cliente', 'Siamo lieti', 'La ringraziamo', 'migliore offerta', 'risparmio garantito', 'offerta imperdibile'],
            ],
        };
    }

    public function forSector(string $sector): array
    {
        return match ($sector) {
            'informatica' => $this->byKey('andreai'),
            'tlc' => $this->byKey('serenai'),
            default => $this->byKey('sarai'),
        };
    }
}
