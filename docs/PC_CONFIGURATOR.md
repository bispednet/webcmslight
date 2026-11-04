# Configuratore PC: dati e quantita

La capacita viene letta dal titolo del singolo SKU. Il testo promozionale della
famiglia non puo trasformare 8 GB in 64 GB o un SSD da 500 GB in uno da 4 TB.
In assenza di una capacita confermata, il componente non viene proposto.

Per la RAM sono distinti:

- capacita totale della confezione;
- numero di moduli nella confezione e capacita per modulo;
- quantita di confezioni selezionate;
- slot fisici e capacita massima della scheda madre, quando disponibili.

Un kit 2x16 GB occupa due slot e costa una volta il prezzo del kit. Due kit
occupano quattro slot e forniscono 64 GB. Il selettore mostra i banchi reali,
il totale in GB e il prezzo della RAM installata. Sono consentite 1, 2 o 4
confezioni, entro gli slot e lo stock disponibili. Con slot sconosciuti non
viene autorizzata l'espansione multipla. La frequenza effettiva dipende anche
dal controller della CPU, dalla scheda madre e dalla configurazione BIOS.

SO-DIMM, RAM registered/ECC e formati fisici non confermati sono esclusi dai
desktop. L'on-die ECC dei normali moduli DDR5 non equivale a RAM server ECC.
Le informazioni sulla grafica integrata vengono mostrate soltanto per le CPU.

## Dati verificati

`database/pc-verified-specs.php` contiene correzioni documentate da fonti del
produttore, vincolate a SKU e titolo. Serve per errori del feed come `2DD54`
nella MSI PRO H610M-E, senza dedurre il numero di slot dal formato ATX.

- [Kingston KF548S38IB-8: singolo modulo SO-DIMM da 8 GB](https://www.kingston.com/datasheets/KF548S38IB-8.pdf).
- [MSI PRO H610M-E: due slot DDR5](https://storage-asset.msi.com/datasheet/mb/global/PRO-H610M-E.pdf).
- [MSI PRO A620AM-B EVO](https://uk.msi.com/Motherboard/PRO-A620AM-B-EVO/Specification).

## Rigenerazione e aggiornamenti

```text
php scripts/auto-update/generate-pc-catalog.php --dry-run --no-llm
php scripts/auto-update/generate-pc-catalog.php --rebuild --no-llm
php scripts/test-pc-configurator.php
node scripts/dev/smoke-pc-configurator.mjs
```

`--no-llm` usa le regole tecniche e commerciali locali. Senza tale opzione il
profilo AI `pc_configurator` resta disponibile per la policy e la revisione.
`--rebuild` sostituisce soltanto le build automatiche `BISBUILD-*` legate a
`pc_builds`. Le configurazioni manuali sono preservate. Si preparano prima tutte
le combinazioni valide; la sostituzione avviene in transazione con lock. Un
errore o nessuna combinazione valida conserva le configurazioni precedenti.

Le configurazioni non fattibili con disponibilita e budget attuali non vengono
ricreate. Titoli, slug, descrizioni e prezzi usano la RAM realmente installata.
Le quantita sono persistite in `pc_build_items.qty`, gia presente nello schema.
Non e necessaria una nuova colonna DB per la scelta dei banchi.

Dopo ogni sincronizzazione commerciale riuscita si aggiornano anche totale e
stock dei PC esistenti. Le liste pubbliche escludono subito un PC se un
componente manca, e scaduto o non ha stock sufficiente, oppure se il totale
salvato non corrisponde ai componenti. Una distinta precedente alla versione
di validazione 2 resta esclusa fino alla rigenerazione.

Il test browser usa Chrome gia installato e Playwright nella cartella privata
`.local/browser-test`, senza usare il profilo personale del browser. Le catture
sono salvate in `.local` e restano escluse da Git.
