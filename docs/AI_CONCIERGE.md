# Team AI Bisped

Il concierge pubblico sostituisce il semplice pulsante WhatsApp con un percorso guidato nativo del CMS.

## Flusso

- `AndreAI` gestisce tecnologia, device e assistenza.
- `SerenAI` gestisce fibra, internet, mobile e telefonia.
- `SarAI` gestisce energia, bollette e pratiche.
- Il router gestisce richieste miste e business.
- La privacy notice precede la raccolta dei dati personali.
- Il sistema genera tre percorsi senza inventare prezzi, coperture o disponibilita.
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

Il flusso deterministico resta disponibile senza LLM. Gemini puo essere usato come arricchimento, mai come dipendenza obbligatoria.

## Sicurezza

Le richieste mutative usano CSRF e rate limit per sessione. I messaggi sono ripuliti da HTML, limitati in lunghezza e rifiutati in caso di spam evidente. Nel widget non vanno richiesti documenti, IBAN o altri dati sensibili.
