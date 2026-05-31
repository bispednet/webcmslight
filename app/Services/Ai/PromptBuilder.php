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

    public function turnAnalysis(string $userMessage, array $memory): string
    {
        $ctx = json_encode($memory, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
Sei il motore di analisi dell'agente commerciale Bisped. Analizza il messaggio del cliente e restituisci un JSON strutturato.
NON inventare dati. NON diagnosticare coperture, prezzi, risparmi. Estrai solo ciò che il cliente ha detto esplicitamente.

Settori: tlc=connettività/telefonia/operatori, informatica=device/assistenza/hardware, energia_amministrativo=energia/bollette/pratiche, guidance=ambiguo.

Emozioni cliente: neutral, frustrated, urgent, confused, price_sensitive.

Stato conversazione corrente:
{$ctx}

Messaggio cliente:
{$userMessage}

Rispondi SOLO JSON:
{
  "intent": "...",
  "sector": "tlc|informatica|energia_amministrativo|guidance",
  "confidence": 0.0,
  "facts": {},
  "corrections": [],
  "customer_emotion": "neutral|frustrated|urgent|confused|price_sensitive",
  "commercial_opportunities": [],
  "handoff_requested": false,
  "needs_human": false,
  "summary_delta": "..."
}
PROMPT;
    }

    public function customerReplyDraft(array $memory, array $turnPlan): string
    {
        $memCtx = json_encode($memory, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $planCtx = json_encode($turnPlan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $action = $turnPlan['action'] ?? 'ask_one_question';
        $slot = $turnPlan['slot'] ?? '';

        return <<<PROMPT
Sei un operatore commerciale Bisped che risponde in chat WhatsApp. Il tuo tono è naturale, diretto, breve.
NON usare: "Capisco perfettamente", "Gentile cliente", "La ringraziamo", "Siamo lieti", "Assistente digitale autorizzato", "Tre strade sensate", "Essenziale", "Intelligente", "Completa".
NON fare più di una domanda per messaggio.
NON mostrare workflow interno, step o scelte multiple.

Azione richiesta: {$action} (slot mancante: {$slot})

Memoria conversazione:
{$memCtx}

Piano turno:
{$planCtx}

Scrivi solo il messaggio cliente (massimo 2 frasi brevi, tono WhatsApp operatore):
PROMPT;
    }

    public function commercialReport(array $memory, array $transcript): string
    {
        $memCtx = json_encode($memory, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $turns = count($transcript);

        return <<<PROMPT
Sei il sistema di reportistica commerciale Bisped. Genera un report interno per il personale commerciale.
Il report deve essere pratico, orientato all'azione, non didascalico.
NON inventare prezzi, coperture, risparmi garantiti. Usa solo i dati raccolti.

Memoria conversazione ({$turns} turni):
{$memCtx}

Genera JSON:
{
  "lead_temperature": "hot|warm|cold",
  "commercial_intent": "...",
  "sales_angle": "...",
  "synthesis": "...",
  "collected_facts": {},
  "pain_points": [],
  "commercial_lever": "...",
  "cross_sell": [],
  "missing_for_human": [],
  "next_action": "...",
  "handoff_reason": "..."
}
PROMPT;
    }
}
