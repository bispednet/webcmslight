# Bisped.net WebCMSLight

Custom CMS PHP/MariaDB per il rework commerciale di `bisped.net`, pensato per sostituire WordPress/WooCommerce solo dopo validazione completa in preview.

## Stato

- Base PHP/MySQL server-rendered con document root in `public/`.
- Pagine pubbliche Bisped: `/`, `/azienda`, `/servizi`, `/sostenibilita`, `/contatti`, `/appuntamenti`, `/products`, `/blog`, `/faq`, `/legal`.
- Route statiche inglesi per preview: `/en`, `/en/company`, `/en/services`, `/en/contact`, `/en/appointments`, `/en/legal`, `/en/find-us`, `/en/sustainability`, `/en/faq`.
- Admin: prodotti, blog, media, impostazioni, ingest, messaggi contatto e appuntamenti.
- Auth: password locale, Google OAuth, wallet EVM/Solana; ruoli `admin`, `commesso`, `cliente`.
- Agenda: richieste appuntamento pubbliche e accettazione admin; sync Google Calendar pronta, attiva solo dopo refresh token OAuth.
- Ingest: job giornaliero per news/offerte con immagini e deduplica.
- Seed iniziale per impostazioni, navigazione, prodotti, FAQ, blog, team e testi legali.
- Asset recuperati in sola lettura da FTP in `public/media/bisped/`.
- Form contatti e appuntamenti con CSRF, honeypot/rate-limit dove applicabile.
- Team AI Bisped: concierge nativo con routing AndreAI/SerenAI/SarAI, qualifica guidata, tre percorsi e handoff WhatsApp persistente.
- Audit migrazione in `docs/BISPED_MIGRATION_AUDIT.md`.
- Security assessment in `docs/SECURITY_ASSESSMENT.md`.
- Runtime locale portabile in `runtime/` con FrankenPHP, MariaDB e Playwright. La cartella e esclusa da Git.

## Sorgenti legacy

- Dump SQL corretto: `/home/funboy/uu4c5pdm_wpb.sql`, importato in `bisped_wp_legacy`.
- Dump SQL precedente: `/home/funboy/old_bisped.net.db.sql`, considerato ambiente test/demo.
- Produzione FTP: usare solo in lettura per analisi e recupero asset.
- Il cron editoriale e l'ingest offerte sono autonomi nel CMS PHP. CopilotRM non e una dipendenza runtime del sito.
- Il concierge WhatsApp usa un profilo Gemini Flash Lite separato e prepara il passaggio alla chat umana con le informazioni raccolte.

## Setup locale

```bash
cp .env.example.php .env.php
```

Poi configurare database MySQL, URL, chiave applicazione e wallet admin in `.env.php`.

```bash
mysql -u bisped_user -p bisped_net < database/schema.sql
runtime/bin/frankenphp php-cli scripts/migrate-ai-concierge.php
```

L'installer web `public/install.php` e disabilitato di default per sicurezza. Abilitarlo solo in setup controllato con `BISPED_ALLOW_WEB_INSTALL=1`, poi rimuovere subito l'accesso.

## Note operative

- Non committare `.env.php`, dump SQL, backup, log, credenziali FTP/SMTP/OAuth o dati cliente.
- Non modificare la produzione WordPress via FTP durante lo sviluppo.
- Il dump `uu4c5pdm_wpb.sql` contiene 116 tabelle importate, WooCommerce 10.7.0, 66 prodotti pubblicati e ordini legacy.
- Il catalogo preview e amministrabile dal pannello `/admin/products`.
- Il tunnel di preview usa `https://solclawn.com`, gia configurato via cloudflared verso `127.0.0.1:4000`.
- Il deploy produzione richiede aggiornamento di `.env.php`, redirect OAuth Google, SMTP, DNS e refresh token Calendar se si vuole il sync automatico.
- I file locali `BISPED_*_PLAN*.md` e `BISPED_HANDOFF.md` sono note operative escluse dal repository.

## Runtime preview attuale

Avvio MariaDB locale:

```bash
cd /home/funboy/bisped.net
runtime/mariadb/bin/mariadbd \
  --basedir=/home/funboy/bisped.net/runtime/mariadb \
  --datadir=/home/funboy/bisped.net/runtime/mariadb-data \
  --socket=/home/funboy/bisped.net/runtime/mariadb.sock \
  --port=3307 \
  --pid-file=/home/funboy/bisped.net/runtime/mariadb.pid \
  --skip-networking=0 \
  --bind-address=127.0.0.1
```

Avvio sito sul tunnel `solclawn.com`:

```bash
cd /home/funboy/bisped.net
runtime/bin/frankenphp php-server \
  --root /home/funboy/bisped.net/public \
  --listen 127.0.0.1:4000 \
  --access-log >> runtime/frankenphp.log 2>&1
```

Test Playwright:

```bash
runtime/venv/bin/python runtime/playwright_check.py
runtime/venv/bin/python runtime/playwright_mobile_links.py
```

Security check rapido:

```bash
rg -n "REAL_SECRET_PREFIX|FTP_PASSWORD|OAUTH_SECRET|SMTP_PASSWORD|TEMP_ADMIN_PASSWORD" \
  --glob '!runtime/**' --glob '!storage/**' --glob '!.env.php'
curl -I https://solclawn.com/
curl -s -o /tmp/install -w '%{http_code}\n' https://solclawn.com/install.php
```
