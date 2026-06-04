# Automazione contenuti su HOST.it (DirectAdmin Cron)

Questo documento spiega il disallineamento articoli e come far generare contenuti automaticamente direttamente su `bisped.net` in produzione.

---

## Perché solclawn.com aveva nuovi articoli e bisped.net no

Erano due ambienti con **database separati**:

| Ambiente | Dove gira | Database | Cron ingest |
|---|---|---|---|
| `solclawn.com` (vecchia preview) | macchina locale, FrankenPHP `127.0.0.1:4000` | MariaDB locale `bisped_net` | ✅ attivo (`cron-setup.sh`, ogni giorno alle 6:00) |
| `bisped.net` (produzione) | HOST.it / DirectAdmin | MySQL `uu4c5pdm_cms` | ❌ nessuno |

Il cron locale generava articoli nel DB locale → li vedevi su solclawn. Bisped.net, avendo un DB diverso e nessun cron, restava fermo.

**Soluzione:** configurare un cron job su HOST.it (DirectAdmin) che esegue lo stesso `ingest.php`. Avendo `require app/bootstrap.php`, lo script legge automaticamente il `.env.php` di produzione e scrive sul DB `uu4c5pdm_cms` e nelle immagini `public/media/blog/` di HOST.it.

---

## Come la data degli articoli è stata corretta

Prima `published_at` prendeva la data della **fonte RSS** (es. un comunicato Samsung del 28/05 risultava pubblicato il 28/05 anche se generato oggi). Ora `published_at = date('Y-m-d')` (data di pubblicazione su Bisped). Vedi `scripts/auto-update/ingest.php`.

---

## Configurare il cron su DirectAdmin

### 1. Trova il path del PHP CLI su HOST.it

Su DirectAdmin il binario PHP è di solito uno di questi:

```
/usr/local/bin/php
/usr/local/php82/bin/php
/usr/local/php83/bin/php
```

Per scoprire quello esatto, crea un file temporaneo `public/_phppath.php` con:

```php
<?php echo PHP_BINARY;
```

Aprilo: `https://bisped.net/_phppath.php` → ti dice il path. Poi **cancellalo**.

> Nota: il path mostrato dal web (php-fpm) può differire dal CLI. In genere su DirectAdmin il CLI è `/usr/local/bin/php`. Se il cron non parte, prova le varianti sopra.

### 2. Crea il cron job

DirectAdmin → **Funzionalità avanzate** → **Cron Jobs** → **Create Cron Job**.

Imposta:

| Campo | Valore |
|---|---|
| Minute | `0` |
| Hour | `6` |
| Day of Month | `*` |
| Month | `*` |
| Day of Week | `*` |
| Command | (vedi sotto) |

**Command** (sostituisci il path PHP se diverso):

```bash
/usr/local/bin/php /home/uu4c5pdm/domains/bisped.net/public_html/scripts/auto-update/ingest.php --all --limit=3 >> /home/uu4c5pdm/domains/bisped.net/public_html/storage/logs/ingest-cron.log 2>&1
```

Questo genera fino a 3 nuovi articoli al giorno alle 6:00, scrivendoli sul DB di produzione.

### 3. Verifica manuale (prima del cron automatico)

Per testarlo subito senza aspettare le 6:00, esegui il comando una volta via cron "ogni minuto" temporaneo, oppure — se HOST.it offre SSH — direttamente da terminale. In assenza di SSH, imposta temporaneamente il cron a `*/10 * * * *` (ogni 10 min), controlla che gli articoli appaiano, poi rimetti `0 6 * * *`.

Controlla il log:
```
storage/logs/ingest-cron.log
```

### 4. Permessi cartelle

Assicurati che siano scrivibili dal cron (chmod 755 via FTP):
- `storage/` (lock, log, rate-limit Gemini)
- `public/media/blog/` (immagini articoli)

---

## Note importanti

- **Le immagini generate dal cron su HOST.it NON sono in Git.** Restano solo sul server di produzione. Questo è corretto: i contenuti dinamici vivono in produzione, non nel repo.
- **Il deploy FTP non cancella i contenuti generati.** GitHub Actions carica/aggiorna i file del repo ma non rimuove le immagini articoli né tocca il DB.
- **Gemini API:** il cron usa la chiave in `.env.php` (`gemini.api_key`). Rispetta i rate limit configurati. Con `--limit=3` il consumo giornaliero è basso.
- **Quota:** se vuoi più o meno articoli al giorno, cambia `--limit=N` nel comando cron.

---

## Allineamento immediato (opzionale)

Se vuoi portare SUBITO su bisped.net gli articoli già generati in locale (senza aspettare il primo cron), due strade:

1. **Via Agent API** (consigliato): usa il Custom GPT o uno script che legge i blog post dal DB locale e li POSTa su `https://bisped.net/api/agent/v1/blog`, caricando le immagini con `/api/agent/v1/media/fetch`.
2. **Via dump SQL parziale:** esporta la tabella `blog_posts` dal DB locale (phpMyAdmin/mysqldump) e importala su HOST.it, poi carica via FTP le immagini corrispondenti da `public/media/blog/`.

---

## Import automatico prodotti da fornitore (Nexths)

È disponibile un modulo che importa i prodotti dal listino **Nexths** nel catalogo Bisped, con pricing automatico e filtro per categoria.

### Componenti

- `app/Services/Catalog/Suppliers/NexthsAdapter.php` — legge il listino Nexths (CSV o API)
- `app/Services/Catalog/ProductImporter.php` — upsert prodotti (match per SKU), pricing, filtro categorie
- `scripts/auto-update/import-products.php` — comando CLI per il cron

### Cosa fa

1. Legge il listino Nexths (file CSV scaricato dal portale, oppure API)
2. **Filtra le categorie**: importa solo smartphone, notebook, desktop, componenti-pc, gaming, tablet, accessori. Scarta il resto (es. casalinghi).
3. **Calcola il prezzo di vendita** dal costo B2B: `costo × (1 + markup) × (1 + IVA)`, con arrotondamento a `,90`. Markup configurabile per categoria.
4. **Upsert per SKU**: se il prodotto esiste aggiorna prezzo e disponibilità, altrimenti lo crea.

### Cosa devi verificare/fornire (Nexths)

Il mapping delle colonne e i campi API sono **presunti** e vanno confermati col tracciato reale:

1. Accedi al portale Nexths e verifica **come scaricano il listino**: file CSV/Excel oppure API.
2. **Se CSV**: scarica un file di esempio e controlla le intestazioni delle colonne. Se non combaciano con quelle in `NexthsAdapter::$columnMap`, aggiungile lì (è già pronto per molte varianti italiane: codice, descrizione, prezzo, categoria, marca, disponibilità).
3. **Se API**: recupera endpoint e credenziali, poi adatta `NexthsAdapter::parseApiRow()`.

### Configurazione (`.env.php`)

```php
'catalog' => [
    'enabled'        => true,
    'markup_default' => 0.18,
    'vat'            => 0.22,
    'markup' => [
        'smartphone'    => 0.12,
        'notebook'      => 0.15,
        'componenti-pc' => 0.20,
        'gaming'        => 0.22,
        'accessori'     => 0.30,
    ],
    'nexths' => [
        'mode'     => 'csv',
        'csv_path' => '/home/uu4c5pdm/domains/bisped.net/public_html/storage/imports/nexths.csv',
        'csv_delimiter' => ';',
        // oppure mode='api' con api_url + api_key
    ],
],
```

### Test prima di andare live

```bash
# Dry-run: non scrive nulla, mostra solo cosa farebbe
php scripts/auto-update/import-products.php --supplier=nexths --dry-run --verbose
```

### Cron (DirectAdmin)

```bash
/usr/local/bin/php /home/uu4c5pdm/domains/bisped.net/public_html/scripts/auto-update/import-products.php --supplier=nexths >> /home/uu4c5pdm/domains/bisped.net/public_html/storage/logs/products-cron.log 2>&1
```

### Workflow CSV consigliato

Finché Nexths non offre un'API, il flusso più semplice è:
1. Scarichi periodicamente il listino CSV dal portale Nexths
2. Lo carichi via FTP in `storage/imports/nexths.csv`
3. Il cron giornaliero lo importa e aggiorna prezzi/disponibilità

> Le foto prodotto: se il CSV Nexths include URL immagini, si può estendere l'importer per scaricarle via lo stesso meccanismo `media/fetch` dell'Agent API. Da abilitare quando si conferma che il listino contiene gli URL.
