# Security Assessment

Data ultima revisione: 2026-06-01 (aggiornato per rework Professional Agent Swarm Concierge)

Ambiente valutato: preview `https://solclawn.com`, repo `bispednet/webcmslight`, runtime FrankenPHP + MariaDB locale.

## Executive Summary

Lo stato attuale e idoneo a una preview controllata. Sono stati corretti i problemi piu importanti emersi dall'audit: segreti hardcoded, installer web esposto, logout senza CSRF, login/registrazione senza CSRF, cookie non sempre `Secure` dietro proxy, mancanza di header di sicurezza e schema DB non allineato.

Prima della produzione servono ancora rotazione/validazione dei segreti reali, hardening infrastrutturale e configurazione OAuth/SMTP definitiva.

## Correzioni Applicate

## Correzioni rework Professional Agent Swarm Concierge (2026-06-01)

- Rimosso numero WhatsApp hardcoded `393346582116` dal sorgente `ConciergeOrchestrator.php`; ora richiesto solo da `.env.php` via `whatsapp.phone_number` o `ai_concierge.whatsapp_number`.
- Corretto falso positivo `customer_type=business`: la parola "negozio" nel contesto "porto in negozio" non viene più interpretata come cliente business.
- Confermato: tutte le query DB nel layer AI usano prepared statements PDO.
- Confermato: l'output LLM (Gemini) viene inserito nel DOM tramite `textContent` (non `innerHTML`) — nessun rischio XSS.
- Confermato: il messaggio utente entra nel prompt LLM già sanitizzato da `PromptInjectionGuard` (strip_tags, 1500 char, rimozione blocchi ripetuti).
- Confermato: la colonna `customer_email` è esclusa dall'`$allowed` di `update()` per design — l'email è persistita solo nel JSON `structured_data`.
- Confermato: l'URL WhatsApp è costruito esclusivamente dal backend con `preg_replace('/\D+/', '', $number)` + `rawurlencode($text)` — nessun open redirect.
- Confermato: rate limit (12 req/min), CSRF token, limite messaggi (40), spam check mantenuti nella nuova architettura swarm.
- Nota: `BispedBusinessContext.php` contiene stime di prezzo indicative (es. ~1700€ per Z Fold6). Non è un dato utente né un segreto, ma va aggiornato manualmente se i prezzi cambiano.

## Correzioni precedenti

- Rimossi segreti FTP hardcoded da `scripts/ftp-download-images.php`; ora usa `BISPED_FTP_USER`, `BISPED_FTP_PASS`, `BISPED_FTP_UPLOADS_URL`.
- Disabilitato `public/install.php` di default; richiede `BISPED_ALLOW_WEB_INSTALL=1`.
- Aggiunta verifica CSRF su login e registrazione; il logout POST chiude sempre la sessione anche se il token e scaduto o gia consumato.
- Aggiornato CSRF per supportare piu token concorrenti e consumo one-time.
- Aggiunto rate limit sessione per login e registrazione.
- Aggiunti header `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` e HSTS su HTTPS.
- Cookie sessione `Secure` anche dietro proxy con `X-Forwarded-Proto: https`.
- Rimosso `X-Powered-By` lato applicazione.
- Sanitizzati i valori usati negli header email del form contatti.
- Aggiornato schema DB con `users.password_hash` e `appointment_requests`.
- Aggiornata documentazione per evitare credenziali in comandi copiabili.
- Integrato Gemini direttamente dal CMS PHP con chiave solo in `.env.php`, rate limit persistente e filtro anti-SSRF per acquisizione testi e immagini editoriali.
- Sostituito il concierge dimostrativo con agenti digitali specializzati: flusso persistente, endpoint CSRF, rate limit, limite messaggi, filtro spam e handoff WhatsApp generato dal backend.
- Evoluto il concierge con estrazione locale dei campi e analisi semantica Gemini JSON vincolata solo per i turni ambigui; il modello non riscrive le risposte pubbliche e non decide prezzi, condizioni o accessi ai dati.
- Rimosse dal widget pubblico le scelte multiple, le card preventivo e i passaggi tecnici: quando gli slot minimi sono completi il backend genera il riepilogo e apre automaticamente WhatsApp. Resta disponibile un link di fallback se il browser blocca il popup.
- Eliminata la raccolta obbligatoria del telefono nel click-to-chat: il cliente puo aprire WhatsApp direttamente. Il riepilogo viene costruito per inclusione di soli fatti dichiarati; Gemini contribuisce al routing con JSON vincolato ma non genera il messaggio operativo.
- Corretto il sanitizer HTML editoriale: i frammenti vengono interpretati esplicitamente come UTF-8 e lo script `scripts/repair-blog-encoding.php` bonifica in modo idempotente i contenuti gia contaminati.

## Controlli Eseguiti

- Secret scan sui file tracciabili, escludendo `.env.php`, runtime, storage e piani sensibili ignorati.
- Parser PHP sui file modificati con `token_get_all(..., TOKEN_PARSE)`.
- Verifica `/install.php`: restituisce `404` senza variabile di abilitazione.
- Verifica header HTTPS su `solclawn.com`.
- Smoke test principali su login, pagine inglesi, appuntamenti e admin nelle iterazioni precedenti.
- Smoke test API completo concierge: qualifica TLC gaming/FWA, routing SerenAI, slot filling naturale, apertura WhatsApp automatica, fallback browser, lead DB, record `contact_messages` e URL `wa.me`.
- Verifica rendering articolo Cherokee dopo bonifica: nessun marker mojibake e accenti UTF-8 corretti.

## Rischi Residui

- `.env.php` locale contiene credenziali reali di test/servizi: e ignorato da Git, ma va protetto a filesystem e ruotato prima della produzione se condiviso.
- La chiave Gemini e configurata localmente e non viene tracciata: va ruotata prima della produzione perche condivisa durante il setup.
- Google Calendar non puo sincronizzare finche manca un refresh token OAuth con scope Calendar.
- Google OAuth richiede redirect autorizzati per dominio di preview e produzione.
- Invio email usa ancora `mail()` nel controller contatti; la config SMTP e presente ma serve un mailer SMTP applicativo per affidabilita e audit.
- Tailwind CDN e ancora caricato dai layout: accettabile in preview, da sostituire con build locale prima della produzione.
- Non esiste ancora un sistema completo di password reset/verify email per utenti registrati.
- Alcuni contenuti legacy non pubblici restano nel repo finche non vengono rimossi o archiviati.
- Il concierge salva dati di qualifica: definire la retention definitiva e una procedura admin di cancellazione/esportazione prima della produzione.

## Checklist Pre-Produzione

- Rigenerare `app.key` e password admin/commessi/clienti demo.
- Ruotare credenziali SMTP/OAuth se sono state condivise in chat o ambienti non definitivi.
- Configurare `app.url=https://bisped.net` e redirect OAuth `https://bisped.net/auth/google/callback`.
- Ottenere refresh token Calendar o service account condiviso con calendario aziendale.
- Installare un mailer SMTP applicativo e disabilitare fallback `mail()` non monitorato.
- Eseguire backup DB e smoke test completo dopo deploy.
- Verificare che `public/install.php` resti non accessibile.
- Servire assets frontend senza CDN Tailwind.
- Abilitare monitoraggio log e alert su `/health/db`.
