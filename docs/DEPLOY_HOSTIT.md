# Deploy su HOST.it — Guida completa

Questa guida copre il primo deploy e il workflow di aggiornamento continuo (modifica in locale → push → deploy automatico).

---

## Prerequisiti

- Account HOST.it con accesso **cPanel** o pannello equivalente
- Credenziali FTP (host, utente, password)
- Accesso **phpMyAdmin** per il database
- Repository su **GitHub** (già configurato)
- PHP **8.2 o superiore** sul server (verifica nel pannello HOST.it)

---

## 1. Verifica PHP version su HOST.it

1. Accedi al pannello HOST.it
2. Cerca **"Select PHP Version"** o **"PHP Version Manager"**
3. Seleziona **PHP 8.2** o 8.3
4. Attiva le estensioni: `pdo_mysql`, `mbstring`, `curl`, `json`, `fileinfo`

Se la versione PHP non è disponibile, contatta il supporto HOST.it.

---

## 2. Database — prima configurazione (una volta sola)

### Crea il database

1. Pannello HOST.it → **MySQL Databases** (o **phpMyAdmin**)
2. Crea un nuovo database, es. `bisped_net`
3. Crea un utente, es. `bisped_user`, con password sicura
4. Assegna **tutti i privilegi** dell'utente al database

### Importa lo schema

1. Apri **phpMyAdmin** → seleziona il database `bisped_net`
2. Clicca su **Import**
3. Carica il file `database/schema.sql` dalla repo
4. Esegui l'import

### Esegui le migration AI Concierge

1. Sempre in phpMyAdmin → tab **SQL**
2. Copia e incolla il contenuto di `database/schema.sql` dalla sezione `ai_conversations` in poi (se non era già nel file importato)
3. Oppure usa il file `scripts/migrate-ai-concierge.php`:
   - Caricalo temporaneamente via FTP nella root
   - Aprilo nel browser: `https://bisped.net/migrate-ai-concierge.php`
   - Cancellalo dopo l'esecuzione

---

## 3. Configurazione .env.php (una volta sola, mai in Git)

1. Apri un editor di testo locale
2. Copia `/.env.example.php` e rinominalo `.env.php`
3. Compila tutti i valori:

```php
'app' => [
    'env'   => 'production',
    'debug' => false,
    'url'   => 'https://bisped.net',   // ← URL produzione
    'key'   => 'base64:GENERA_CON_PHP', // ← bin2hex(random_bytes(32))
],
'database' => [
    'host'     => 'localhost',          // ← solitamente localhost su HOST.it
    'port'     => 3306,
    'database' => 'bisped_net',         // ← nome DB creato sopra
    'username' => 'bisped_user',
    'password' => 'PASSWORD_SICURA',
],
'gemini' => [
    'api_key' => 'LA_TUA_CHIAVE_GEMINI',
],
'ai_concierge' => [
    'whatsapp_number' => '393346582116',  // ← numero WhatsApp reale Bisped
],
'whatsapp' => [
    'phone_number' => '393346582116',
],
'agent' => [
    'api_key' => 'GENERA_CON_PHP_bin2hex_random_bytes_32',
],
```

**Genera le chiavi sicure:**

Apri qualsiasi interprete PHP online (es. 3v4l.org) e lancia:
```php
echo bin2hex(random_bytes(32));
```

Ottieni due valori: uno per `app.key` e uno per `agent.api_key`.

4. Carica `.env.php` via FTP nella **root del sito** (stessa cartella di `README.md`)
5. Verifica che non sia accessibile dal web: `https://bisped.net/.env.php` deve dare **403** o **404**

---

## 4. Struttura directory su HOST.it

```
/public_html/        ← document root (o /httpdocs/ dipende da HOST.it)
├── public/          ← questo è il document root PHP (vedi punto 5)
│   ├── index.php
│   ├── admin.php
│   ├── assets/
│   └── media/
├── app/
├── database/
├── docs/
├── scripts/
├── .env.php         ← NON in Git, caricato manualmente
└── ...
```

---

## 5. Imposta il Document Root

Il sito deve rispondere da `public/`, non dalla root del repo.

**Su HOST.it con cPanel:**
1. cPanel → **Domains** o **Addon Domains**
2. Trova `bisped.net` e clicca **Manage**
3. Cambia **Document Root** da `/public_html` a `/public_html/public`
4. Salva

**Se non è possibile cambiare il document root:**
- Crea un file `.htaccess` nella root con:
```apache
RewriteEngine On
RewriteRule ^(.*)$ public/$1 [L]
```

---

## 6. Primo caricamento via FTP (una volta sola)

Usa un client FTP come **FileZilla** o **Cyberduck**.

**Connessione:**
- Host: (credenziali da pannello HOST.it, es. `ftp.bisped.net`)
- Porta: 21 (o 22 per SFTP se disponibile)
- Utente e password FTP

**Cosa caricare:**

Carica TUTTO il contenuto della repo **tranne**:
- `runtime/` (server locale, non serve)
- `.env.php` (già caricato al punto 3)
- `storage/gemini-rate-limit.json` (si ricrea da solo)

**Cartelle da caricare:**
```
app/        → carica tutto
database/   → carica tutto
docs/       → carica tutto
public/     → carica tutto
scripts/    → carica tutto
.htaccess   → se c'è nella root
.github/    → non serve su FTP, serve solo su GitHub
```

---

## 7. Verifica funzionamento

Dopo il caricamento:

```
1. https://bisped.net/health/db          → deve rispondere {"ok":true}
2. https://bisped.net/                   → home page
3. https://bisped.net/admin              → pannello admin (login)
4. https://bisped.net/.env.php           → deve dare 403/404 (mai accessibile)
5. https://bisped.net/api/agent/v1/ping  → {"ok":true,"site":"Bisped"}
   (con header Authorization: Bearer {agent.api_key})
```

---

## 8. Deploy automatico per aggiornamenti futuri

Una volta fatto il primo deploy manuale, tutti gli aggiornamenti successivi avvengono **automaticamente** tramite GitHub Actions.

### Come funziona

```
Tu lavori in locale
      ↓
git commit + git push → branch main
      ↓
GitHub Actions esegue il workflow .github/workflows/deploy-hostit.yml
      ↓
FTP Deploy carica SOLO i file modificati su HOST.it
      ↓
Sito aggiornato in 1-3 minuti, senza fare nulla manualmente
```

### Configurazione GitHub Actions (una volta sola)

1. Vai su **GitHub → Repository → Settings → Secrets and variables → Actions**
2. Aggiungi questi **Repository secrets**:

| Secret | Valore |
|--------|--------|
| `FTP_HOST` | `ftp.bisped.net` o come indicato da HOST.it |
| `FTP_USER` | Utente FTP (es. `bisped@bisped.net`) |
| `FTP_PASS` | Password FTP |
| `FTP_REMOTE_DIR` | `/public_html/` o `/httpdocs/` (directory root su HOST.it) |

3. Vai su **GitHub → Repository → Settings → Actions → General**
4. Abilita **"Allow all actions and reusable workflows"**

### Primo test del deploy automatico

1. Fai una modifica qualsiasi (es. un commento in un file PHP)
2. `git add . && git commit -m "test: verifica deploy automatico"`
3. `git push origin main`
4. Vai su **GitHub → Repository → Actions** → vedrai il workflow in esecuzione
5. Dopo 1-3 minuti, la modifica è online

### Se il deploy fallisce

- Vai su **GitHub → Actions → ultimo workflow → View logs**
- Gli errori FTP più comuni:
  - Credenziali errate → ricontrolla i secrets
  - `server-dir` sbagliato → verifica la directory root su HOST.it
  - Timeout → normale su primo deploy con molti file; riprovare

---

## 9. Workflow quotidiano (dopo la configurazione)

```bash
# 1. Lavora sul codice in locale (branch feature)
git checkout -b feature/mia-modifica
# ... modifica file ...

# 2. Testa in locale con FrankenPHP
./runtime/bin/frankenphp php-server --root public --listen 127.0.0.1:4000

# 3. Commit e push sul branch feature
git add .
git commit -m "feat: descrizione modifica"
git push origin feature/mia-modifica

# 4. Merge su main (da GitHub o localmente)
git checkout main
git merge feature/mia-modifica
git push origin main
# ↑ GitHub Actions scatta automaticamente e fa il deploy

# 5. Opzionale: controlla il deploy
# GitHub → Actions → workflow in corso
```

---

## 10. Modifiche al database dopo il primo deploy

Se aggiungi colonne o tabelle:

1. Scrivi il SQL in un file es. `database/migrations/20260602-agent-image.sql`
2. Eseguilo manualmente in **phpMyAdmin** → tab SQL
3. Documenta la migration nel file

Non esiste un sistema di migration automatico (non è un framework). Le migration si applicano manualmente via phpMyAdmin.

---

## 11. Troubleshooting comune

| Problema | Soluzione |
|---|---|
| Pagina bianca | Abilita `display_errors=1` in `.env.php` temporaneamente |
| 500 Internal Server Error | Controlla `storage/logs/` o i log di HOST.it in cPanel |
| CSS/JS non caricati | Verifica che `public/assets/` sia caricato e `mod_rewrite` sia attivo |
| DB connection failed | Controlla host (spesso `localhost`, non `127.0.0.1` su shared hosting) |
| `.htaccess` non funziona | Verifica che `mod_rewrite` sia abilitato in cPanel → Apache Handlers |
| Sessioni non funzionano | Aggiungi `session_save_path = '/tmp'` in `.htaccess` o in `.env.php` |
| AI Concierge non risponde | Verifica `gemini.api_key` in `.env.php` e la quota Gemini |

---

## 12. Sicurezza in produzione

- `.env.php` NON in Git, NON accessibile via web
- `app.debug = false` in produzione
- `public/install.php` disabilitato (richiede `BISPED_ALLOW_WEB_INSTALL=1`)
- `agent-openapi.json`: blocca per IP nel `.htaccess` una volta configurato il GPT
- Ruota `agent.api_key` se compromessa (cambia in `.env.php` e nel Custom GPT)
