# Security Assessment

Data: 2026-05-30

Ambiente valutato: preview `https://solclawn.com`, repo `bispednet/webcmslight`, runtime FrankenPHP + MariaDB locale.

## Executive Summary

Lo stato attuale e idoneo a una preview controllata. Sono stati corretti i problemi piu importanti emersi dall'audit: segreti hardcoded, installer web esposto, logout senza CSRF, login/registrazione senza CSRF, cookie non sempre `Secure` dietro proxy, mancanza di header di sicurezza e schema DB non allineato.

Prima della produzione servono ancora rotazione/validazione dei segreti reali, hardening infrastrutturale e configurazione OAuth/SMTP definitiva.

## Correzioni Applicate

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
- Aggiunto concierge WhatsApp con endpoint CSRF, cronologia limitata, rate limit di sessione e profilo Gemini separato.

## Controlli Eseguiti

- Secret scan sui file tracciabili, escludendo `.env.php`, runtime, storage e piani sensibili ignorati.
- Parser PHP sui file modificati con `token_get_all(..., TOKEN_PARSE)`.
- Verifica `/install.php`: restituisce `404` senza variabile di abilitazione.
- Verifica header HTTPS su `solclawn.com`.
- Smoke test principali su login, pagine inglesi, appuntamenti e admin nelle iterazioni precedenti.

## Rischi Residui

- `.env.php` locale contiene credenziali reali di test/servizi: e ignorato da Git, ma va protetto a filesystem e ruotato prima della produzione se condiviso.
- La chiave Gemini e configurata localmente e non viene tracciata: va ruotata prima della produzione perche condivisa durante il setup.
- Google Calendar non puo sincronizzare finche manca un refresh token OAuth con scope Calendar.
- Google OAuth richiede redirect autorizzati per dominio di preview e produzione.
- Invio email usa ancora `mail()` nel controller contatti; la config SMTP e presente ma serve un mailer SMTP applicativo per affidabilita e audit.
- Tailwind CDN e ancora caricato dai layout: accettabile in preview, da sostituire con build locale prima della produzione.
- Non esiste ancora un sistema completo di password reset/verify email per utenti registrati.
- Alcuni contenuti legacy non pubblici restano nel repo finche non vengono rimossi o archiviati.

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
