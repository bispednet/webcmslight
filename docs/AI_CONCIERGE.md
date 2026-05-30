# SarAI e agenti digitali Bisped

Il concierge pubblico si presenta come `SarAI`, Sara Digitale Bisped. Parte da una richiesta libera, raccoglie i dettagli gia presenti nel messaggio e chiede solo cio che manca. Non e un questionario e non finge di essere una persona umana.

## Flusso

- `SarAI` apre la conversazione e gestisce energia, bollette, pratiche e orientamento iniziale.
- `AndreAI` prende in carico tecnologia, device e assistenza.
- `SerenAI` gestisce fibra, internet, mobile e telefonia.
- Il cambio agente e dichiarato in chat e visibile nell'header.
- La privacy notice precede il riepilogo per il negozio e la raccolta dei dati personali.
- SarAI applica il metodo: prima capisco come vivi, poi ti consiglio.
- Il sistema genera tre pre-preventivi senza inventare prezzi, coperture o disponibilita.
- Il link WhatsApp viene creato dal backend e il riepilogo viene salvato anche in `contact_messages`.

## Endpoint

- `GET /ai/concierge/bootstrap`
- `POST /ai/concierge/message`
- `POST /ai/concierge/choice`
- `POST /ai/concierge/handoff/whatsapp`
- `GET /admin/ai-concierge`

## Setup

```bash
runtime/bin/frankenphp php-cli scripts/migrate-ai-concierge.php
```

Il flusso deterministico resta disponibile senza LLM. Gemini Flash Lite riscrive le risposte libere in tono naturale, ma non puo superare le regole backend o diventare una dipendenza obbligatoria.

## Sicurezza

Le richieste mutative usano CSRF e rate limit per sessione. I messaggi sono ripuliti da HTML, limitati in lunghezza e rifiutati in caso di spam evidente. Nel widget non vanno richiesti documenti, IBAN o altri dati sensibili.
