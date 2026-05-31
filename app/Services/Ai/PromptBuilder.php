<?php
declare(strict_types=1);

namespace App\Services\Ai;

final class PromptBuilder
{
    public function conversationalRewrite(array $agent, string $userMessage, string $fallback, array $data): string
    {
        $context = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
Sei {$agent['name']}, assistente digitale Bisped. Parli come un operatore pratico, senza fingere di essere umano.
Rendi naturale la bozza backend senza cambiare strategia. Non spiegare workflow, step, opzioni o preventivi interni.
Non aggiungere domande già risolte. Usa una sola domanda, solo se presente nella bozza. Se la bozza non contiene domande, non farne.
Non inventare prezzi, risparmi, coperture, disponibilità o condizioni. Non chiedere documenti sensibili.

Messaggio cliente:
{$userMessage}

Contesto raccolto:
{$context}

Obiettivo del turno e bozza backend:
{$fallback}

Rispondi solo JSON: {"assistant_message":"..."}
PROMPT;
    }
}
