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
/usr/local/php81/bin/php
/usr/local/php82/bin/php
/usr/local/php83/bin/php
```

Per scoprire quello esatto, crea un file temporaneo `public/_phppath.php` con:

```php
<?php echo PHP_BINARY;
```

Aprilo: `https://bisped.net/_phppath.php` → ti dice il path. Poi **cancellalo**.

> Nota: il path mostrato dal web (php-fpm) può differire dal CLI. In genere su DirectAdmin il CLI è `/usr/local/php81/bin/php`. Se il cron non parte, prova le varianti sopra.

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
/usr/local/php81/bin/php /home/uu4c5pdm/domains/bisped.net/public_html/scripts/auto-update/ingest.php --all --limit=3 >> /home/uu4c5pdm/domains/bisped.net/public_html/storage/logs/ingest-cron.log 2>&1
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

## Import automatico prodotti da Runner

Il riferimento completo per protezioni, prezzi e pubblicazione e' [CATALOG_SAFETY.md](CATALOG_SAFETY.md).

Il server verificato e' `runnertechstore.it`, porta `21`, FTP standard
(`ftp_ssl=false`). Credenziali e codice cliente restano nel file privato `.env.php`.
I file obbligatori sono `articoli.txt`, `{CODICE_CLIENTE}/prezzi.txt` e
`dispo.txt`: vengono scaricati e verificati insieme. Una fotografia fallita
non riutilizza file precedenti. Immagini e descrizioni vengono aggiornate nel full.

### Aggiornamenti commerciali ogni ora, catalogo completo ogni giorno

| Modalita | Operazioni | Frequenza |
|---|---|---|
| `commercial` | Verifica appartenenza, costo, prezzo e stock; ritira gli assenti | Ogni ora |
| `full` | Esegue le stesse verifiche e aggiunge nuovi articoli con immagini | Ogni giorno alle 4 |

Il vecchio nome `availability` resta un alias di `commercial`.

```bash
0 * * * * /opt/alt/php83/usr/bin/php /home/uu4c5pdm/domains/bisped.net/public_html/scripts/auto-update/import-products.php --supplier=runner --mode=commercial >> /home/uu4c5pdm/domains/bisped.net/public_html/storage/logs/products-cron.log 2>&1
0 4 * * * /opt/alt/php83/usr/bin/php /home/uu4c5pdm/domains/bisped.net/public_html/scripts/auto-update/import-products.php --supplier=runner --mode=full >> /home/uu4c5pdm/domains/bisped.net/public_html/storage/logs/products-cron.log 2>&1
```

Conservare gli altri cron del sito. L'import completo puo durare alcuni minuti.
Il lock sul database impedisce due riconciliazioni contemporanee; il controllo
pre-incasso puo aggiornare un singolo articolo senza attendere il cron.

### Prezzo e sicurezza

Il costo d'acquisto e' netto IVA. Il ricarico e' 20% fino a 150 EUR,
scende linearmente al 5,5% a 1.000 EUR e rimane 5,5% oltre la soglia.
Si aggiungono sempre 5 EUR, poi IVA; l'arrotondamento verso l'alto termina a ,90.
Nella fascia alta e' richiesto almeno un ricarico netto di 50 EUR prima delle spese.
I parametri DB in Impostazioni hanno precedenza sulla configurazione del file.

Un articolo senza costo valido, esaurito, ritirato, scaduto o sotto soglia non
e' acquistabile. L'assenza dal catalogo porta a ritiro e stock zero, senza
cancellare lo storico. Le vecchie promozioni vengono eliminate dal ricalcolo.
Il pruning richiede almeno 500 righe e controlli sulla completezza del feed.

### Verifiche manuali

```bash
php scripts/auto-update/import-products.php --supplier=runner --mode=commercial --dry-run
php scripts/diagnose-catalog-safety.php
```

La dashboard segnala ultimo aggiornamento, errori e dati scaduti. Le cartelle
`storage/imports/runner` e `public/media/products/runner` devono essere scrivibili.
Le foto scaricate sono locali; i dati commerciali vengono aggiornati anche se
una fotografia non e' disponibile.
