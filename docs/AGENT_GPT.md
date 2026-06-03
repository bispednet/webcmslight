# Gestore Bisped.net — Custom GPT

Guida completa per configurare e usare il Custom GPT che gestisce il sito Bisped tramite chat.

---

## Cosa puoi fare

| Funzione | Esempi |
|----------|--------|
| **Prodotti** | Crea, modifica, aggiorna prezzi, cambia disponibilità, elimina |
| **Immagini** | Carica immagini da URL esterno, le salva sul server |
| **Blog** | Crea articoli, modifica testi, pubblica/nascondi post |
| **Lead AI** | Vedi lead qualificati dal Concierge con report commerciale |
| **Messaggi** | Leggi messaggi di contatto in arrivo |
| **Appuntamenti** | Vedi richieste appuntamento in sospeso |
| **Statistiche** | Dashboard rapida: prodotti, lead oggi, messaggi non letti |

---

## Prerequisiti

1. Account **ChatGPT Plus** o Team (il Custom GPT richiede GPT-4+)
2. Il sito **bisped.net** online e funzionante
3. La chiave API generata in `.env.php` sotto `agent.api_key`

---

## Setup — passo dopo passo

### 1. Vai su ChatGPT → My GPTs

Apri [chat.openai.com](https://chat.openai.com) → clicca sul tuo avatar in alto a destra → **My GPTs** → **Create a GPT**.

### 2. Scheda **Configure**

Clicca su **Configure** (non Create).

#### Name
```
Gestore Bisped.net
```

#### Description
```
Gestisce prodotti, blog, lead e statistiche del sito Bisped.net tramite API diretta. Parla italiano.
```

#### Instructions (copia tutto il blocco qui sotto)

```
Sei l'assistente CMS di Bisped srl — negozio informatica, telefonia ed energia a Piombino (LI), Toscana.
Gestisci il sito bisped.net tramite le API collegate. Parli sempre italiano.

═══════════════════════════════════════
IDENTITÀ BISPED
═══════════════════════════════════════
Bisped è un negozio fisico e online che vende e assiste:
- Smartphone: Samsung Galaxy (S25/S24, A series, Z Fold6/Flip6), iPhone (15/16), Xiaomi, Motorola, Oppo
- PC e notebook: Acer, Asus, Lenovo, HP, MSI — nuovi e ricondizionati
- Componenti hardware: GPU (Nvidia RTX, AMD RX), RAM, SSD, HDD
- Riparazioni: schermi, batterie, schede madri (smartphone, tablet, notebook)
- Teleassistenza da remoto: Windows, configurazioni, virus
- Contratti fibra/FWA/mobile: Fastweb, TIM, WindTre, Vodafone, Iliad, Eolo, Sky Wifi
- Contratti energia: luce e gas per privati e aziende (Edison, Enel, ENI, A2A, Estra)

═══════════════════════════════════════
COSA PUOI FARE
═══════════════════════════════════════

1. PRODOTTI
   - Creare nuovi prodotti nel catalogo
   - Aggiornare prezzi, descrizioni, disponibilità
   - Eliminare prodotti obsoleti
   - Caricare immagini da URL (prima usa fetchMedia per salvare l'URL locale)

2. BLOG
   - Creare articoli (in italiano, con is_published: true per pubblicare subito)
   - Aggiornare articoli esistenti
   - Il titolo in inglese (title_en) è opzionale

3. LEAD AI CONCIERGE
   - Vedere chi ha usato il chatbot del sito con i dati qualificati
   - Leggere il report commerciale per ogni lead (budget, dispositivo, operatore, etc.)
   - Filtrare per settore: tlc, informatica, energia_amministrativo

4. MESSAGGI E APPUNTAMENTI
   - Leggere messaggi di contatto non ancora letti
   - Vedere appuntamenti in sospeso

5. STATISTICHE
   - Dashboard veloce: quanti prodotti, lead oggi, messaggi nuovi

6. IMMAGINI
   - Caricare immagine da URL esterno: usa fetchMedia con l'URL
   - Il server scarica e salva l'immagine, restituisce l'URL locale
   - Usa l'URL locale nel campo image_url del prodotto o del blog post

═══════════════════════════════════════
REGOLE FERME
═══════════════════════════════════════
- Parla sempre in italiano
- Prima di ELIMINARE qualcosa: mostra cosa stai per eliminare e chiedi "Confermi l'eliminazione?"
- Prima di modificare più di 5 prodotti in serie: chiedi conferma
- NON inventare prezzi, disponibilità o informazioni non presenti nell'API
- Se non trovi qualcosa, di' "Non trovato nell'API" — non inventare
- I prezzi sono sempre in euro (€)
- Per i lead: mostra solo i dati che l'API restituisce, non fare previsioni commerciali

═══════════════════════════════════════
VALORI STANDARD
═══════════════════════════════════════
stock_status: "disponibile" | "esaurito" | "su ordinazione" | "in arrivo" | "ritirato"

category (prodotti tipici Bisped):
  "smartphone" | "notebook" | "desktop" | "componenti-pc" | "tablet" | "accessori"
  "fibra-casa" | "mobile" | "fwa" | "energia-luce" | "energia-gas" | "riparazione" | "servizi"

featured_order: 0 = prodotto in evidenza (appare per primo), numeri più alti = ordine normale

═══════════════════════════════════════
COME MOSTRO I DATI
═══════════════════════════════════════
- Tabelle markdown per liste di prodotti/lead
- Testo strutturato per report commerciali
- Numeri interi per conteggi
- Quando modifico qualcosa, confermo con "✓ [cosa] aggiornato/creato/eliminato"
```

#### Conversation Starters (aggiungi questi)

```
Mostrami le statistiche del sito: prodotti, lead oggi e messaggi non letti
```
```
Quali prodotti ho nel catalogo? Mostrami quelli senza prezzo impostato
```
```
Mostrami i lead di oggi con il report commerciale
```
```
Crea un articolo del blog: "Samsung Galaxy S25 Ultra — perché sceglierlo"
```

#### Capabilities

- ✅ **Web Search** — attiva (utile per trovare immagini prodotti)
- ☐ Canvas — non necessario
- ✅ **Image Generation** — opzionale
- ✅ **Code Interpreter** — utile per analisi dati lead

---

### 3. Aggiungi l'Action

1. Scorri in basso fino a **Actions** → clicca **"Create new action"**

2. Nella scheda **Authentication**:
   - Tipo: **API Key**
   - Auth type: **Bearer**
   - API Key: incolla il valore di `agent.api_key` da `.env.php`

3. Clicca **"Import from URL"** e incolla:
   ```
   https://bisped.net/agent-openapi.json
   ```
   Oppure apri il file `public/agent-openapi.json` dal repo e incolla il contenuto nel campo **Schema**.

4. Clicca **"Test"** accanto a `ping` → deve rispondere `{"ok":true,"site":"Bisped"}`

5. Clicca **Save** in alto a destra

---

### 4. Pubblica il GPT

- **Only me** — solo tu (consigliato)
- **Anyone with a link** — condividi con il team
- **Public** — visibile a tutti (non consigliare per dati aziendali)

---

## Esempi di utilizzo

### Gestione prodotti

```
"Quanti prodotti ho nel catalogo?"
"Mostrami tutti gli smartphone"
"Aggiungi: Samsung Galaxy S25 Ultra, categoria smartphone, prezzo 1199€, disponibile"
"Metti il Galaxy A55 in esaurito"
"Aggiorna la descrizione del prodotto ID 12"
"Elimina il prodotto 'Nokia 3310' — è obsoleto"
```

### Immagini prodotti

```
"Salva questa immagine sul server: https://esempio.com/samsung-s25.jpg"
→ GPT usa fetchMedia, ottieni /media/agent/20260603-abc123.jpg

"Aggiorna il prodotto ID 12 impostando image_url = /media/agent/20260603-abc123.jpg"
```

### Blog

```
"Crea un articolo del blog su Samsung Galaxy Z Fold6:
 titolo, 3 paragrafi HTML, snippet di 100 parole, data oggi, pubblicato subito"

"Mostrami gli ultimi 5 articoli del blog"
"Metti il post ID 8 non pubblicato (is_published: false)"
```

### Lead e commerciale

```
"Mostrami i lead di oggi"
"Dammi i dettagli del lead ID 42 con il report commerciale"
"Quanti lead ho nel settore TLC?"
"Mostrami i lead con urgenza alta"
```

### Operazioni multiple

```
"Metti tutti i prodotti iPhone come 'in arrivo' (aggiornamento di massa)"
→ GPT chiede conferma prima di procedere

"Crea 3 prodotti: Samsung A25 a 299€, A35 a 399€, A55 a 499€, tutti disponibili, categoria smartphone"
→ GPT crea uno alla volta e conferma
```

---

## Cosa il GPT NON può fare

| Limitazione | Workaround |
|-------------|------------|
| Caricare file direttamente (upload fisico) | Usa `fetchMedia` con URL pubblico dell'immagine |
| Accedere al filesystem del server | Non necessario — tutto via API |
| Modificare `.env.php` o configurazioni | Farlo manualmente via FTP |
| Eseguire script PHP | Farlo manualmente via phpMyAdmin o browser |
| Vedere il contenuto delle sessioni utente | Non disponibile per privacy |
| Modificare lo schema del database | Usare phpMyAdmin |

---

## Sicurezza della chiave API

La chiave `agent.api_key` dà **accesso completo** al CMS. Trattala come una password.

**Cose da fare:**
- Tienila segreta, non condividerla
- Non metterla in chat pubbliche o screenshot
- Ruotala ogni 6 mesi o se sospetti compromissione

**Come ruotarla:**
1. Genera una nuova chiave: `bin2hex(random_bytes(32))` in qualsiasi PHP online
2. Aggiorna `.env.php` sul server via FTP
3. Aggiorna la chiave nel Custom GPT → Actions → Authentication → edit

**Se la chiave viene compromessa:**
1. Cambia immediatamente `.env.php` sul server
2. Il vecchio GPT smette di funzionare istantaneamente
3. Configura la nuova chiave nel GPT

---

## Aggiornare lo schema OpenAPI

Se l'API viene estesa (nuovi endpoint), devi aggiornare il GPT:

1. La nuova spec è disponibile su `https://bisped.net/agent-openapi.json`
2. Nel GPT → Actions → clicca l'action esistente → **"Import from URL"** di nuovo
3. Salva

---

## Risoluzione problemi

| Errore | Causa | Soluzione |
|--------|-------|-----------|
| `401 Unauthorized` | Chiave sbagliata o mancante | Verifica `agent.api_key` in `.env.php` e nel GPT |
| `404 Not Found` | Endpoint non trovato | Verifica che il sito sia online e la route registrata |
| `422 Unprocessable` | Dati mancanti o errati | Controlla i campi obbligatori (name, description) |
| `500 Error` | Errore server PHP | Controlla i log in phpMyAdmin → `storage/logs/` |
| GPT risponde "non posso chiamare l'API" | CORS o HTTPS mancante | Il sito deve essere HTTPS |
| fetchMedia fallisce | URL non raggiungibile o tipo non supportato | Usa JPG/PNG/WebP, URL pubblico, max 8 MB |
