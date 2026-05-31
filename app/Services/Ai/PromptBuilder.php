<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class PromptBuilder
{
    public function conversationAnalysis(string $userMessage, array $data): string
    {
        $context = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
Analizza il turno del cliente per il router Bisped. Devi estrarre solo ciò che il cliente ha espresso, senza completare ipotesi.
Settori ammessi: tlc per telefonia, SIM, giga, offerte mobile, internet e connettività; informatica per device, riparazioni e assistenza tecnica; energia_amministrativo per energia, bollette e pratiche; guidance se non è chiaro.
Se il cliente vuole scrivere direttamente su WhatsApp, handoff_requested deve essere true.
Se contesta un riferimento al gaming che non ha fatto, remove_gaming_assumption deve essere true.
La sintesi deve essere breve, concreta e prudente. Non diagnosticare. Non inventare operatore, gaming, tecnologia, urgenza o recapiti.

Conversazione raccolta:
{$context}

Nuovo messaggio:
{$userMessage}

Rispondi solo JSON:
{"sector":"guidance|tlc|informatica|energia_amministrativo","confidence":0.0,"need_summary":"...","handoff_requested":false,"remove_gaming_assumption":false}
PROMPT;
    }

}
