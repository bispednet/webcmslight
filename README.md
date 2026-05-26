# Bisped.net custom CMS

Rework locale di `bisped.net` basato su AIRewebCMS, pensato per sostituire WordPress/WooCommerce solo quando il nuovo sito sara validato.

## Stato

- Base PHP/MySQL server-rendered con document root in `public/`.
- Pagine pubbliche Bisped: `/`, `/azienda`, `/servizi`, `/sostenibilita`, `/contatti`, `/products`, `/blog`, `/faq`, `/legal`.
- Seed iniziale per impostazioni, navigazione, prodotti WooCommerce importati, FAQ, blog e testi legali provvisori.
- Asset recuperati in sola lettura da FTP in `public/media/bisped/`.
- Form contatti con CSRF, honeypot e rate-limit sessione.
- Audit migrazione in `docs/BISPED_MIGRATION_AUDIT.md`.
- Runtime locale portabile in `runtime/` con FrankenPHP, MariaDB e Playwright. La cartella e esclusa da Git.

## Sorgenti legacy

- Dump SQL corretto: `/home/funboy/uu4c5pdm_wpb.sql`, importato in `bisped_wp_legacy`.
- Dump SQL precedente: `/home/funboy/old_bisped.net.db.sql`, considerato ambiente test/demo.
- Produzione FTP: usare solo in lettura per analisi e recupero asset.
- CopilotRM: `/home/funboy/copilotrm`, da ispezionare e integrare senza modificarlo da questo progetto.

## Setup locale

```bash
cp .env.example.php .env.php
```

Poi configurare database MySQL, URL, chiave applicazione e wallet admin in `.env.php`.

```bash
mysql -u bisped_user -p bisped_net < database/schema.sql
```

Con document root puntato a `public/`, visitare:

```text
/install.php
```

L installer crea lo schema, importa `database/seed-data.php` e scrive `storage/install.lock`.

## Note operative

- Non committare `.env.php`, dump SQL, backup, log o dati cliente.
- Non modificare la produzione WordPress via FTP durante lo sviluppo.
- Il dump `uu4c5pdm_wpb.sql` contiene 116 tabelle importate, WooCommerce 10.7.0, 66 prodotti pubblicati e ordini legacy.
- Il catalogo preview importa 36 prodotti reali come prima tranche amministrabile.
- Il tunnel di preview usa `https://solclawn.com`, gia configurato via cloudflared verso `127.0.0.1:4000`.

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
