# BISPED.NET REWORK COMPLETO
## Piano tecnico operativo per migrazione WordPress/WooCommerce verso custom CMS PHP/MySQL basato su AIRewebCMS + integrazione CopilotRM

Versione: 2.0  
Data: 26 maggio 2026  
Target progetto: `/home/funboy/bisped.net`  
Dump sorgente corretto: `/home/funboy/uu4c5pdm_wpb.sql`  
Dump precedente/test: `/home/funboy/old_bisped.net.db.sql`  
CRM esistente: `/home/funboy/copilotrm`  
Base CMS pubblica: `https://github.com/0xfunboy/AIRewebCMS`  
Dominio finale: `https://www.bisped.net`

---

## Stato esecuzione locale - 26 maggio 2026

Avanzamento iniziale completato in `/home/funboy/bisped.net`:

- AIRewebCMS copiato come base tecnica locale e repository Git inizializzato su branch `bisped-rework`.
- `.gitignore` aggiornato per escludere secret, dump, backup, log, storage runtime e `_migration.local`.
- Dump `/home/funboy/old_bisped.net.db.sql` analizzato in sola lettura con artefatti in `_migration.local`.
- FTP produzione ispezionato esclusivamente in lettura.
- Asset Bisped recuperati da FTP in `public/media/bisped/`.
- Pagine pubbliche create/adattate: `/`, `/azienda`, `/servizi`, `/sostenibilita`, `/contatti`, `/products`, `/blog`, `/faq`, `/legal`.
- Seed iniziale Bisped creato in `database/seed-data.php`.
- Form contatti adattato con CSRF, honeypot e rate-limit sessione.
- Audit tecnico scritto in `docs/BISPED_MIGRATION_AUDIT.md`.

Nota importante: il dump SQL locale appare come ambiente test/demo Techfly e non contiene il catalogo WooCommerce completo rilevato nella produzione FTP. Per completare la migrazione ecommerce servira un export DB aggiornato della produzione o accesso DB read-only.

Aggiornamento successivo:

- Runtime PHP installato localmente con FrankenPHP 8.5.6 in `runtime/bin/frankenphp`.
- MariaDB portabile installato in `runtime/mariadb`, attivo su `127.0.0.1:3307`.
- Dump corretto `/home/funboy/uu4c5pdm_wpb.sql` importato in `bisped_wp_legacy`.
- Database CMS locale `bisped_net` creato e popolato.
- Dati reali rilevati: 116 tabelle, prefisso `wpb_`, tema `rehub-theme`, WooCommerce 10.7.0, 66 prodotti pubblicati, 53 pagine pubblicate, 166 post pubblicati.
- Prima tranche catalogo: 36 prodotti WooCommerce importati nel seed CMS.
- Sito avviato sul tunnel `https://solclawn.com` tramite servizio locale `127.0.0.1:4000`.
- Playwright installato in `runtime/venv` e test browser completati con successo su desktop e mobile.

---

## 0. Obiettivo reale

Questo progetto non è una semplice migrazione WordPress.

L’obiettivo è ricostruire Bisped.net come sito custom, leggero, veloce, moderno, amministrabile e integrato con CopilotRM, mantenendo tutto ciò che ha valore dal vecchio WordPress/WooCommerce:

- contenuti frontend
- pagine
- slug
- template logici
- immagini e media utili
- prodotti
- categorie prodotto
- prezzi
- attributi
- tasse
- spedizioni
- pagamenti
- ordini o richieste rilevanti, se presenti
- news
- blog
- novità
- SEO metadata
- redirect
- policy legali
- cookie policy
- configurazioni e logiche importanti dei plugin
- configurazioni antispam e sicurezza, tradotte in componenti custom
- import/export amministrativi utili
- tutto ciò che serve a far funzionare ecommerce, lead generation e assistenza

Deve invece essere eliminata tutta la zavorra WordPress:

- runtime WordPress
- plugin installati come codice eseguibile
- temi WordPress
- shortcode non più necessari
- transients
- cache
- action scheduler
- sessioni vecchie
- revisioni inutili
- spam
- commenti non utili
- opzioni obsolete
- dati tecnici del CMS vecchio
- dipendenze inutili
- bloat grafico o JS inutile
- tabelle duplicate o non più rilevanti

Il risultato deve essere un sito custom PHP/MySQL, non un WordPress alleggerito.

---

## 1. Decisione architetturale

### 1.1 Base consigliata

Usare AIRewebCMS come base tecnica.

Motivo:

AIRewebCMS è già un CMS PHP/MySQL leggero, server-rendered, con dashboard admin, inline editing, media library, schema SQL, seed data, service layer, public templates, gestione contenuti modulari e struttura già adatta a essere adattata a un sito business.

Non partire da zero salvo blocchi tecnici gravi.

### 1.2 Repo pubblico o repo privato

Non sviluppare direttamente su branch pubblico di AIRewebCMS.

Strategia consigliata:

- usare AIRewebCMS come base iniziale
- copiare o clonare il codice in `/home/funboy/bisped.net`
- inizializzare un nuovo repo privato per Bisped
- mantenere eventualmente `upstream` verso AIRewebCMS solo per riferimento
- non pushare mai dump SQL, password, secret, token, credenziali SMTP, chiavi API, dati clienti, dati ordini o dati CRM

Nota importante:

Anche se il repo finale sarà privato, i secret non vanno committati.  
Il repo privato non è un password manager.  
I secret devono stare in `.env.php` o `.env.local.php` sul server, esclusi da Git.  
Nel repo deve esistere solo `.env.example.php` con placeholder.

### 1.3 Strategia Git consigliata

Opzione migliore:

```bash
cd /home/funboy

git clone https://github.com/0xfunboy/AIRewebCMS /tmp/AIRewebCMS-bisped-base

rsync -a \
  --exclude='.git' \
  --exclude='.env.php' \
  --exclude='storage/logs/*' \
  /tmp/AIRewebCMS-bisped-base/ \
  /home/funboy/bisped.net/

cd /home/funboy/bisped.net

git init
git checkout -b bisped-rework
```

Poi creare un repo privato, per esempio `0xfunboy/bisped.net`, e collegarlo:

```bash
git remote add origin git@github.com:0xfunboy/bisped.net.git
```

Prima del primo commit verificare `.gitignore`.

`.gitignore` minimo obbligatorio:

```gitignore
.env.php
.env.*.php
*.env
*.sql
*.sql.gz
*.dump
*.bak
*.backup
/storage/logs/*
/storage/cache/*
/storage/sessions/*
/storage/tmp/*
/storage/imports/*
/_migration.local/
/node_modules/
/vendor/
.DS_Store
```

Se AIRewebCMS contiene già `.env.php` tracciato da Git, rimuoverlo immediatamente dall’indice:

```bash
git rm --cached .env.php 2>/dev/null || true
rm -f .env.php
cp .env.example.php .env.php
```

---

## 2. Struttura server reale

### 2.1 Percorsi dati

Il developer agent deve lavorare con questi path reali:

```text
/home/funboy/bisped.net
/home/funboy/old_bisped.net.db.sql
/home/funboy/copilotrm
```

### 2.2 Regola operativa

Non spostare e non modificare direttamente:

```text
/home/funboy/old_bisped.net.db.sql
/home/funboy/copilotrm
```

Il dump WordPress è sorgente read-only.

CopilotRM è sorgente da ispezionare e integrare, non da rifare o sovrascrivere.

Tutte le operazioni devono essere fatte dentro:

```text
/home/funboy/bisped.net
```

Per analisi, import temporanei e mapping usare:

```text
/home/funboy/bisped.net/_migration.local
```

Questa cartella deve restare esclusa da Git.

---

## 3. Filosofia del rework

### 3.1 Cosa deve diventare Bisped.net

Bisped.net deve diventare un sito business moderno per azienda IT/TLC, con:

- identità visiva rosso, nero, bianco
- tono professionale, tecnico, concreto
- performance alta
- contenuti gestibili
- catalogo prodotti gestibile
- ecommerce leggero
- richiesta preventivo
- richiesta assistenza
- teleassistenza
- news/blog
- backend admin semplice
- integrazione diretta con CopilotRM
- SEO preservata
- slugs preservati
- frontend moderno ma non artificiale
- grafica aggiornata 2025/2026
- componenti custom al posto dei plugin WordPress

### 3.2 Cosa non deve diventare

Non deve diventare:

- un clone estetico vecchio
- un WordPress mascherato
- un sito pieno di placeholder
- un catalogo morto
- un frontend generico da template
- un CMS enorme
- un sistema con framework pesanti
- un progetto con segreti nel repository
- una migrazione cieca che importa tutto il dump
- un sito bello ma vuoto
- un sito tecnico ma brutto
- un sito veloce ma non amministrabile
- un sito amministrabile ma non integrato col CRM

---

## 4. Stack tecnico richiesto

### 4.1 Backend

- PHP 8.1 o superiore
- PDO MySQL
- architettura stile AIRewebCMS
- niente Laravel
- niente Symfony
- niente WordPress
- niente WooCommerce runtime
- niente framework pesanti
- service layer pulito
- router semplice
- controller modulari
- template PHP server-rendered
- API interne JSON dove servono
- admin dashboard
- inline editing frontend
- audit log per modifiche admin
- CSRF su tutte le form
- rate limit su endpoint pubblici
- honeypot antispam
- validazione input server-side

### 4.2 Frontend

- HTML server-rendered
- Tailwind CSS
- JavaScript vanilla dove possibile
- Alpine.js solo se serve
- no SPA
- no React
- no Vue
- no build chain inutile se non strettamente necessaria
- pagine mobile-first
- immagini WebP/AVIF dove possibile
- lazy loading immagini
- componenti riusabili

### 4.3 Database

- MySQL 8 o compatibile
- schema normalizzato custom
- tabelle legacy solo temporanee o di mapping
- import ripetibile
- audit import
- mappa ID WordPress -> ID nuovo sistema
- nessun vincolo inutile che blocchi la migrazione
- foreign key dove utili ma senza rendere impossibile importare dati sporchi

### 4.4 Hosting

- document root su `/home/funboy/bisped.net/public`
- `.env.php` fuori dal web root se possibile, oppure protetto
- storage scrivibile solo dove necessario
- backup DB prima di deploy
- HTTPS obbligatorio
- cookie secure e httponly
- directory listing disabilitato

---

## 5. Analisi del dump WordPress

### 5.1 Primo compito del developer agent

Il developer agent deve analizzare realmente:

```bash
/home/funboy/old_bisped.net.db.sql
```

Non deve assumere prefissi tabella standard.

Deve identificare:

- prefisso tabelle
- versione WordPress
- presenza WooCommerce
- plugin installati deducibili da tabelle e options
- tema usato
- pagine pubbliche
- prodotti
- categorie
- ordini
- coupon
- metodi pagamento
- tasse
- spedizioni
- media
- SEO plugin
- cookie/privacy plugin
- form/contact plugin
- security/antispam plugin
- import/export plugin
- cache plugin
- backup plugin
- eventuali page builder
- eventuali shortcode
- eventuali custom post type

### 5.2 Comandi utili di analisi

```bash
mkdir -p /home/funboy/bisped.net/_migration.local

cd /home/funboy/bisped.net

grep -n "CREATE TABLE" /home/funboy/old_bisped.net.db.sql \
  > _migration.local/create_tables.txt

grep -n "INSERT INTO" /home/funboy/old_bisped.net.db.sql \
  | head -200 \
  > _migration.local/insert_preview.txt

grep -Ei "woocommerce|yoast|rank_math|elementor|cookie|complianz|wordfence|akismet|contact|form|smtp|shipping|payment|stripe|paypal|invoice|import|export|backup|cache" \
  /home/funboy/old_bisped.net.db.sql \
  | head -500 \
  > _migration.local/plugin_signals.txt
```

Estrarre elenco tabelle:

```bash
awk '/CREATE TABLE/ {print $3}' /home/funboy/old_bisped.net.db.sql \
  | sed 's/`//g' \
  | sort \
  > _migration.local/tables.txt
```

Import in database temporaneo:

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS bisped_wp_legacy DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p bisped_wp_legacy < /home/funboy/old_bisped.net.db.sql
```

Se il dump è troppo grande, non tagliarlo alla cieca. Usare import reale su DB temporaneo.

---

## 6. Cosa salvare dal vecchio WordPress

### 6.1 Tabelle WordPress core

Salvare contenuto utile da:

- `*_posts`
- `*_postmeta`
- `*_terms`
- `*_term_taxonomy`
- `*_term_relationships`
- `*_options`
- `*_comments`, solo se contengono recensioni prodotto o commenti pubblicabili
- `*_commentmeta`, solo se collegato a recensioni utili
- `*_users`, solo se serve recuperare autori blog/admin storici, senza importare password come credenziali valide
- `*_usermeta`, solo se serve per autori o clienti ecommerce, da filtrare
- `*_links`, normalmente da ignorare salvo uso reale

### 6.2 WordPress posts da importare

Da `posts` importare:

- `post_type = page`
- `post_type = post`
- `post_type = product`
- `post_type = product_variation`
- `post_type = attachment`
- eventuali custom post type legati a:
  - servizi
  - portfolio
  - slider
  - banner
  - testimonials
  - FAQ
  - offerte
  - moduli
  - landing page
  - cookie/legal pages

Non importare come contenuto finale:

- revision
- nav_menu_item, salvo ricostruzione menu
- shop_order come post se esistono tabelle WooCommerce moderne dedicate
- scheduled-action
- oembed_cache
- wp_global_styles
- wp_template se non rilevante

### 6.3 WordPress postmeta da interpretare

Da `postmeta` salvare e mappare:

- `_thumbnail_id`
- `_wp_attached_file`
- `_wp_attachment_metadata`
- `_price`
- `_regular_price`
- `_sale_price`
- `_sku`
- `_stock`
- `_stock_status`
- `_manage_stock`
- `_weight`
- `_length`
- `_width`
- `_height`
- `_tax_status`
- `_tax_class`
- `_virtual`
- `_downloadable`
- `_product_image_gallery`
- `_yoast_wpseo_title`
- `_yoast_wpseo_metadesc`
- `_yoast_wpseo_focuskw`
- `rank_math_title`
- `rank_math_description`
- `rank_math_focus_keyword`
- `_elementor_data`, solo per estrarre contenuti e sezioni se il sito usava Elementor
- `_wp_page_template`
- campi custom legati a servizi, assistenza, prodotti, CTA

Non salvare:

- lock
- edit_last
- edit_lock
- transient
- cache
- sessioni
- dati tecnici di page builder non convertibili

### 6.4 Termini e categorie

Importare:

- categorie blog
- tag blog
- categorie prodotto
- tag prodotto
- attributi prodotto WooCommerce
- brand prodotto se presenti come taxonomy
- categorie servizi se presenti

Mappare:

- `category` -> `blog_categories`
- `post_tag` -> `blog_tags`
- `product_cat` -> `product_categories`
- `product_tag` -> `product_tags`
- `pa_*` -> `product_attributes`
- custom taxonomy -> valutare e normalizzare

### 6.5 Media

Importare:

- immagini prodotto
- gallery prodotto
- immagini pagine
- loghi
- favicon
- hero
- immagini blog/news
- allegati PDF utili
- manuali
- schede tecniche
- documenti scaricabili

Non importare:

- thumbnail duplicate inutili
- cache immagini
- versioni generate non usate
- immagini rotte
- immagini senza riferimento e non rilevanti
- SVG non sicuri senza sanitizzazione

Creare tabella `media_assets` con:

- `id`
- `legacy_attachment_id`
- `original_url`
- `original_path`
- `new_path`
- `mime_type`
- `title`
- `alt_text`
- `caption`
- `width`
- `height`
- `file_size`
- `checksum`
- `is_used`
- `usage_count`
- `created_at`
- `updated_at`

---

## 7. Plugin WordPress: cosa conservare e come trasformarlo

I plugin non vanno portati come plugin.

Vanno letti, capiti e convertiti in componenti custom.

### 7.1 WooCommerce

Conservare:

- prodotti
- variazioni
- categorie
- tag
- attributi
- prezzi
- SKU
- stock
- gallery
- descrizioni brevi
- descrizioni lunghe
- prodotto in evidenza
- prodotto in saldo
- tasse
- classi fiscali
- aliquote
- zone spedizione
- metodi spedizione
- metodi pagamento configurati
- coupon se presenti e ancora utili
- ordini se servono come storico
- clienti se legalmente e tecnicamente utile
- email template solo come riferimento testuale, non come codice

Ricostruire nel nuovo sito:

- catalogo prodotti
- scheda prodotto
- categoria prodotto
- ricerca prodotti
- filtro categoria/marca/prezzo
- richiesta preventivo prodotto
- carrello leggero, se richiesto dopo analisi
- checkout leggero, se i pagamenti vanno mantenuti subito
- gestione tasse
- gestione spedizioni
- gestione metodi pagamento
- admin prodotti
- import/export prodotti CSV
- export ordini/leads
- integrazione lead prodotto verso CopilotRM

Nota:

Se il vecchio ecommerce era più vetrina che checkout reale, il nuovo sistema deve privilegiare lead generation e preventivo, ma senza perdere dati su pagamenti, tasse e spedizioni.  
La decisione finale checkout sì/no va presa dopo analisi degli ordini e dei payment gateway realmente usati nel dump.

### 7.2 Cookie policy / privacy plugin

Conservare:

- testi privacy
- cookie policy
- preferenze categorie cookie
- banner copy
- link policy
- data ultimo aggiornamento
- eventuali script di terze parti rilevati
- consenso analytics/marketing se presente

Ricostruire:

- componente cookie banner custom
- pagina cookie policy
- pagina privacy policy
- tabella `consent_logs`
- gestione categorie cookie
- blocco script marketing fino a consenso
- pannello admin per modificare testi policy
- link permanente nel footer

### 7.3 SEO plugin

Plugin possibili:

- Yoast
- RankMath
- All in One SEO
- SEO Framework

Conservare:

- title SEO
- meta description
- focus keyword
- canonical
- robots
- sitemap settings utili
- OG title
- OG description
- OG image
- Twitter card
- redirect se gestiti dal plugin
- breadcrumb label se presente

Ricostruire:

- tabella `seo_meta`
- generatore sitemap XML
- robots.txt
- canonical automatici
- OpenGraph
- Twitter card
- schema.org base
- redirect manager
- preservazione slug 1:1

### 7.4 Contact form / lead form plugin

Plugin possibili:

- Contact Form 7
- WPForms
- Gravity Forms
- Fluent Forms
- Ninja Forms

Conservare:

- campi form
- etichette
- email destination
- messaggi di conferma
- oggetti email
- form usati in pagine pubbliche
- form contatto
- form preventivo
- form assistenza
- form teleassistenza
- eventuali submission storiche, se presenti

Ricostruire:

- form custom PHP
- CSRF
- honeypot
- rate limit
- validazione
- salvataggio lead in DB nuovo
- inoltro a CopilotRM
- notifica email via SMTP
- audit log
- admin leads

### 7.5 Sicurezza / antispam

Plugin possibili:

- Wordfence
- iThemes Security
- Akismet
- Antispam Bee
- reCAPTCHA
- Cloudflare Turnstile

Conservare:

- regole antispam rilevanti
- whitelist/blacklist se presenti
- email admin sicurezza
- eventuale chiave Turnstile/reCAPTCHA solo come riferimento, mai committarla
- logica di protezione form

Ricostruire:

- CSRF
- honeypot invisibile
- rate limit IP
- throttling per email/telefono
- blocklist semplice
- log tentativi sospetti
- protezione admin
- session cookie sicuri
- password hash forte o login wallet se mantenuto da AIRewebCMS
- headers sicurezza
- upload sanitization
- SVG sanitization
- MIME sniffing
- limite dimensione upload

### 7.6 Import/export

Conservare:

- mapping CSV
- plugin export prodotti se usato
- configurazioni import feed
- struttura colonne prodotti
- eventuali cron di import catalogo

Ricostruire:

- export CSV prodotti
- import CSV prodotti
- export leads
- export ordini
- export blog/news
- export media mapping
- import idempotente
- report errori import
- preview prima dell’import definitivo

### 7.7 Cache/performance

Non conservare cache vecchie.

Ricostruire:

- cache HTML opzionale per pagine pubbliche
- cache query prodotti
- cache menu/footer/settings
- invalidazione cache quando admin modifica contenuti
- compressione asset
- lazy loading
- immagini WebP
- sitemap cache
- no plugin cache WordPress

### 7.8 Page builder / tema

Se il vecchio sito usava Elementor, WPBakery, Gutenberg blocks o tema custom:

- estrarre contenuto testuale
- estrarre immagini
- estrarre struttura sezioni
- estrarre CTA
- estrarre menu
- estrarre footer
- estrarre layout ricorrenti
- convertire shortcode/blocchi in componenti PHP custom

Non portare:

- JSON Elementor come runtime
- CSS generato da page builder
- shortcode non interpretati
- classi tema vecchio senza utilità

---

## 8. Schema dati target

Il nuovo DB deve avere schema pulito e leggibile.

### 8.1 Tabelle core

Minimo richiesto:

```text
settings
admins
admin_sessions
audit_log
media_assets
pages
page_blocks
menus
menu_items
seo_meta
redirects
blog_posts
blog_categories
blog_tags
blog_post_tags
product_categories
products
product_variants
product_media
product_attributes
product_attribute_values
product_specs
product_tags
product_tag_links
tax_classes
tax_rates
shipping_zones
shipping_methods
payment_methods
coupons
carts
cart_items
orders
order_items
leads
lead_events
tickets
ticket_messages
consent_categories
consent_texts
consent_logs
forms
form_fields
form_submissions
legacy_import_jobs
legacy_import_map
legacy_plugin_snapshots
crm_sync_log
internal_api_tokens
```

### 8.2 Tabelle legacy temporanee

È accettabile creare tabelle temporanee in `_migration.local` o DB temporaneo.

Non lasciare nel DB finale tabelle WordPress brute-force salvo necessità tracciata.

Se serve conservare dati non ancora convertiti:

```text
legacy_raw_records
legacy_plugin_snapshots
```

Ma solo per record selezionati, non per copiare tutto WordPress.

### 8.3 Regola sugli ID legacy

Ogni record migrato deve conservare il riferimento all’origine:

- `legacy_source`
- `legacy_table`
- `legacy_id`
- `legacy_slug`
- `legacy_hash`

Esempio:

```text
products.legacy_id = wp_posts.ID
pages.legacy_id = wp_posts.ID
media_assets.legacy_attachment_id = wp_posts.ID
```

Questo serve per audit, reimport e debug.

---

## 9. Migrazione contenuti frontend

### 9.1 Slug e URL

Regola obbligatoria:

Gli slug pubblici attuali devono essere preservati.

Per ogni vecchio URL:

- se la pagina esiste ancora, mantenere stesso URL
- se la pagina è accorpata, creare redirect 301
- se è rimossa, decidere redirect verso pagina più vicina
- non lasciare 404 per pagine indicizzate senza decisione

Creare:

```text
_migration.local/url-map.csv
_migration.local/redirects.csv
```

Campi:

```csv
legacy_url,new_url,status,reason,source_type,legacy_id
```

### 9.2 Pagine da ricostruire

Il developer agent deve prima estrarre l’elenco reale dal dump.

Poi ricostruire almeno questi template/pattern:

- homepage
- chi siamo / azienda
- servizi
- assistenza
- teleassistenza
- prodotti / shop
- categoria prodotto
- scheda prodotto
- carrello o richiesta preventivo, secondo decisione ecommerce
- checkout o conferma richiesta, secondo decisione ecommerce
- blog/news/novità
- dettaglio articolo
- contatti
- privacy policy
- cookie policy
- condizioni vendita
- spedizioni
- pagamenti
- resi/garanzia
- area admin login
- admin dashboard
- gestione pagine
- gestione blocchi
- gestione media
- gestione prodotti
- gestione categorie
- gestione ordini/leads
- gestione blog
- gestione SEO
- gestione redirect
- gestione policy
- gestione form
- gestione CRM sync

### 9.3 Conversione contenuti

Per ogni pagina:

- estrarre titolo
- slug
- contenuto HTML
- immagini
- shortcodes
- blocchi Gutenberg
- metadata SEO
- template usato
- menu di appartenenza
- CTA
- form incorporati
- prodotti collegati
- allegati

Poi convertire in:

- `pages`
- `page_blocks`
- `media_assets`
- `seo_meta`
- `forms`
- componenti PHP

I blocchi devono essere normalizzati, per esempio:

```text
hero
text_image
service_grid
product_grid
cta_band
faq
contact_form
teleassistance_download
blog_preview
brand_strip
testimonial_grid
legal_text
```

---

## 10. Ecommerce target

### 10.1 Obiettivo ecommerce

Il nuovo ecommerce non deve replicare tutta la complessità WooCommerce se non serve.

Deve però preservare e rendere amministrabili:

- catalogo prodotti
- prezzi
- IVA/tasse
- spedizioni
- disponibilità
- SKU
- categorie
- immagini
- gallery
- descrizioni
- schede tecniche
- varianti
- attributi
- metodi pagamento
- richieste preventivo
- eventuali ordini
- email amministrative
- lead verso CopilotRM

### 10.2 Modalità ecommerce supportate

Implementare in modo modulare due modalità:

#### Modalità A: catalogo + richiesta preventivo

Default consigliato se il vecchio checkout non è realmente centrale.

Funzionalità:

- scheda prodotto
- prezzo visibile o “richiedi prezzo”
- richiesta preventivo
- richiesta disponibilità
- aggiunta prodotto alla richiesta
- form cliente
- invio a CopilotRM
- salvataggio lead
- notifica email
- nessun pagamento diretto obbligatorio

#### Modalità B: checkout leggero

Da attivare solo se dal dump emerge uso reale ecommerce.

Funzionalità:

- carrello
- checkout
- calcolo tasse
- calcolo spedizione
- scelta pagamento
- ordine
- stato ordine
- email conferma
- backend ordini
- eventuale gateway pagamento, solo dopo identificazione gateway reale

### 10.3 Pagamenti

Dal dump estrarre:

- gateway attivi
- gateway disattivi
- configurazioni principali
- valute
- modalità test/live
- testi checkout
- bonifico
- contrassegno
- PayPal
- Stripe
- altri gateway

Nel nuovo sito:

- non committare chiavi gateway
- salvare solo configurazione non sensibile
- secret in `.env.php`
- admin UI per abilitare/disabilitare metodi
- pagamento online disattivabile da settings
- fallback sempre disponibile: richiesta preventivo / pagamento offline

### 10.4 Tasse

Dal dump WooCommerce estrarre:

- paese base negozio
- valuta
- prezzi inclusi/esclusi IVA
- classi fiscali
- aliquote
- zone
- regole

Nel nuovo sito:

- `tax_classes`
- `tax_rates`
- calcolo IVA deterministico
- visualizzazione prezzo coerente
- admin tasse
- test unitari su calcolo tasse

### 10.5 Spedizioni

Dal dump estrarre:

- zone spedizione
- metodi spedizione
- corrieri
- ritiro in sede
- spedizione gratuita
- flat rate
- soglie
- classi spedizione

Nel nuovo sito:

- `shipping_zones`
- `shipping_methods`
- calcolo shipping semplice
- possibilità “ritiro in sede”
- possibilità “spedizione da concordare”
- admin spedizioni
- fallback se non calcolabile: richiesta preventivo

### 10.6 Prodotti

Ogni prodotto deve avere:

```text
id
legacy_id
sku
slug
name
short_description
description
category_id
brand
regular_price
sale_price
currency
tax_class_id
stock_status
stock_quantity
manage_stock
featured
status
main_image_id
seo_title
seo_description
created_at
updated_at
```

Varianti:

```text
product_id
sku
attributes_json
regular_price
sale_price
stock_status
stock_quantity
image_id
legacy_id
```

Attributi:

```text
name
slug
type
value
sort_order
```

---

## 11. Grafica e UX

### 11.1 Identità visiva

Bisped deve mantenere identità:

- rosso
- nero
- bianco
- tecnico
- professionale
- locale ma moderno
- aziendale
- IT/TLC
- assistenza reale
- no stile crypto
- no stile AIRewardrop
- no meme
- no mascotte
- no template SaaS generico

### 11.2 Direzione grafica

Look richiesto:

- moderno 2025/2026
- pulito
- contrasto alto
- sezioni chiare
- hero forte
- CTA evidenti
- cards prodotto ordinate
- footer completo
- header serio
- mobile curato
- immagini credibili
- icone tecniche
- niente stock image ridicole
- niente gradienti eccessivi
- niente animazioni inutili

### 11.3 Palette consigliata

```text
nero profondo: #080808
grigio scuro: #151515
rosso Bisped: #D71920 o ricavare dal logo reale
rosso hover: #B9151B
bianco: #FFFFFF
grigio testo: #D6D6D6
grigio bordo: #2A2A2A
sfondo chiaro alternativo: #F6F6F6
```

Il colore rosso va confermato estraendolo da logo/media reali.

### 11.4 Componenti UI

Creare componenti:

- `Header`
- `TopBar`
- `MainNav`
- `MobileNav`
- `Hero`
- `ServiceCard`
- `ProductCard`
- `ProductFilter`
- `ProductGallery`
- `CTASection`
- `TrustStrip`
- `BlogCard`
- `ContactForm`
- `QuoteForm`
- `TicketForm`
- `TeleassistanceBox`
- `CookieBanner`
- `Footer`
- `Breadcrumbs`
- `Pagination`
- `SearchBar`
- `AdminToolbar`
- `InlineEditable`
- `MediaPicker`

### 11.5 Footer

Footer obbligatorio con:

- logo Bisped
- breve descrizione azienda
- link servizi
- link shop/prodotti
- link assistenza
- link teleassistenza
- link blog/news
- contatti
- orari se disponibili nel contenuto
- indirizzo se presente nel vecchio sito
- telefono/email se presenti nel vecchio sito
- privacy policy
- cookie policy
- condizioni vendita
- pagamenti
- spedizioni
- P.IVA/dati aziendali se presenti
- credits tecnici opzionali

---

## 12. Contenuti

### 12.1 Regola contenuti

Il sito nuovo deve partire già pieno di contenuti.

Non sono accettabili pagine vuote con lorem ipsum.

Quando i contenuti esistono nel dump:

- importarli
- ripulirli
- impaginarli
- preservarli semanticamente

Quando i contenuti non bastano:

- creare testi professionali coerenti con Bisped
- marcarli come `generated_draft = 1` nel DB o in report
- renderli modificabili da admin
- non inventare dati legali, prezzi, garanzie, indirizzi o certificazioni non presenti

### 12.2 Tono testi

Tono richiesto:

- professionale
- diretto
- tecnico
- concreto
- commerciale ma non finto
- orientato a clienti business e privati
- assistenza reale
- niente supercazzole da agenzia
- niente claim non verificabili
- niente buzzword AI se non serve

### 12.3 Pagine principali attese

La struttura finale deve emergere dal dump, ma il sito deve coprire almeno:

```text
/
azienda
servizi
assistenza
teleassistenza
prodotti
prodotti/{categoria}
prodotto/{slug}
preventivo
blog
blog/{slug}
contatti
privacy-policy
cookie-policy
condizioni-di-vendita
pagamenti
spedizioni
resi-e-garanzia
```

Se gli slug attuali sono diversi, usare gli slug attuali e creare alias/redirect.

---

## 13. Teleassistenza

### 13.1 Pagina teleassistenza

La pagina teleassistenza deve essere una pagina centrale.

Deve contenere:

- spiegazione chiara del servizio
- quando usarla
- requisiti
- privacy/sicurezza
- pulsante download agent/software remoto se presente
- form apertura ticket
- form richiesta appuntamento
- collegamento diretto a CopilotRM
- CTA telefono/email
- FAQ

### 13.2 Form ticket

Campi minimi:

- nome
- cognome
- azienda opzionale
- email
- telefono
- tipo dispositivo
- sistema operativo
- problema
- urgenza
- consenso privacy
- allegato opzionale screenshot/log
- prodotto/ordine collegato opzionale

Alla submit:

- validare
- salvare in `tickets`
- creare o aggiornare customer in CopilotRM
- creare ticket in CopilotRM
- inviare email conferma
- notificare admin
- creare audit log
- mostrare codice ticket

---

## 14. Integrazione CopilotRM

### 14.1 Obiettivo

Il nuovo Bisped.net deve diventare frontend pubblico e commerciale collegato a CopilotRM.

CopilotRM resta il sistema CRM/operativo.

Bisped.net gestisce:

- contenuti pubblici
- catalogo
- form
- lead
- richieste preventivo
- ticket assistenza
- newsletter/news
- entry-point per agenti locali

CopilotRM gestisce o riceve:

- anagrafiche clienti
- lead qualificati
- ticket
- timeline contatti
- eventuali attività operative
- eventuale agente locale

### 14.2 Regola di integrazione

Non modificare CopilotRM senza analisi.

Prima:

```bash
cd /home/funboy/copilotrm
find . -maxdepth 3 -type f | sort > /home/funboy/bisped.net/_migration.local/copilotrm_files.txt
grep -R "CREATE TABLE\|customers\|clients\|tickets\|leads\|contacts" -n . \
  > /home/funboy/bisped.net/_migration.local/copilotrm_schema_signals.txt
```

Individuare:

- linguaggio/framework
- file config
- schema DB
- tabelle customers
- tabelle tickets
- tabelle leads
- modelli dati
- API interne se esistono
- sistema auth
- sistema log
- eventuali webhook

### 14.3 CopilotBridge

Creare:

```text
app/Services/CopilotBridge.php
```

Responsabilità:

```php
createLead(array $payload): CopilotResult
createTicket(array $payload): CopilotResult
upsertCustomer(array $payload): CopilotResult
appendCustomerEvent(string $customerId, array $event): CopilotResult
syncProductCatalog(array $product): CopilotResult
getTicketStatus(string $externalTicketId): CopilotResult
healthCheck(): CopilotResult
```

Il bridge deve supportare due modalità:

#### Modalità DB diretto

Se CopilotRM usa MySQL accessibile:

- connessione DB separata
- credenziali dedicate
- permessi minimi
- mapping tabella per tabella
- transazioni
- log sync

#### Modalità API

Se CopilotRM espone endpoint HTTP:

- client HTTP interno
- token in `.env.php`
- retry
- timeout
- log sync
- gestione errori

### 14.4 Tabelle sync

Nel sito nuovo creare:

```text
crm_sync_log
```

Campi:

```text
id
entity_type
entity_id
direction
crm_entity_type
crm_entity_id
status
request_payload_json
response_payload_json
error_message
attempts
created_at
updated_at
```

### 14.5 API interne per LLM locale

Creare endpoint interni protetti:

```text
GET  /internal/api/health
GET  /internal/api/catalog/search?q=&category=&brand=&limit=
GET  /internal/api/catalog/product/{id}
GET  /internal/api/catalog/sku/{sku}
GET  /internal/api/blog/latest
POST /internal/api/blog/draft
POST /internal/api/offers/draft
POST /internal/api/leads
POST /internal/api/tickets
GET  /internal/api/company/context
```

Sicurezza:

- token interno in header
- IP allowlist se possibile
- rate limit
- audit log
- nessun accesso pubblico libero
- niente dati sensibili cliente senza permesso

### 14.6 Embedding e LLM

Gli embedding non servono perché “l’LLM non basta”.

Servono perché fanno un lavoro diverso.

L’LLM ragiona, scrive, decide e sintetizza.

Gli embedding servono a:

- trasformare contenuti in vettori confrontabili
- trovare testi simili
- recuperare contesto rilevante
- fare deduplica semantica
- raggruppare prodotti/news/ticket simili
- costruire memoria interrogabile
- alimentare RAG
- evitare di passare tutto il database all’LLM
- recuperare solo quello che serve
- fare storico e confronto nel tempo

Per Bisped/CopilotRM usare embedding su:

- prodotti
- descrizioni prodotto
- categorie
- servizi
- FAQ
- articoli blog
- ticket risolti
- documenti assistenza
- policy
- offerte

Aggiornamento:

- non solo settimanale
- rigenerazione incrementale quando un contenuto cambia
- batch notturno opzionale
- rebuild settimanale solo come manutenzione

Nel nuovo sito predisporre hook:

```text
onProductSaved -> queue embedding update
onBlogPostSaved -> queue embedding update
onTicketClosed -> queue embedding update
onPageSaved -> queue embedding update
```

Se il sistema embedding non viene implementato subito, lasciare interfacce e job stub.

---

## 15. Admin dashboard

### 15.1 Moduli admin obbligatori

Admin deve gestire:

- dashboard
- pagine
- blocchi pagina
- media library
- menu
- blog/news
- categorie blog
- prodotti
- categorie prodotto
- attributi prodotto
- tasse
- spedizioni
- pagamenti
- coupon, se importati
- lead
- ticket
- ordini, se implementati
- SEO metadata
- redirect
- cookie/privacy texts
- form
- impostazioni sito
- integrazione CopilotRM
- import/export
- audit log

### 15.2 Inline editing

Mantenere o adattare il sistema AIRewebCMS:

- admin autenticato vede toolbar
- toggle admin mode
- editing testi direttamente dal frontend
- editing immagini
- editing link/CTA
- salvataggio via API
- CSRF
- audit log
- rollback minimo o storico modifiche

### 15.3 Media library

Funzionalità:

- upload immagini
- selezione da media
- replace immagine mantenendo URL se richiesto
- delete bloccato se media in uso
- WebP conversion
- alt text
- caption
- filtro per tipo
- ricerca
- usage count
- sanitizzazione SVG
- max upload configurabile

---

## 16. SEO

### 16.1 SEO 1:1

Obiettivo:

- non perdere ranking
- non perdere slug
- non perdere metadata
- non perdere immagini social
- non perdere pagine indicizzate
- non generare 404 inutili

### 16.2 Output SEO obbligatori

Implementare:

- title per pagina
- meta description
- canonical
- OpenGraph
- Twitter card
- robots
- sitemap.xml
- robots.txt
- breadcrumbs
- schema.org LocalBusiness/Organization
- schema.org Product dove applicabile
- schema.org BlogPosting dove applicabile
- redirect 301
- pagina 404 curata

### 16.3 Redirect

Creare tabella:

```text
redirects
```

Campi:

```text
id
source_path
target_path
status_code
reason
legacy_id
hit_count
last_hit_at
created_at
updated_at
```

Importare redirect dal vecchio sistema se presenti.

Creare redirect automatici per:

- vecchi URL prodotto
- vecchie categorie
- vecchi post
- vecchie pagine
- URL con slash/non slash
- eventuali permalink WordPress cambiati

---

## 17. Sicurezza

### 17.1 Regole base

Obbligatorio:

- `.env.php` non committato
- dump SQL non committato
- password non committate
- secret non committati
- CSRF
- session secure
- httponly cookie
- SameSite
- rate limit
- upload validation
- MIME sniffing
- SVG sanitization
- no eval
- no unserialize su input non fidato
- prepared statements
- output escaping
- admin protected
- log accessi admin
- audit modifiche contenuto
- backup prima di migrazione

### 17.2 Header sicurezza

Impostare:

```text
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy minimale
Content-Security-Policy iniziale compatibile
```

### 17.3 Antispam form

Implementare:

- honeypot
- minimum submit time
- rate limit IP
- rate limit email
- blacklist pattern
- Cloudflare Turnstile opzionale
- log spam
- blocco allegati pericolosi

---

## 18. Performance

### 18.1 Target

Target:

- homepage sotto 1 secondo su server reale
- Lighthouse Performance alto
- HTML server-rendered
- query ridotte
- immagini ottimizzate
- CSS minimale
- JS minimale

### 18.2 Ottimizzazioni

Implementare:

- eager loading solo hero
- lazy loading immagini sotto fold
- WebP
- width/height immagini
- preload logo/hero solo se serve
- cache settings/menu/footer
- query indicizzate
- paginazione prodotti/blog
- no asset inutili da AIRewebCMS se non usati
- no JS admin per utenti pubblici

### 18.3 Indici DB

Aggiungere indici su:

```text
slug
status
category_id
sku
created_at
published_at
legacy_id
email
phone
source
```

---

## 19. Migrazione: script richiesti

### 19.1 Script principali

Creare:

```text
scripts/legacy/analyze-wp-dump.php
scripts/legacy/import-wp-to-temp.php
scripts/legacy/extract-plugin-config.php
scripts/legacy/migrate-pages.php
scripts/legacy/migrate-media.php
scripts/legacy/migrate-products.php
scripts/legacy/migrate-woocommerce-settings.php
scripts/legacy/migrate-blog.php
scripts/legacy/migrate-seo.php
scripts/legacy/migrate-redirects.php
scripts/legacy/migrate-forms.php
scripts/legacy/migrate-legal.php
scripts/legacy/generate-url-map.php
scripts/legacy/validate-migration.php
scripts/legacy/full-migration.php
```

### 19.2 Full migration

`full-migration.php` deve:

1. leggere `.env.php`
2. connettersi al DB legacy temporaneo
3. connettersi al DB nuovo
4. creare import job
5. migrare media
6. migrare categorie
7. migrare pagine
8. migrare blog
9. migrare prodotti
10. migrare WooCommerce settings
11. migrare SEO
12. migrare legal/policy
13. migrare form
14. generare redirect
15. validare conteggi
16. scrivere report

Output:

```text
storage/imports/YYYYMMDD-HHMMSS/report.json
storage/imports/YYYYMMDD-HHMMSS/report.md
storage/imports/YYYYMMDD-HHMMSS/url-map.csv
storage/imports/YYYYMMDD-HHMMSS/product-map.csv
storage/imports/YYYYMMDD-HHMMSS/media-map.csv
storage/imports/YYYYMMDD-HHMMSS/errors.log
```

### 19.3 Import idempotente

Lo script deve poter essere rilanciato.

Regole:

- se `legacy_id` esiste aggiorna
- se non esiste crea
- non creare duplicati
- mantenere mapping
- loggare conflitti
- non cancellare contenuti nuovi manuali senza flag esplicito

---

## 20. Testing e validazione

### 20.1 Test minimi

Creare test o script QA per:

- homepage 200
- tutte le pagine importate 200
- tutti i prodotti importati 200
- categorie prodotto 200
- articoli blog 200
- form contatti salva lead
- form teleassistenza crea ticket
- lead sync CopilotRM
- ticket sync CopilotRM
- sitemap valida
- robots.txt presente
- redirect funzionanti
- no errori PHP log
- admin login
- inline edit
- upload media
- import prodotti
- export prodotti
- tasse calcolate
- spedizioni calcolate
- pagamento offline visibile se attivo
- cookie banner funzionante
- policy raggiungibili

### 20.2 Report conteggi

Confrontare:

```text
numero pagine WP -> numero pagine nuove
numero post WP -> numero blog nuovi
numero prodotti WP -> numero prodotti nuovi
numero categorie prodotto WP -> numero categorie nuove
numero immagini usate -> numero media asset nuovi
numero form rilevati -> numero form nuovi
numero redirect -> numero redirect nuovi
```

Ogni discrepanza deve essere spiegata.

### 20.3 QA manuale

Controllare:

- homepage
- mobile
- header
- menu
- footer
- pagine servizio
- teleassistenza
- shop
- scheda prodotto
- form contatto
- form preventivo
- privacy/cookie
- backend admin
- velocità
- errori console
- errori PHP
- email inviate
- CRM riceve lead/ticket

---

## 21. Deploy

### 21.1 Prima del deploy

Checklist:

- backup vecchio sito
- backup vecchio DB
- backup CopilotRM
- nuovo DB creato
- `.env.php` configurato
- migration completata
- report letto
- admin funzionante
- form testati
- email testate
- CRM sync testato
- sitemap generata
- redirect generati
- robots corretto
- cookie policy raggiungibile
- pagine legali raggiungibili
- performance testata
- nessun secret in Git
- nessun dump in Git

### 21.2 Document root

Document root:

```text
/home/funboy/bisped.net/public
```

Non esporre:

```text
/home/funboy/bisped.net/app
/home/funboy/bisped.net/database
/home/funboy/bisped.net/storage
/home/funboy/bisped.net/scripts
/home/funboy/bisped.net/_migration.local
/home/funboy/bisped.net/.env.php
```

### 21.3 Post deploy

Dopo deploy:

- controllare log PHP
- controllare form
- controllare CopilotRM sync
- controllare sitemap
- controllare Google Search Console se disponibile
- controllare redirect top URL
- controllare prodotti principali
- controllare mobile
- controllare cookie banner

---

## 22. Criteri di accettazione

Il lavoro è accettabile solo se:

- il sito gira senza WordPress
- il sito gira senza WooCommerce runtime
- il sito usa AIRewebCMS adattato o architettura equivalente
- il dump è stato analizzato realmente
- i contenuti frontend sono migrati
- i prodotti sono migrati
- categorie prodotto migrate
- prezzi migrati
- immagini prodotto migrate o mappate
- SEO metadata migrati
- slug preservati
- redirect creati
- blog/news migrati
- policy migrate
- cookie banner custom presente
- form contatti funzionante
- form teleassistenza funzionante
- lead salvati
- ticket salvati
- CopilotRM integrato almeno con bridge funzionante o stub configurabile
- admin dashboard funzionante
- inline editing funzionante
- media library funzionante
- import/export prodotti presente
- tasse/spedizioni/pagamenti preservati almeno come configurazione
- nessun secret nel repo
- nessun dump SQL nel repo
- report migrazione presente
- sito mobile curato
- frontend moderno e coerente Bisped
- performance buona
- documentazione deployment presente

---

## 23. Prompt operativo per Claude Code / Cursor / Codex

Usare questo prompt dentro `/home/funboy/bisped.net`.

```text
Ruolo:
Sei un senior full-stack engineer PHP/MySQL specializzato in migrazioni WordPress/WooCommerce verso CMS custom leggeri, integrazioni CRM e siti business ad alte performance.

Contesto reale:
Il progetto finale vive in /home/funboy/bisped.net.
Il dump del vecchio sito WordPress/WooCommerce è /home/funboy/old_bisped.net.db.sql.
Il CRM esistente è /home/funboy/copilotrm.
La base tecnica consigliata è AIRewebCMS: https://github.com/0xfunboy/AIRewebCMS.
Il nuovo sito deve sostituire Bisped.net senza usare WordPress.

Obiettivo:
Ricostruire Bisped.net come sito custom PHP/MySQL moderno, leggero, amministrabile, con frontend completo, ecommerce/catalogo, gestione prodotti, tasse, spedizioni, pagamenti, news/blog, cookie policy, sicurezza antispam, import/export, SEO 1:1 e integrazione con CopilotRM.

Regola fondamentale:
Non copiare WordPress.
Non portare plugin WordPress come codice.
Analizza il dump, conserva i dati e le logiche utili, elimina la zavorra.
I plugin importanti devono diventare componenti custom del nuovo sito.

Percorsi:
- target: /home/funboy/bisped.net
- dump legacy: /home/funboy/old_bisped.net.db.sql
- CRM: /home/funboy/copilotrm
- workspace migrazione locale: /home/funboy/bisped.net/_migration.local

Vincoli:
- niente Laravel/Symfony/framework pesanti
- niente WordPress
- niente WooCommerce runtime
- niente secret nel repo
- niente dump SQL nel repo
- document root su public/
- .env.php escluso da Git
- SQL dump escluso da Git
- contenuti reali migrati dal dump
- slug preservati
- redirect 301 dove serve
- frontend già pieno di contenuti
- grafica moderna Bisped rosso/nero/bianco
- admin dashboard completa
- inline editing dove possibile
- media library
- API interne protette per CopilotRM/LLM locale

Prima fase obbligatoria:
1. Ispeziona AIRewebCMS se già presente nel target.
2. Se il target è vuoto, usa AIRewebCMS come base, ma prepara repo privato Bisped.
3. Verifica .gitignore e rimuovi qualsiasi .env.php tracciato.
4. Crea _migration.local e tienilo fuori da Git.
5. Analizza realmente /home/funboy/old_bisped.net.db.sql.
6. Identifica prefisso tabelle, plugin, WooCommerce, SEO plugin, cookie plugin, form plugin, security plugin, import/export plugin, cache plugin, page builder, tema.
7. Genera report iniziale in storage/imports/analysis-report.md.

Migrazione:
Crea script in scripts/legacy/ per:
- analisi dump
- import DB temporaneo
- estrazione plugin config
- migrazione pagine
- migrazione media
- migrazione prodotti
- migrazione WooCommerce settings
- migrazione blog/news
- migrazione SEO
- migrazione redirect
- migrazione form
- migrazione policy
- validazione finale

Schema:
Crea o adatta database/schema.sql includendo:
settings, admins, sessions, audit_log, media_assets, pages, page_blocks, menus, menu_items, seo_meta, redirects, blog_posts, blog_categories, blog_tags, products, product_categories, product_variants, product_media, product_attributes, tax_classes, tax_rates, shipping_zones, shipping_methods, payment_methods, coupons, carts, orders, leads, tickets, consent_logs, forms, form_submissions, legacy_import_map, legacy_plugin_snapshots, crm_sync_log, internal_api_tokens.

WooCommerce:
Migra prodotti, categorie, tag, attributi, varianti, SKU, prezzi, sale price, stock, immagini, gallery, tasse, spedizioni, pagamenti, coupon e ordini se presenti.
Se il checkout reale non è necessario, implementa catalogo + richiesta preventivo mantenendo comunque configurazioni tasse/spedizioni/pagamenti in admin.
Se dal dump emerge uso reale checkout, implementa checkout leggero modulare.

Plugin:
Converti i plugin utili in componenti custom:
- cookie/privacy -> cookie banner + consent logs + policy pages
- SEO -> seo_meta + sitemap + robots + OG + redirect manager
- form -> form custom + CSRF + honeypot + lead/ticket
- antispam/security -> rate limit + honeypot + upload validation + secure headers
- import/export -> CSV/JSON import/export admin
- cache -> cache custom leggera
- page builder/theme -> componenti PHP server-rendered

CopilotRM:
Ispeziona /home/funboy/copilotrm senza modificarlo.
Crea app/Services/CopilotBridge.php.
Supporta DB diretto o API, secondo struttura reale trovata.
Implementa:
- createLead
- createTicket
- upsertCustomer
- appendCustomerEvent
- syncProductCatalog
- getTicketStatus
- healthCheck
Logga tutto in crm_sync_log.

API interne:
Crea endpoint protetti:
GET /internal/api/health
GET /internal/api/catalog/search
GET /internal/api/catalog/product/{id}
GET /internal/api/catalog/sku/{sku}
GET /internal/api/blog/latest
POST /internal/api/blog/draft
POST /internal/api/offers/draft
POST /internal/api/leads
POST /internal/api/tickets
GET /internal/api/company/context

Frontend:
Ricostruisci il sito con identità Bisped:
- rosso nero bianco
- moderno 2025/2026
- professionale IT/TLC
- mobile-first
- header completo
- footer completo
- home forte
- servizi
- assistenza
- teleassistenza
- catalogo prodotti
- scheda prodotto
- blog/news
- contatti
- policy
- ecommerce/quote flow
- nessun placeholder inutile

SEO:
Preserva slug.
Genera url-map.csv.
Genera redirects.csv.
Implementa sitemap.xml, robots.txt, canonical, OG, schema.org, redirect 301.

Sicurezza:
Non committare secret.
Non committare dump.
Usa prepared statements.
CSRF ovunque.
Rate limit form.
Upload sicuro.
Sessioni sicure.
Audit admin.
Secure headers.

Output richiesto:
- codice completo nel progetto
- schema SQL
- migration scripts
- report analisi dump
- report migrazione
- url-map.csv
- redirects.csv
- admin funzionante
- frontend funzionante
- CopilotBridge funzionante o stub configurabile se CRM schema non è ancora sufficiente
- README deployment
- checklist QA

Non fermarti a spiegare.
Implementa file reali.
Quando devi fare assunzioni, scrivile in ASSUMPTIONS.md.
Quando trovi dati mancanti, crea placeholder strutturati ma segnali nel report.
Quando trovi dati sensibili, non committarli.
```

---

## 24. ASSUMPTIONS.md da creare

Creare un file:

```text
ASSUMPTIONS.md
```

Deve contenere solo assunzioni tecniche fatte durante lo sviluppo, per esempio:

```markdown
# Assumptions

- Il vecchio checkout WooCommerce non sembra usato negli ultimi X mesi, quindi il flusso default è catalogo + richiesta preventivo.
- Il gateway PayPal è stato rilevato ma non contiene configurazioni live esportabili; le chiavi vanno reinserite in .env.php.
- Alcuni media risultano mancanti nel dump; sono stati mappati come missing_media.
- La pagina X non aveva contenuto utile; è stata ricostruita come draft.
```

Non nascondere assunzioni nel codice.

---

## 25. REPORT obbligatori

### 25.1 analysis-report.md

Deve contenere:

- tabelle trovate
- prefisso WordPress
- plugin rilevati
- tema rilevato
- numero pagine
- numero post
- numero prodotti
- numero categorie
- numero immagini
- numero ordini
- numero form
- SEO plugin
- cookie plugin
- security plugin
- page builder
- dati da salvare
- dati da scartare
- rischi

### 25.2 migration-report.md

Deve contenere:

- cosa è stato migrato
- conteggi
- errori
- media mancanti
- URL preservati
- redirect creati
- prodotti senza prezzo
- prodotti senza immagine
- pagine draft
- configurazioni WooCommerce importate
- configurazioni plugin convertite
- stato CopilotRM sync
- prossimi interventi manuali

---

## 26. Priorità di sviluppo

Ordine esatto:

1. sicurezza repo e `.gitignore`
2. bootstrap AIRewebCMS
3. analisi dump
4. schema target
5. migration scripts
6. import contenuti base
7. frontend home + layout globale
8. prodotti/catalogo
9. ecommerce settings tasse/spedizioni/pagamenti
10. blog/news
11. policy/cookie
12. form contatti/preventivi/teleassistenza
13. CopilotRM bridge
14. admin dashboard
15. inline editing
16. SEO/redirects/sitemap
17. performance
18. QA
19. deploy notes

Non fare prima la grafica finale se i dati non sono migrati.  
Non fare prima CopilotRM se i form e il modello lead/ticket non sono stabili.  
Non fare checkout prima di sapere se serve davvero.

---

## 27. Regola finale

Il sito deve sembrare progettato, non migrato.

Il vecchio WordPress serve come fonte dati e memoria storica.

Il nuovo Bisped.net deve essere:

- più veloce
- più pulito
- più sicuro
- più amministrabile
- più moderno
- più utile commercialmente
- integrato con CopilotRM
- pronto per essere esteso da agenti locali
- senza dipendere da WordPress
