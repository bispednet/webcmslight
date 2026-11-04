# Sviluppo locale Windows di Bisped

La copia locale usa PHP 8.3.36 e MySQL 8.0.45 portatili nella cartella `runtime/`.
I programmi ascoltano solo su `127.0.0.1`. Node.js gestisce avvio, arresto,
raccolta delle email e protezione dell'anteprima condivisibile.

## Utilizzo quotidiano

- Aprire `Avvia-Locale.cmd` per avviare l'ambiente.
- Sito: <http://127.0.0.1:8080>.
- Catalogo: <http://127.0.0.1:8080/products>.
- Login CMS: <http://127.0.0.1:8080/login>.
- Credenziali di sviluppo: `.local/ACCESSI-LOCALI.txt`.
- Aprire `Arresta-Locale.cmd` per arrestare l'ambiente, incluso MySQL.

Salvare un file e aggiornare il browser per vedere la modifica. I contenuti
modificati dal CMS vengono salvati nel database `bisped_local` del PC.
Il collegamento MySQL per un editor esterno è `127.0.0.1:3307`.
Nome database, utente e password si trovano nel file privato degli accessi.

Avvio, arresto e stato sono disponibili anche da terminale:

```powershell
node scripts/dev/manage.mjs start
node scripts/dev/manage.mjs status
node scripts/dev/manage.mjs stop
```

## Configurazione e dati

`scripts/dev/clone-production.mjs config-db` recupera via SSH il file originale
di configurazione in `.local/production.env.php` e crea un dump privato del DB
in `.local/backups/`. Il dump usa una transazione consistente senza lock delle
tabelle. Le credenziali MySQL non vengono passate nella riga di comando.

`scripts/dev/clone-production.mjs media` scarica l'archivio delle immagini.
Durante il primo setup vengono importati i dati e copiate le immagini mancanti.
Un setup successivo conserva il database locale e i file già presenti.

Il `.env.php` locale è un caricatore. La configurazione completa modificabile è
in `.env.local.php`, copiata dall'hosting e separata dalla produzione.
`scripts/dev/local-config.php` applica inoltre le impostazioni locali di
`.local/environment.json` e le variazioni AI di `.local/ai-overrides.json`:

- Il database viene sostituito integralmente con quello locale.
- Le chiavi dell'applicazione, le sessioni e l'accesso amministratore sono separati.
- Provider, modelli e parametri AI sono modificabili da `/_dev/ai`, dopo il login
  amministratore. I tre profili indipendenti sono chatbot, news e configuratore PC.
  Le chiamate AI usano le chiavi configurate e possono consumare credito.
- Le email vengono salvate come `.eml` in `.local/mail`, senza consegna esterna.
- PayPal usa sandbox e non riceve le credenziali live.
- Google OAuth, Calendar e invii WhatsApp sono disabilitati; possono richiedere
  credenziali e callback di test dedicati prima di essere verificati.
- Il catalogo Runner viene aggiornato insieme a prezzi e stock ogni ora mentre
  l'ambiente è acceso. `auto_catalog_sync=false` nel JSON sospende questo job.
- Analytics e Turnstile sono disattivati nell'anteprima.
- Gli altri cron di produzione non vengono installati sul PC.

La cartella `.local/` contiene dati reali e segreti: conservarla sul PC.
`.local/`, `runtime/`, `.cloudflared/`, `github/`, certificati, dump e contenuti
scaricati sono esclusi da Git. Anche i nuovi file di `.github/` sono ignorati;
il workflow di produzione già versionato resta tracciato e ha esclusioni
aggiuntive per questi file. L'identità Git del repository è `bispednet`.

## Staging con Cloudflare

Indirizzo: <https://stag.yed1z.ai>. Prima viene richiesta la password del proxy;
poi il login CMS permette di aprire <https://stag.yed1z.ai/_dev/ai>.

Il proxy di anteprima ascolta su `127.0.0.1:8081` e richiede sempre la password
specificata in `.local/ACCESSI-LOCALI.txt`. Imposta anche `noindex` e disabilita
la cache. Questa è la destinazione da esporre con il tunnel, anziché la porta
locale 8080. Il sito condiviso rimane disponibile mentre PC e servizi sono accesi.

Il certificato Cloudflare va in `.cloudflared/cert-yed1z.ai.pem`.
La configurazione è in `.cloudflared/staging.yml` e viene avviata insieme
all'ambiente. Il record DNS `stag.yed1z.ai` è associato al tunnel dedicato.

## Verifiche

```powershell
runtime/php/php.exe scripts/dev/check-local.php
node scripts/dev/smoke-local.mjs
node scripts/dev/smoke-checkout.mjs
node scripts/dev/smoke-staging.mjs
runtime/php/php.exe scripts/test-catalog-safety.php
node scripts/dev/check-secrets.mjs --working
```

Il primo comando controlla isolamento e scrittura del database (con rollback),
conservazione dei modelli e impostazioni dei servizi. Il secondo verifica home,
catalogo, blog, login amministratore, immagini, file riservati e password staging.
I log sono in `.local/logs/`.

Gli hook locali pre-commit e pre-push bloccano file riservati, chiavi API
riconoscibili e segreti presenti nelle configurazioni private. L'insieme dei file
da pubblicare si controlla con `git diff --cached --name-only`.
