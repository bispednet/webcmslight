# AI Concierge — Professional Agent Swarm

Il componente AI Concierge di Bisped implementa un'architettura a swarm di agenti con supervisore invisibile. Il cliente vede solo una chat WhatsApp-like. Il sistema opera come un operatore consapevole, non come un form guidato.

## Architettura

```
WhatsApp-like UI
  → ConversationSupervisor (invisibile al cliente)
  → AgentSwarmRouter
  → agente attivo: SerenAI / AndreAI / SarAI
  → ConversationMemory (stato persistente per turno)
  → LeadExtractor (slot extraction continua)
  → AgentTurnPlanner (prossima mossa)
  → ResponseComposer (risposta naturale)
  → ResponseStyleGuard (blocca frasi vietate)
  → HandoffDecisionEngine (quando passare a WhatsApp)
  → CommercialReportBuilder (report per admin)
  → WhatsApp automatico + summary precompilato
```

## Agenti

| Agente | Settore | Keywords principali |
|--------|---------|---------------------|
| **SerenAI** | TLC | fibra, FWA, modem, operatori, ping, gaming legato alla linea |
| **AndreAI** | Informatica | PC, notebook, device, riparazioni, assistenza hardware |
| **SarAI** | Energia / Amministrativo | luce, gas, bollette, volture, subentri, pratiche |

**Regola gaming**: gaming + connessione/ping/FWA/fibra/operatori → **SerenAI** (non AndreAI). AndreAI diventa agente secondario solo se emergono segnali hardware (PC, scheda video, driver, temperatura).

## Flusso di un turno

1. **LeadExtractor** analizza ogni messaggio: estrae telefono, operatore, tecnologia, urgenza, tipo cliente, segnali gaming, correzioni esplicite.
2. **ConversationRepair** rileva correzioni ("chi ti ha detto che gioco?") e le applica alla memoria.
3. **ConversationMemory** viene aggiornata: i dati espliciti battono sempre le inferenze precedenti.
4. **AgentSwarmRouter** sceglie l'agente attivo in base al settore e ai segnali.
5. **AgentTurnPlanner** decide la prossima mossa: `ask_one_question`, `handoff`, `repair`, `clarify`.
6. **HandoffDecisionEngine** sovrascrive il piano se i criteri di handoff sono soddisfatti.
7. **ResponseComposer** genera la risposta cliente (naturale, senza workflow interno).
8. **ResponseStyleGuard** verifica e ripulisce la risposta da frasi vietate.
9. Se `handoff_ready=true`, **CommercialReportBuilder** genera il report admin e **WhatsAppHandoffBuilder** genera il summary cliente.

## Criteri di handoff automatico

- Cliente chiede esplicitamente umano o WhatsApp → **immediato**
- Cliente è irritato e il settore è noto → **immediato**
- Telefono presente + settore + esigenza → **immediato**
- Urgenza alta + settore + esigenza + ≥2 turni → **immediato**
- Dopo 4 turni utili con settore + esigenza → handoff anche senza telefono
- Callback richiesto + telefono presente → **immediato**

## ConversationMemory

Struttura dati live della conversazione, persistita in `ai_conversations.structured_data`:

```json
{
  "active_agent": "serenai",
  "main_sector": "tlc",
  "phone": "3346582115",
  "urgency": "alta",
  "need_summary": "Connessione Vodafone FWA lenta, gaming online",
  "facts": {
    "operator": "Vodafone",
    "access_type": "FWA",
    "usage_context": {"gaming": true},
    "pain_points": {"stabilita_ping": true}
  },
  "handoff_ready": true,
  "handoff_reason": "phone_and_context_complete",
  "useful_turn_count": 3,
  "commercial_report": "...",
  "analytics": {
    "lead_temperature": "hot",
    "commercial_intent": "solve_blocking_problem",
    "sales_angle": "stability_not_price",
    "cross_sell": ["Controllo modem/router"]
  }
}
```

## Regole UX pubblico

- Niente scelte multiple nel flow pubblico
- Niente card Essenziale/Intelligente/Completa
- Niente copy interno ("posso usare queste risposte?", "Scrivi come parleresti al banco")
- Niente "Capisco perfettamente" ripetuto
- Una domanda per messaggio (al massimo due se strettamente collegate)
- Il launcher apre la chat AI, non WhatsApp direttamente
- WhatsApp si apre automaticamente quando `action=redirect_whatsapp`
- Se il popup è bloccato, compare il bottone "Apri WhatsApp"

## Frasi vietate (ResponseStyleGuard)

```
Capisco perfettamente | Gentile cliente | La ringraziamo | Siamo lieti
Assistente digitale autorizzato | Configurata sul metodo | Tre strade sensate
Essenziale | Intelligente | Completa | Posso usare queste risposte
Sì, prepara il riepilogo | Quanto è urgente? | Scrivi come parleresti al banco
Al resto ci pensa | Ti trasferisco | Passo la parola
```

## Endpoint API

| Metodo | Path | Descrizione |
|--------|------|-------------|
| `GET` | `/ai/concierge/bootstrap` | Avvia conversazione, restituisce greeting |
| `POST` | `/ai/concierge/message` | Invia messaggio, riceve risposta |
| `POST` | `/ai/concierge/handoff/whatsapp` | Forza handoff WhatsApp |
| `GET` | `/admin/ai-concierge` | Lista conversazioni admin |
| `GET` | `/admin/ai-concierge/conversations/{id}` | Dettaglio con report commerciale |

## Risposta API con handoff

```json
{
  "conversation_id": "uuid",
  "reply": "Perfetto. Ti apro WhatsApp con il riepilogo pronto.",
  "action": "redirect_whatsapp",
  "handoff": {
    "url": "https://wa.me/393346582116?text=...",
    "summary": "..."
  },
  "agent": {
    "key": "serenai",
    "name": "SerenAI"
  }
}
```

## Database

Tabelle utilizzate:
- `ai_conversations` — stato conversazione + `structured_data` JSON
- `ai_messages` — transcript
- `ai_leads` — lead qualificati
- `contact_messages` — notifica al personale
- `ai_conversation_reports` — report commerciali (graceful degradation se assente)

Migration opzionale consigliata:

```sql
CREATE TABLE IF NOT EXISTS ai_conversation_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id BIGINT UNSIGNED NOT NULL,
    report_type ENUM('whatsapp_summary','commercial_report','analytics') NOT NULL,
    content LONGTEXT NULL,
    content_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES ai_conversations(id) ON DELETE CASCADE,
    INDEX idx_ai_reports_conversation (conversation_id),
    INDEX idx_ai_reports_type (report_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## File reworkati

**Nuovi servizi:**
- `app/Services/Ai/ConversationSupervisor.php`
- `app/Services/Ai/ConversationMemory.php`
- `app/Services/Ai/AgentSwarmRouter.php`
- `app/Services/Ai/AgentTurnPlanner.php`
- `app/Services/Ai/ResponseComposer.php`
- `app/Services/Ai/ResponseStyleGuard.php`
- `app/Services/Ai/HandoffDecisionEngine.php`
- `app/Services/Ai/CommercialReportBuilder.php`
- `app/Services/Ai/ConversationAnalyticsBuilder.php`
- `app/Services/Ai/ConversationRepair.php`

**Reworkati:**
- `app/Services/Ai/ConciergeOrchestrator.php`
- `app/Services/Ai/NeedClassifier.php`
- `app/Services/Ai/LeadExtractor.php`
- `app/Services/Ai/PromptBuilder.php`
- `app/Services/WhatsApp/WhatsAppHandoffBuilder.php`
- `public/assets/js/ai-concierge.js`
- `app/Views/partials/ai-concierge-widget.php`
- `app/Views/admin/ai-concierge/show-content.php`
- `app/Views/admin/ai-concierge/index-content.php`

## Test

```bash
php scripts/test-ai-concierge-professional-swarm.php
```

Coverage: SerenAI gaming FWA, energia business, correzione, handoff immediato, frasi vietate, classifier, extractor, handoff engine, style guard, report builder, WhatsApp summary.

## Variabili d'ambiente

Vedi `.env.example.php`. Flag rilevanti:

```
AI_CONCIERGE_WHATSAPP_NUMBER=393346582116
AI_CONCIERGE_MAX_MESSAGES=40
AI_CONCIERGE_RATE_LIMIT=12
GEMINI_CONCIERGE_MODEL=gemini-2.0-flash
```
