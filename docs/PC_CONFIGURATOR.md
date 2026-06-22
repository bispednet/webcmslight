# PC configurabili Bisped

Obiettivo: pubblicare PC gaming/ufficio come prodotti normali del CMS, ma con componenti modificabili solo tra alternative compatibili.

## Architettura

Il catalogo componenti resta la sorgente primaria. Il configuratore aggiunge tre livelli:

- `pc_component_specs`: specifiche normalizzate estratte dai prodotti importati.
- `pc_builds`: un prodotto CMS che rappresenta un PC configurabile.
- `pc_build_items`: componenti scelti per quel PC.
- `pc_commercial_policies`: policy commerciale giornaliera generata da LLM o fallback conservativo.

La pagina prodotto legge `pc_builds` e mostra i selettori. Ogni cambio chiama:

```
GET /products/{slug}/configurator-options
```

L'endpoint ricalcola le alternative ammesse e il totale componenti.

## Vincoli bloccanti

Il sistema non propone una variante quando non riesce a verificarne la compatibilita:

- CPU e scheda madre devono avere lo stesso socket.
- RAM e scheda madre devono usare lo stesso tipo memoria (`DDR4`/`DDR5`).
- Case e scheda madre devono combaciare per form factor, quando noto.
- Alimentatore deve coprire consumo stimato CPU + GPU + margine.
- I prodotti esauriti, ritirati o non disponibili non vengono proposti.

Le specifiche vengono estratte in modo conservativo da nome prodotto, categoria fornitore e keyword: socket (`AM5`, `AM4`, `LGA1851`, `LGA1700`), chipset, DDR, wattaggio, capacita, formato scheda madre/case.

## Validazione commerciale autonoma

La compatibilita tecnica non basta: una build puo essere compatibile ma invendibile. Il job giornaliero quindi lavora in due fasi:

1. genera una policy commerciale aggiornata via LLM, usando data corrente e snapshot del listino disponibile;
2. costruisce candidati e pubblica solo quelli approvati dalla review commerciale.

La policy definisce per ogni fascia soglie come RAM minima, storage minimo, interfaccia storage richiesta, classe GPU minima, range PSU, qualita minima del case e keyword da evitare/preferire.

Se l'LLM non e disponibile, il sistema usa un fallback conservativo: meglio saltare una fascia che pubblicare PC con 4GB RAM, SATA/HDD come disco principale, alimentatori scarsi, case office su gaming o GPU fuori standard. Il fallback e solo una rete di sicurezza; il cron normale deve girare con Gemini configurato.

## Job operativi

Prima installazione:

```bash
php scripts/migrate-pc-configurator.php
```

Aggiornare solo le specifiche componenti:

```bash
php scripts/auto-update/sync-pc-component-specs.php
```

Generare/aggiornare le build PC:

```bash
php scripts/auto-update/generate-pc-builds.php
```

Test/emergenza senza LLM:

```bash
php scripts/auto-update/generate-pc-builds.php --no-llm
```

Pipeline completa PC:

```bash
php scripts/auto-update/generate-pc-catalog.php
```

Dry-run:

```bash
php scripts/auto-update/generate-pc-catalog.php --dry-run
```

## Cron consigliato

Eseguire dopo il full import prodotti e prezzi:

```bash
30 4 * * * /usr/local/php81/bin/php /home/uu4c5pdm/domains/bisped.net/public_html/scripts/auto-update/generate-pc-catalog.php >> /home/uu4c5pdm/domains/bisped.net/public_html/storage/logs/pc-configurator-cron.log 2>&1
```

Il cron va eseguito senza `--no-llm`: Gemini genera la policy commerciale e valida i candidati migliori prima della pubblicazione. I nomi prodotto restano deterministici (`PC GAMING GRAM9 5070TI 32`) per essere leggibili e stabili.

## Profili generati

- Office max 500 euro, AMD e Intel.
- Entry gaming max 1000 euro, AMD e Intel.
- Gaming 1500, 2000, 3000, 4000, 5000 euro.
- Gaming Mostruoso.

Nelle fasce alte il planner prova a includere anche raffreddamento e periferiche. Se il catalogo non contiene componenti sufficienti o sicuri per una fascia, quella build viene saltata invece di pubblicare una configurazione dubbia.
