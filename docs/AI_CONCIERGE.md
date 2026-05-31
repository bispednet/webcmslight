# WhatsApp guidato Bisped

Il launcher pubblico si presenta come `WhatsApp Bisped`. Apre una chat interna essenziale, raccoglie i dettagli gia presenti nel messaggio e chiede solo cio che manca. Quando il quadro e sufficiente apre automaticamente WhatsApp con il riepilogo pronto.

## Flusso

- Il cliente non vede step interni, scelte multiple o card preventivo.
- `SarAI` gestisce energia, bollette, pratiche e orientamento iniziale.
- `AndreAI` prende in carico tecnologia, device e assistenza.
- `SerenAI` gestisce fibra, internet, mobile e telefonia.
- Il cambio agente e dichiarato in chat e visibile nell'header.
- La disclosure privacy resta discreta nel footer del widget.
- SarAI applica il metodo: prima capisco come vivi, poi ti consiglio.
- Il sistema genera tre pre-preventivi solo per il negozio, senza esporli nel widget o nel messaggio WhatsApp e senza inventare prezzi, coperture o disponibilita.
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
