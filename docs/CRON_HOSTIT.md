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

## Import automatico prodotti da fornitore (Runner S.p.A.)

Modulo che importa i prodotti dal listino **Runner** nel catalogo Bisped, con pricing automatico e filtro per categoria. Basato sul tracciato ufficiale Runner (file `.txt` pipe-delimited via FTP).

### Componenti

- `app/Services/Catalog/Suppliers/RunnerAdapter.php` — scarica e joina i tracciati Runner
- `app/Services/Catalog/ProductImporter.php` — upsert per SKU, pricing, filtro categorie
- `scripts/auto-update/import-products.php` — comando CLI per il cron

### Come funziona

1. **Scarica via FTP** dal server Runner:
   - `articoli.txt` (anagrafica: codice, descrizione, produttore, categoria, disponibilità)
   - `{CODICE_CLIENTE}/prezzi.txt` (il TUO prezzo di acquisto personalizzato)
   - `immagini.txt` (URL foto prodotto)
   - `descp.txt` (descrizioni estese)
2. **Joina per Codice** anagrafica + prezzo + immagine + descrizione.
3. **Filtra le categorie**: importa solo smartphone, notebook, desktop, componenti-pc, gaming, tablet, connettività, accessori. Scarta il resto (es. elettrodomestici).
4. **Calcola il prezzo di vendita** dal costo B2B (`PrezzoPers`): `costo × (1 + markup) × (1 + IVA)`, arrotondato a `,90`. Markup configurabile per categoria.
5. **Upsert per SKU**: se il prodotto esiste aggiorna prezzo e disponibilità, altrimenti lo crea.

### ⚠️ Host FTP Runner

`ftp.runner.it` è dietro **Cloudflare** e NON instrada il traffico FTP. Il vero host FTP è un altro: cercalo nella mail con le credenziali (user `C111445`), oppure aprilo un ticket nell'area assistenza Runner. Inserisci quell'host in `catalog.runner.ftp_host`.

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
        'connettivita'  => 0.20,
        'accessori'     => 0.30,
    ],
    'runner' => [
        'ftp_host'      => 'HOST_FTP_RUNNER',   // dalla mail Runner (non ftp.runner.it)
        'ftp_user'      => 'C111445',
        'ftp_pass'      => '••••••••',
        'ftp_port'      => 21,
        'ftp_ssl'       => false,               // true se Runner usa FTPS
        'customer_code' => 'C111445',           // cartella prezzi personalizzati
        'work_dir'      => '/home/uu4c5pdm/domains/bisped.net/public_html/storage/imports/runner',
    ],
],
```

### Test prima di andare live

```bash
# Dry-run: non scrive nulla, mostra solo cosa farebbe
php scripts/auto-update/import-products.php --supplier=runner --dry-run --verbose
```

### Due cron: disponibilità (6h) + catalogo completo (24h)

La disponibilità è critica: **non vogliamo a catalogo prodotti non disponibili su Runner**. Per questo ci sono due modalità separate.

| Modalità | Cosa fa | Frequenza |
|---|---|---|
| `--mode=availability` | Scarica solo `dispo.txt` (leggero), mette `esaurito` ciò che è a 0 e `disponibile` ciò che torna. Non crea/rimuove. | **ogni 6h** |
| `--mode=full` | Import completo: crea nuovi, aggiorna prezzi, esaurisce stock 0, marca `ritirato` i prodotti non più nel listino. | **ogni 24h** |

**Cron su DirectAdmin** (sostituisci il path PHP se diverso):

```bash
# Disponibilità — ogni 6 ore
0 */6 * * *  /usr/local/php81/bin/php /home/uu4c5pdm/domains/bisped.net/public_html/scripts/auto-update/import-products.php --supplier=runner --mode=availability >> /home/uu4c5pdm/domains/bisped.net/public_html/storage/logs/products-cron.log 2>&1

# Catalogo completo — ogni giorno alle 4:00
0 4 * * *    /usr/local/php81/bin/php /home/uu4c5pdm/domains/bisped.net/public_html/scripts/auto-update/import-products.php --supplier=runner --mode=full >> /home/uu4c5pdm/domains/bisped.net/public_html/storage/logs/products-cron.log 2>&1
```

### Sicurezza del prune

Il prune (rimozione prodotti non più nel listino) gira **solo** se l'import legge almeno 500 prodotti, per evitare di svuotare il catalogo se il feed FTP arriva vuoto o corrotto. I prodotti rimossi passano a `ritirato`, non vengono cancellati dal DB.

### Pricing (formula)

```
prezzo_vendita = ( costo_acquisto × (1 + markup%) + markup_fisso ) × (1 + IVA)
```
arrotondato a `,90`. Esempi reali verificati:
- Cavo USB costo €3,16 → **€11,90** (i 5€ fissi scoraggiano la minuteria)
- SSD Crucial 500GB costo €84,66 → **€119,90**
- Notebook Acer i9 costo €700 → **€934,90** (markup 8%)
- iPhone 17 Pro Max costo €1262 → **€1672,90**

Parametri in `catalog` (`.env.php`): `markup_default`, `markup_fixed`, `vat`, override `markup[categoria]` e `fixed[categoria]`. Modificabili anche dalla dashboard admin (Impostazioni → Catalogo).

### Famiglie escluse

Non vengono importate: Elettrodomestici/Articoli regalo, Educational, Digital Signage, Software, Ufficio e Consumabili. Modificabile in `ProductImporter::FAMILY_EXCLUDE` o via `catalog.family_exclude`.

### Note

- **Prezzo:** `PrezzoPers` è il TUO prezzo di acquisto netto (cartella `C111445/prezzi.txt`). Il prezzo di vendita lo calcola l'importer con markup + IVA. Solo i prodotti con un prezzo personalizzato vengono importati.
- **Immagini:** il tracciato fornisce URL (`immagini.txt`). Per ora salviamo l'URL remoto Runner; volendo si possono scaricare in locale con lo stesso meccanismo `media/fetch` dell'Agent API.
- **Categorie:** il filtro usa `DescCatMerc`. Se Runner usa nomi categoria non ancora mappati, aggiungili in `ProductImporter::$categoryMap`.
- **Permessi:** assicurati che `storage/imports/runner/` sia scrivibile (chmod 755).
