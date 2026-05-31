# WhatsApp guidato Bisped

Il launcher pubblico si presenta come `WhatsApp Bisped`. Apre una chat interna essenziale, interpreta la richiesta e apre WhatsApp non appena esiste un problema azionabile. Il cliente non deve completare una qualifica commerciale prima di poter scrivere al negozio.

## Flusso

- Il cliente non vede step interni, scelte multiple, card preventivo o raccolta obbligatoria del numero.
- Una richiesta concreta produce un handoff immediato; una richiesta incomprensibile riceve al massimo un chiarimento aperto prima del fallback WhatsApp.
- Il recapito non e obbligatorio: con il click-to-chat e il cliente ad aprire WhatsApp dal proprio account.
- `SarAI` gestisce energia, bollette, pratiche e orientamento iniziale.
- `AndreAI` prende in carico tecnologia, device e assistenza.
- `SerenAI` gestisce fibra, internet, mobile e telefonia.
- Il cambio agente e dichiarato in chat e visibile nell'header.
- La disclosure privacy resta discreta nel footer del widget.
- SarAI applica il metodo: prima capisco come vivi, poi ti consiglio.
- Il sistema genera tre pre-preventivi solo per il negozio, senza esporli nel widget o nel messaggio WhatsApp e senza inventare prezzi, coperture o disponibilita.
- Il link WhatsApp viene creato dal backend e il riepilogo viene salvato anche in `contact_messages`.

## Architettura conversazionale

- `NeedClassifier` usa evidenze pesate e resta prudente sui casi ambigui.
- `LeadExtractor` salva memoria conversazionale e fatti espliciti, comprese le correzioni del cliente.
- `ConversationalAnalyzer` usa Gemini Flash Lite soltanto sui turni ambigui e accetta esclusivamente JSON vincolato.
- `ConciergeStateMachine` non e piu un questionario: una richiesta azionabile passa subito a WhatsApp; una richiesta vaga riceve al massimo un chiarimento aperto.
- `WhatsAppHandoffBuilder` costruisce il testo per inclusione: non mostra valori mancanti e non aggiunge diagnosi o ipotesi.

Il design segue pattern consolidati: slot come memoria, compilazione silenziosa e validata, required slot dinamici e handoff contestuale verso specialisti o umani.

Riferimenti:

- Rasa slots: `https://rasa.com/docs/reference/primitives/slots`
- Rasa forms dinamici: `https://rasa.com/docs/rasa/forms/`
- Microsoft Semantic Kernel handoff orchestration: `https://learn.microsoft.com/en-us/semantic-kernel/frameworks/agent/agent-orchestration/handoff`
- OpenAI Agents SDK handoffs: `https://openai.github.io/openai-agents-python/handoffs/`

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

Il flusso deterministico resta disponibile senza LLM ed evita chiamate esterne quando le evidenze locali sono gia sufficienti. Gemini Flash Lite esegue una sola analisi semantica JSON vincolata esclusivamente sui turni ambigui, ma non decide il workflow, non riscrive le risposte pubbliche e non scrive il riepilogo operativo. Il messaggio WhatsApp include soltanto fatti dichiarati dal cliente.

## Sicurezza

Le richieste mutative usano CSRF e rate limit per sessione. I messaggi sono ripuliti da HTML, limitati in lunghezza e rifiutati in caso di spam evidente. Nel widget non vanno richiesti documenti, IBAN o altri dati sensibili.
