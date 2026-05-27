# bisped.net — Handoff tecnico completo
**Data**: 27 maggio 2026  
**Branch**: `rework/full-foundation-v3`  
**Repo**: `git@github-bispednet:bispednet/webcmslight`  
**Tunnel locale**: `https://solclawn.com` → `127.0.0.1:4000`  
**Dominio finale**: `https://bisped.net`

---

## 1. Cosa è stato fatto

### Stack tecnico installato e funzionante
| Componente | Dettaglio |
|---|---|
| Runtime PHP | FrankenPHP 8.5.6 — `runtime/bin/frankenphp` |
| Database | MariaDB portabile `runtime/mariadb`, porta **3307**, DB `bisped_net`, utente `bisped_user` |
| Web server | FrankenPHP serve su `127.0.0.1:4000`, reverse proxy via nginx/tunnel verso solclawn.com |
| CLI PHP | `runtime/bin/frankenphp php-cli <script>` |
| Config secrets | `.env.php` (in .gitignore) — NON committare mai |

### Architettura CMS custom (PHP MVC)
```
public/
  index.php       → router principale (pagine pubbliche)
  admin.php       → router admin (protetto da sessione admin)
  assets/css/     → app.css (design system CSS custom props + Tailwind CDN)
app/
  bootstrap.php   → autoload PSR-4 + container + DB
  Controllers/
    PageController.php          → tutte le pagine pubbliche
    ContactController.php       → form contatti + email
    AuthController.php          → login admin
    Admin/
      DashboardController.php
      ProductsController.php    → CRUD prodotti
      PostsController.php       → CRUD blog
      SettingsController.php    → impostazioni sito
      IngestController.php      → pannello auto-update
      [+ 12 altri controller admin]
  Views/
    layouts/main.php            → layout pubblico
    layouts/admin.php           → layout admin
    public/
      home-content.php          → homepage
      azienda-content.php       → chi siamo
      servizi-content.php       → servizi
      dove-content.php          → dove siamo + mappa + recensioni
      products-content.php      → catalogo con live search + filtri
      product-content.php       → scheda prodotto singola
      blog-content.php          → lista articoli
      blog-post-content.php     → articolo + prodotti correlati dinamici
      teleassistenza-content.php → teleassistenza con form ticket
      contact-content.php       → form contatti
  Services/Security/Csrf.php    → token CSRF
  Support/Sanitizer.php         → sanitizzazione input
  Support/Validator.php         → validazione campi
scripts/
  seed-products-modern.php      → seed 21 prodotti 2025-2026
  seed-blog-posts.php           → seed 10 articoli blog
  download-product-images.sh    → download immagini (prima versione)
  retry-product-images.sh       → retry con URL corretti
  update-product-images.php     → aggiorna image_url nel DB
  auto-update/
    ingest.php                  → sistema di ingest automatico
    sources.json                → registro 29 fonti RSS/API/scrape
    cron-setup.sh               → installa cron giornaliero
```

### Pagine pubbliche attive (tutte 200 OK)
| URL | Contenuto |
|---|---|
| `/` | Homepage con hero, features, prodotti in evidenza, coupon BISPED10, newsletter |
| `/azienda` o `/chi-siamo` | Chi siamo, 3 pilastri, foto negozio con cache-bust |
| `/servizi` | Servizi offerti |
| `/dove-siamo` | Mappa Google, orari, contatti separati (fisso vs WA), Google Reviews |
| `/products` | Catalogo 21 prodotti — live search JS + filtro categoria + promo strip |
| `/products/{slug}` | Scheda prodotto singola |
| `/blog` | Lista 10 articoli |
| `/blog/{slug}` | Articolo + prodotti correlati dinamici via tag |
| `/teleassistenza` | Come funziona + tariffe + form ticket |
| `/contatti` | Form contatti con CSRF + honeypot + rate-limit |
| `/faq` | FAQ |
| `/sitemap.xml` | Sitemap dinamica (41 URL: 10 statiche + 21 prodotti + 10 post) |
| `/robots.txt` | Disallow: /admin/, /auth/, /api/, /storage/ |

### Pannello admin (autenticazione richiesta)
- URL: `/admin/dashboard`
- CRUD completo: Prodotti, Blog Post, Impostazioni, Agenti, Partner, FAQ, Navigation, Media, Settings
- **Auto-Update** (ingest): `/admin/ingest` — trigger manuale, log recente, lista fonti

### Dati nel DB
| Tabella | Contenuto |
|---|---|
| `products` | 21 prodotti 2025-2026, tutti con `image_url` impostata |
| `blog_posts` | 10 articoli SEO ottimizzati in italiano |
| `contact_messages` | 7 messaggi da form |
| `settings` | ~20 chiavi: nome sito, SEO, hero, logo, immagini |
| `ingest_log` | vuota (nessun ingest ancora eseguito) |
| `ingest_sources` | vuota (il sistema legge da `sources.json`, non dal DB) |

### Prodotti caricati (21 totali)
**Smartphone (7)**: Samsung Galaxy S25, iPhone 16, Xiaomi 14T Pro, Samsung Galaxy A55 5G, Google Pixel 9, + AirPods Pro 2, Galaxy Watch 7 *(vedi nota categoria sotto)*

**Informatica (6)**: MacBook Air M3, ASUS VivoBook 16X OLED, Lenovo IdeaPad 5, Mini PC N100, Sony WH-1000XM5, Galaxy Tab S9 FE

**Gaming (6)**: ASUS TUF Gaming A15, Lenovo LOQ 15, Samsung Odyssey G5, Logitech G Pro X Superlight 2, HyperX Cloud Alpha Wireless, Corsair K70 RGB

**Connettività (2)**: TP-Link Archer AXE75, Fritz!Box 7590 AX

### Immagini prodotto
Tutte le 21 immagini sono scaricate in `public/media/products/{slug}.jpg` e l'URL è aggiornato nel DB. Fonti usate:
- GSMArena CDN (`fdn2.gsmarena.com/vv/bigpic/`) per smartphone e wearable
- NotebookCheck (`notebookcheck.net/uploads/` e `fileadmin/_processed_/`) per notebook
- CDN ufficiali per accessori: Corsair, HyperX (Shopify), Fritz (Shopify), TP-Link Static, Logitech G (Cloudinary), SoundGuys WordPress CDN per Sony WH-1000XM5, GMKtec Shopify per Mini PC

### Contatti corretti (verificati)
- **Telefono negozio**: `+39 0565 31136` → `tel:+390565311136`
- **WhatsApp**: `+39 334 658 2116` → `https://wa.me/393346582116`
- **Email pubblica**: `negozio@bisped.net` (non `info@bisped.net` che è solo B2B/fornitori)
- **Indirizzo**: `Piazza della Costituzione, 68 — 57025 Piombino (LI)`
- **Mappa**: Google Maps embed punta all'indirizzo corretto; CTA "Raggiungi il negozio con Google Maps" presente

---

## 2. Come funziona il sistema di ingest automatico

### Obiettivo
Aggiornare automaticamente il catalogo blog con notizie e offerte dei brand partner (Samsung, Apple, TIM, Fastweb, ARERA energia, ecc.) usando RSS feeds, API open data e scrape.

### File chiave
- `scripts/auto-update/sources.json` — registro delle 29 fonti
- `scripts/auto-update/ingest.php` — script PHP di ingest
- `scripts/auto-update/cron-setup.sh` — installa il cron
- `app/Controllers/Admin/IngestController.php` — pannello web admin
- Tabelle DB: `ingest_log`, `ingest_sources`

### Come funziona `ingest.php`
```
1. Legge sources.json (o argomento --source=categoria)
2. Per ogni fonte:
   - RSS → fetch XML con SimpleXML, filtra per keywords
   - API open data → es. ARERA CSV/XML delle offerte energia
   - scrape → HTML fetch + regex semplici
3. Per ogni item trovato:
   a. Se Ollama (localhost:11434) è disponibile:
      → LLM (llama3.2) genera un articolo HTML completo in italiano
   b. Se Ollama non disponibile:
      → fallback: articolo minimal HTML con titolo, snippet, link fonte
4. INSERT in blog_posts (ON DUPLICATE KEY UPDATE su slug)
5. Log in ingest_log
```

### Opzioni CLI
```bash
# Tutti i feed (solo informatica e smartphone di default)
php scripts/auto-update/ingest.php --all

# Solo una categoria
php scripts/auto-update/ingest.php --source=energia

# Dry run (non scrive nel DB)
php scripts/auto-update/ingest.php --all --dry-run --verbose
```

### Cron installazione
```bash
bash scripts/auto-update/cron-setup.sh
# Installa: 0 6 * * * php /home/funboy/bisped.net/scripts/auto-update/ingest.php --all
# Log: storage/ingest-cron.log
```

### Trigger manuale via admin
`/admin/ingest` → seleziona categoria → "Avvia ingest" → esegue in background via `exec()`.

### Dipendenza LLM (Ollama)
- **Locale**: Ollama deve girare su `localhost:11434` con `llama3.2` scaricato
- **Produzione**: se Ollama non è disponibile, il sistema usa un template fallback. Per qualità migliore in produzione, configurare LLM_FALLBACK_PROVIDER in `.env.php` o puntare all'API OpenAI/Anthropic/DeepSeek via CopilotRM (`/home/funboy/copilotrm` porta 4010, endpoint `POST /api/ingest/rss/sync`)

### Integrazione CopilotRM (opzionale)
In `ingest.php` è presente `copilotrm_rss_sync()` che chiama `http://localhost:4010/api/ingest/rss/sync`. CopilotRM (`/home/funboy/copilotrm`) ha già integrati:
- `packages/integrations-energy` → ARERA Portale Offerte + stub Enel/Iren/Enegan/etc.
- `packages/integrations-telco` → AGCOM comparatore + stub TIM/Fastweb/Vodafone/WindTre/Iliad
- `packages/integrations-rss` → RSS sync reale
- LLM agent (`packages/agents-content`) per generazione articoli
Se CopilotRM è attivo, usarlo come backend ingest è la soluzione più potente.

---

## 3. Bug noti e correzioni immediate da fare

### Priorità ALTA — da fare prima del go-live

| # | Problema | File | Fix |
|---|---|---|---|
| 1 | `contact_email` nel DB è ancora `info@bisped.net` | DB `settings` | `UPDATE settings SET setting_value='negozio@bisped.net' WHERE setting_key='contact_email'` |
| 2 | `business_telegram` nel DB è `mailto:info@bisped.net` | DB `settings` | Aggiornare con link WA o Telegram reale |
| 3 | `app.env = 'local'` e `app.debug = true` | `.env.php` | Cambiare in produzione: `'env' => 'production', 'debug' => false` |
| 4 | `app.url = 'https://solclawn.com'` | `.env.php` | Cambiare in `'url' => 'https://bisped.net'` |
| 5 | PHP `mail()` non funziona su molti VPS | `ContactController.php` | Configurare SMTP reale (Brevo/Mailgun/Gmail SMTP) via PHPMailer o `sendmail_path` in php.ini |
| 6 | Cron ingest non installato | server | Eseguire `bash scripts/auto-update/cron-setup.sh` |
| 7 | Admin panel non ha vista messaggi | nessuna | Creare `Admin/MessagesController.php` + route + view per leggere `contact_messages` |

### Priorità MEDIA — migliorie consigliate

| # | Problema | Fix |
|---|---|---|
| 8 | Categorie prodotti non coerenti con filtri UI | Sony WH-1000XM5 è in "Informatica" ma dovrebbe essere "Audio"; Galaxy Watch e AirPods Pro sono in "Smartphone" ma dovrebbero essere "Wearable"/"Audio". Aggiungere categorie o rinominare. |
| 9 | Immagini prodotto non ottimizzate | Sony WH-1000XM5 è 868KB. Installare `imagemagick` e aggiungere pipeline di resize (es. 600x600 max) — uno script `scripts/optimize-images.sh` con `mogrify -resize 800x800\> -quality 80` |
| 10 | Foto negozio non aggiornata in produzione | `public/media/bisped/fronte_negozio_bisped.png` è la foto aggiornata localmente. Al deploy su bisped.net, uploadare la stessa foto tramite FTP o admin media |
| 11 | Google Business Place ID reale | In `dove-content.php` le recensioni puntano a ricerca Google Maps generica. Recuperare il Place ID reale dalla Google Business Console e usare link tipo `https://g.page/r/{PLACE_ID}/review` |
| 12 | Coupon BISPED10 decorativo | Nella homepage il coupon è solo visuale. Se si vuole renderlo funzionale, aggiungere tabella `coupons` nel DB e logica di validazione |
| 13 | Newsletter senza storage lista | Il form newsletter POSTs a `/contatti` con `topic=newsletter`. Non c'è export della lista. Valutare Brevo/Mailchimp integration o tabella `newsletter_subscribers` |
| 14 | `ingest_sources` table vuota | Il sistema legge da `sources.json`, non dal DB. Per gestione via admin, popolare la tabella e aggiornare `ingest.php` a leggere dal DB con fallback a file |
| 15 | File immagini legacy in products/ | `public/media/products/` contiene ancora le immagini dei vecchi prodotti WooCommerce (OPPO, Samsung vecchi modelli, etc.) — possono essere eliminati o archiviati |
| 16 | Nessun carrello / checkout | Il sito è un catalogo con lead generation (form + WhatsApp). Se si vuole e-commerce vero, serve carrello + Stripe/PayPal. Non è richiesto nell'attuale scope. |

### Priorità BASSA — future improvements

| # | Idea |
|---|---|
| 17 | **Admin vista contatti**: tabella messaggi, stato (new/read/archived), risposta via email |
| 18 | **Prodotti varianti**: colori/storage per smartphone (es. iPhone 16 in 128/256/512GB) |
| 19 | **Redirect legacy**: gli URL del vecchio WordPress (es. `/prodotto/nome`) non reindirizzano a `/products/slug`. Aggiungere tabella redirects + middleware |
| 20 | **Cache HTML**: per pagine ad alto traffico (home, products), aggiungere micro-cache su file system o Varnish |
| 21 | **CDN immagini proprie**: scaricare le immagini dai CDN di terzi (GSMArena, NotebookCheck) e servirle dal proprio dominio è più stabile ma soggetto a copyright. Valutare se usare immagini ufficiali dei produttori o scattare le proprie |
| 22 | **Test automatizzati**: Playwright è già installato in `runtime/venv`. Aggiungere test E2E per form contatti, ricerca prodotti, blog |
| 23 | **Ingest qualità**: la generazione LLM con llama3.2 è buona ma non eccellente. Passare a GPT-4o-mini o Claude Haiku via CopilotRM per articoli di qualità superiore |
| 24 | **Offerte connettività/energia**: le fonti scrape TIM/Fastweb/Enel sono stub. Implementare parser HTML reali o usare i servizi già in CopilotRM |

---

## 4. Come deployare in produzione su bisped.net

### Prerequisiti server
- PHP 8.2+ (preferibilmente FrankenPHP o PHP-FPM + nginx)
- MariaDB o MySQL 8.0+
- Accesso SSH
- Dominio `bisped.net` con DNS puntato al server

### Passi deploy
```bash
# 1. Clone repo
git clone git@github-bispednet:bispednet/webcmslight bisped.net
cd bisped.net
git checkout rework/full-foundation-v3

# 2. Copia .env.php dalla macchina locale (NON è nel repo)
# Aggiornare:
# - app.url = 'https://bisped.net'
# - app.env = 'production'
# - app.debug = false
# - database.host, port, username, password
# - admin_emails = ['negozio@bisped.net']

# 3. Importa schema DB
mysql -u user -p bisped_net < database/schema.sql

# 4. Esegui seed
php scripts/seed-settings-bisped.php
php scripts/seed-products-modern.php
php scripts/seed-blog-posts.php

# 5. Aggiorna DB settings corretti
mysql -u user -p bisped_net -e "
  UPDATE settings SET setting_value='negozio@bisped.net' WHERE setting_key='contact_email';
  UPDATE settings SET setting_value='https://wa.me/393346582116' WHERE setting_key='business_telegram';
"

# 6. Upload immagini prodotto
# (già nel repo sotto public/media/products/)

# 7. Upload foto negozio (NON è nel repo — file privato)
# scp public/media/bisped/fronte_negozio_bisped.png server:/path/public/media/bisped/

# 8. Installa cron ingest
bash scripts/auto-update/cron-setup.sh

# 9. Configura nginx + TLS (Let's Encrypt)
# O usa FrankenPHP con auto-TLS: frankenphp run --config Caddyfile

# 10. Crea admin user
# /admin/setup se route esiste, oppure INSERT diretto nel DB
```

### .env.php minimo per produzione
```php
return [
  'app' => [
    'env' => 'production',
    'debug' => false,
    'url' => 'https://bisped.net',
    'timezone' => 'Europe/Rome',
    'session_name' => 'bisped_session',
  ],
  'database' => [
    'host' => '127.0.0.1',
    'port' => 3306,   // porta standard in produzione
    'database' => 'bisped_net',
    'username' => 'bisped_user',
    'password' => 'YOUR_STRONG_PASSWORD',
  ],
  'admin_emails' => ['negozio@bisped.net'],
];
```

---

## 5. Cosa migliorerei (priorità di sviluppo)

### Se ho 1 settimana
1. **Admin messaggi contatti** — è assurdo non poter leggere le email ricevute senza accedere al DB
2. **SMTP configurato** — PHP `mail()` è inaffidabile in VPS. 20 minuti con Brevo (free tier: 300 email/giorno)
3. **Fix categorie prodotti** — rinominare Sony WH-1000XM5 in "Audio", Galaxy Watch e AirPods in "Wearable"
4. **Fix settings DB** — contact_email e business_telegram

### Se ho 1 mese
5. **Ingest reale funzionante** — collegare CopilotRM come backend LLM + parser HTML per TIM/Fastweb/Enel
6. **Image optimization pipeline** — resize/compress automatico al download/upload
7. **Varianti prodotto** — SKU per storage/colore, indispensabile per smartphone
8. **Google Business Place ID** — link diretto alle recensioni reali
9. **Carrello WhatsApp** — non serve un checkout completo, ma almeno un "aggiungi al carrello" che genera un messaggio WhatsApp precompilato con i prodotti scelti
10. **Redirect legacy** — per SEO, le vecchie URL WordPress

### Se ho 3 mesi
11. **E-commerce leggero** — carrello + Stripe (pagamento carta) per i prodotti del catalogo
12. **CRM integration** — collega i messaggi di contatto a CopilotRM come lead, gestisci follow-up via agenti AI
13. **Automazione offerte operatori** — parser reali per TIM, Fastweb Vodafone, Iliad, Wind3 → blog post automatici sulle offerte del mese
14. **Automazione offerte energia** — ARERA open data già parseable → confronto tariffe mensile automatico
15. **SEO tecnico** — aggiungere JSON-LD structured data per Product, LocalBusiness, BreadcrumbList

---

## 6. Comandi utili

```bash
# Avviare il server locale
/home/funboy/bisped.net/runtime/bin/frankenphp run --config /home/funboy/bisped.net/Caddyfile

# CLI PHP
/home/funboy/bisped.net/runtime/bin/frankenphp php-cli script.php

# MariaDB CLI
/home/funboy/bisped.net/runtime/mariadb/bin/mariadb \
  -u bisped_user -pREDACTED_LOCAL_DB_PASSWORD -h 127.0.0.1 -P 3307 bisped_net

# Git push (SSH alias configurato)
git push git@github-bispednet:bispednet/webcmslight rework/full-foundation-v3

# Ingest manuale (verbose)
/home/funboy/bisped.net/runtime/bin/frankenphp php-cli \
  scripts/auto-update/ingest.php --all --verbose

# Aggiornare immagini prodotto dopo nuovi download
/home/funboy/bisped.net/runtime/bin/frankenphp php-cli \
  scripts/update-product-images.php

# Test pagine (tunnel locale)
curl -I https://solclawn.com/products
```

---

## 7. Stato attuale — checklist go-live

| Item | Stato |
|---|---|
| ✅ CMS custom PHP funzionante | FATTO |
| ✅ Design system dark/light, mobile-first | FATTO |
| ✅ 21 prodotti 2025-2026 con immagini e prezzi | FATTO |
| ✅ 10 articoli blog SEO ottimizzati | FATTO |
| ✅ Blog ↔ prodotti correlati dinamici | FATTO |
| ✅ Form contatti CSRF + honeypot + rate-limit + DB | FATTO |
| ✅ Sitemap.xml + robots.txt | FATTO |
| ✅ Teleassistenza + ticket form | FATTO |
| ✅ Dove siamo: mappa + orari + recensioni | FATTO |
| ✅ Dati contatto corretti (tel/WA/email/indirizzo) | FATTO |
| ✅ Cache-bust foto negozio | FATTO |
| ✅ Live search + filtri categorie prodotti | FATTO |
| ✅ Coupon banner + newsletter section homepage | FATTO |
| ✅ Admin CRUD prodotti/blog/settings | FATTO |
| ✅ Auto-update ingest system (script + admin UI) | FATTO |
| ✅ robots.txt + sitemap.xml | FATTO |
| ⚠️ `.env.php` debug=true, url=solclawn.com | DA AGGIORNARE |
| ⚠️ `contact_email` in DB è `info@bisped.net` | DA AGGIORNARE |
| ⚠️ PHP `mail()` senza SMTP configurato | DA CONFIGURARE |
| ⚠️ Cron ingest non installato | DA INSTALLARE |
| ⚠️ DNS non punta ancora a bisped.net | DA FARE |
| ❌ Admin vista messaggi contatti | MANCANTE |
| ❌ Categorie prodotti non coerenti (Audio/Wearable) | MANCANTE |
| ❌ Google Business Place ID reale | MANCANTE |
| ❌ Carrello / checkout | NON IN SCOPE (solo catalogo + lead gen) |

---

*Documento generato il 27 maggio 2026. Aggiornare ad ogni sprint.*
