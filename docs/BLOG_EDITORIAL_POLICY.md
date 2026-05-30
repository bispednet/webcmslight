# Blog editorial policy

Il blog bisp&d deve essere utile anche senza aprire la fonte originale. Il cron non pubblica comunicati riassunti in due righe e non trasforma una pagina commerciale invariata in una nuova notizia quotidiana.

## Regole di pubblicazione

- Ogni articolo deve avere una versione italiana e una inglese complete.
- Ogni versione deve spiegare il fatto, il contesto, l'impatto pratico, cosa verificare e il punto di vista bisp&d.
- Prezzi, date, copertura, disponibilita e specifiche non possono essere inventati.
- Le pagine tariffarie vengono pubblicate solo se il contenuto estratto contiene una novita verificabile e cambia rispetto all'ultima acquisizione.
- URL canonico e fingerprint impediscono duplicati della stessa fonte.
- Il cron prova ad acquisire il testo pubblico della fonte oltre all'estratto RSS, senza accedere a URL locali o privati.
- Le immagini remote vengono copiate in locale con nome normalizzato; in caso di errore si usa un'immagine locale coerente con la categoria.
- La fonte originale appare in coda all'articolo, in piccolo, e si apre in una nuova scheda.
- Il rendering decodifica le entita HTML prima dell'escape finale per evitare anteprime come `&amp;amp;` o `&#039;`.

## Controllo qualita

Il modello editoriale riceve prima un prompt strutturato in italiano. Solo se il testo supera il controllo qualita, una seconda richiesta traduce integralmente lo stesso articolo in inglese. Titolo e anteprima vengono derivati dalla rispettiva versione per evitare contenuti ibridi. Se il modello non e disponibile o restituisce contenuti sotto soglia, il cron salta la pubblicazione: il fallback prudente viene usato solo nelle simulazioni tecniche. Gli articoli legacy troppo brevi devono essere rimossi o riscritti prima della pubblicazione.

La redazione automatica usa direttamente Gemini API dal CMS PHP con modello `gemma-4-31b-it`: CopilotRM non e una dipendenza runtime del sito. La chiave resta in `.env.php`, escluso da Git. Il client applica un cooldown persistente di 7 secondi e limiti locali di 10 richieste al minuto, 5.000 token al minuto e 1.000 richieste al giorno. `gemma-4-31b-it` non supporta `thinkingBudget`: il cron di collaudo tenta fino a dieci fonti al giorno e scarta scalette o residui del ragionamento senza pubblicarli.

Prima del deploy su un database esistente eseguire:

```bash
runtime/bin/frankenphp php-cli scripts/migrate-blog-editorial.php
```
