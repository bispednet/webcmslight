# Sincronizzazione e protezione del catalogo

Il catalogo Runner viene trattato come una fotografia composta da articoli,
prezzi personali e disponibilità. Un aggiornamento del solo stock non può
confermare prezzi o rimettere in vendita un articolo ritirato.

## Regole applicate

- L'endpoint Runner verificato è `runnertechstore.it:21`, FTP standard.
- I tre file commerciali obbligatori devono essere scaricati e validati insieme.
  Un errore di login o download non riutilizza una fotografia precedente.
- File mancanti, vuoti, duplicati, numeri ambigui o riduzioni anomale interrompono
  l'aggiornamento. Gli aggiornamenti commerciali sono in transazione.
- La provenienza è salvata in `supplier_key`, indipendentemente dall'ordine
  di vetrina. Gli articoli manuali e quelli di altri fornitori sono preservati.
- Un articolo assente viene ritirato e portato a quantità zero. Uno senza stock
  o senza costo valido non è vendibile. Una vecchia promozione viene cancellata
  quando si ricalcola il prezzo dal nuovo costo.
- Costo, soglia minima e date delle verifiche sono persistiti separatamente dalle
  modifiche editoriali. Le date del fornitore sono memorizzate in UTC.
- Il prezzo usa il costo netto: ricarico del 20% fino a 150 EUR, riduzione
  lineare al 5,5% tra 150 e 1.000 EUR, poi 5,5% costante. Si aggiungono
  5 EUR su tutti i prodotti e infine l'IVA; l'arrotondamento a ,90 e' sempre
  verso l'alto. Le vecchie maggiorazioni per categoria non si applicano.
- La protezione minima usa costo + 5,5% + 5 EUR, poi IVA, e impone almeno
  50 EUR di ricarico netto dalla fascia di costo 1.000 EUR. Questo e' un
  ricarico prima di commissioni, spedizioni e altri costi, non un utile netto.
  I parametri sono modificabili nella dashboard.
- I dati scadono dopo due ore per impostazione predefinita. Gli articoli importati
  non verificati o sotto soglia vengono esclusi dalle liste pubbliche, dalle
  proposte di componenti e dalle risposte dell'API agenti.
- Le vecchie schede possono restare raggiungibili per i link esistenti, ma senza
  prezzo verificato, disponibilità positiva o pulsante di acquisto.
- Il server controlla nuovamente disponibilità, prezzo e importo approvato prima
  di creare un pagamento e prima di incassarlo. Prima dell'incasso richiede una
  fotografia Runner corrente. Se il fornitore non è verificabile, sospende il
  pagamento. Un ordine già completato non viene incassato nuovamente.
- La conferma di stock resta una lettura del listino, non una prenotazione di
  merce nel gestionale del distributore.

## Operatività

Eseguire `scripts/migrate-catalog-safety.php` per aggiungere le colonne e il
registro `catalog_sync_runs`. Dopo la migrazione è necessaria una
sincronizzazione riuscita per convalidare gli articoli importati.

```text
php scripts/auto-update/import-products.php --supplier=runner --mode=commercial
php scripts/auto-update/import-products.php --supplier=runner --mode=full
php scripts/diagnose-catalog-safety.php
```

`commercial` aggiorna costo, prezzo, stock e ritiro degli articoli esistenti.
`full` aggiunge anche nuovi articoli e immagini. `availability` è mantenuto come
alias di `commercial`. `--dry-run` legge e verifica senza modificare il DB.
Un import con `--limit` non ritira gli articoli non processati.

Configurare `commercial` ogni ora e mantenere `full` giornaliero. Una frequenza
di sei ore è incompatibile con la scadenza predefinita di due ore. Sul PC il
gestore locale esegue `commercial` ogni ora mentre è acceso; un import completo
si avvia esplicitamente. La dashboard segnala errori, dati scaduti e ultimo
aggiornamento riuscito. Gli snapshot vecchi vengono rimossi dopo almeno 24 ore,
mantenendo almeno le ultime tre cartelle di download.

## Pubblicazione in produzione

Prima di pubblicare:

1. Salvare un backup consistente del DB e del `.env.php` fuori dalla document root.
2. Preparare la migrazione e verificare gli accessi Runner con host e protocollo
   corretti; non sovrascrivere le altre credenziali o i modelli di produzione.
3. Pubblicare solo i sorgenti controllati. Configurazioni locali, dump,
   certificati, runtime e cartelle di servizio devono restare esclusi.
4. Eseguire la migrazione, poi la sincronizzazione commerciale, controllando il
   registro e i conteggi. Durante il passaggio gli articoli non verificati sono
   bloccati dal nuovo codice.
5. Portare il cron commerciale a frequenza oraria, conservando gli altri cron.
6. Verificare home, catalogo, schede ritirate, dashboard e controlli pre-incasso.
   Verificare i pagamenti di test nell'ambiente sandbox.

Un rollback dei file deve essere valutato insieme alla sicurezza del catalogo:
il vecchio codice non applica le nuove protezioni. Ripristinare il DB da un backup
può cancellare ordini arrivati nel frattempo, quindi non è una procedura automatica.

## Verifiche ripetibili

```text
php scripts/test-catalog-safety.php
node scripts/dev/smoke-local.mjs
node scripts/dev/smoke-checkout.mjs
node scripts/dev/smoke-staging.mjs
node scripts/dev/check-secrets.mjs --working
```

Il test PHP usa un database temporaneo sul MySQL locale e non modifica dati
di produzione. I test HTTP dei pagamenti creano solo ordini sintetici locali e
verificano che i casi bloccati non arrivino alle API di incasso.
