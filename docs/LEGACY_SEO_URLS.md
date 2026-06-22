# Compatibilita SEO vecchio shop

Il vecchio WordPress/WooCommerce usava URL sotto `/negozio/`, ad esempio:

- `/negozio/`
- `/negozio/samsung-galaxy/`
- `/negozio/oppo-a53s-4g-128gb-blue-6/`
- `/negozio/xiaomi-redmi-note-11-pro-5g128-bk/`
- `/negozio/msi-monitor-27-led-ips/`
- `/negozio/pc-gaming-shark-5/`

Il file `wp-sitemap.xml` non e piu disponibile online, ma Wayback CDX ha restituito almeno 2000 vecchie URL `/negozio/*` con status 200. La compatibilita e quindi implementata per pattern, non come elenco statico fragile.

Regole attuali:

- `/negozio` e `/negozio/` servono il catalogo moderno senza spostare `/products`.
- `/negozio/{slug}` cerca prima una scheda prodotto moderna equivalente e, se il match e abbastanza fedele, risponde con 301 verso `/products/{slug-moderno}`.
- Se non esiste un match prodotto fedele ma lo slug contiene marca/famiglia riconoscibile, viene servita una landing SEO dinamica con prodotti e articoli collegati.
- Se non viene riconosciuto nulla, la URL legacy risponde con 301 verso `/negozio`, evitando 404 sulle vecchie schede indicizzate.

Comando usato per recuperare campioni da Wayback:

```bash
curl -L 'https://web.archive.org/cdx?url=bisped.net/negozio/*&output=json&fl=original,statuscode,mimetype,timestamp&filter=statuscode:200&collapse=urlkey&limit=2000'
```
