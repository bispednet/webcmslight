# Bisped migration audit

Data: 2026-05-26

## Sorgenti analizzate

- `/home/funboy/old_bisped.net.db.sql` analizzato in sola lettura.
- `/home/funboy/uu4c5pdm_wpb.sql` analizzato e importato localmente in `bisped_wp_legacy`.
- FTP `public_html` analizzato in sola lettura, senza modifiche.
- `/home/funboy/copilotrm` ispezionato solo come progetto esterno da integrare in fasi successive.

## Dump SQL locale

- Prefisso tabelle: `_wp`.
- Tabelle rilevate: 19.
- WordPress options principali:
  - `siteurl`: `https://test.bisped.net`
  - `home`: `https://test.bisped.net`
  - `template` / `stylesheet`: `techfly`
  - permalink: `/%postname%/`
- Plugin attivi nel dump:
  - Advanced Custom Fields Pro
  - Akeeba Backup
  - Classic Editor
  - Classic Widgets
  - Contact Form 7
  - Duplicate Menu
  - my-custom-plugin
  - One Click Demo Import
  - Options Importer
- Post type rilevati:
  - `page`: 9
  - `post`: 6
  - `attachment`: 56
  - `nav_menu_item`: 27
  - `wpcf7_contact_form`: 2
  - `acf-field-group`: 13
  - `acf-field`: 228
- Nota: il dump sembra contenere soprattutto una installazione test/demo basata su Techfly, non il catalogo WooCommerce completo della produzione.

## Dump SQL corretto

- File: `/home/funboy/uu4c5pdm_wpb.sql`.
- Database locale: `bisped_wp_legacy`.
- Tabelle importate: 116.
- Prefisso tabelle: `wpb_`.
- WordPress options principali:
  - `siteurl`: `https://bisped.net`
  - `home`: `https://bisped.net`
  - `blogname`: `Bisp&d s.r.l. - Computer e Internet a Piombino`
  - `template` / `stylesheet`: `rehub-theme`
  - `woocommerce_version`: `10.7.0`
- Plugin attivi principali:
  - WooCommerce
  - WooCommerce PayPal Payments
  - WooCommerce Services
  - Flexible Shipping
  - Rank Math SEO
  - Elementor
  - Cookie Law Info
  - Easy WP SMTP
  - Wordfence
  - Jetpack
  - UpdraftPlus
  - Rehub Framework
- Contenuti rilevati:
  - Pagine pubblicate: 53
  - Post pubblicati: 166
  - Prodotti pubblicati: 66
  - Allegati: 224
  - Coupon pubblicati: 10
  - Ordini legacy rilevati in `shop_order`: 98 tra completati, falliti, rimborsati e cancellati.
- Categorie prodotto principali:
  - SMARTPHONE: 44
  - GamingPC: 15
  - CUFFIE E MICROFONI: 3
  - TASTIERE: 2
  - Monitor: 1
  - NOTEBOOK: 1
- Prima tranche CMS:
  - 36 prodotti importati in `database/seed-data.php`.
  - Tabelle CMS popolate nel database locale `bisped_net`.
  - Form contatti testato con scrittura in `contact_messages`.

## FTP produzione

Il controllo FTP read-only mostra una produzione WordPress diversa e piu ricca:

- Tema presente: `rehub-theme`.
- Plugin presenti:
  - WooCommerce
  - WooCommerce PayPal Payments
  - WooCommerce Services
  - Flexible Shipping
  - Rank Math SEO
  - Elementor
  - Cookie Law Info
  - Easy WP SMTP
  - Wordfence
  - UpdraftPlus
  - Jetpack
  - altri plugin di utilita e import/export
- Asset Bisped recuperati in locale:
  - `/public/media/bisped/bisped_logo.png`
  - `/public/media/bisped/fronte_negozio_bisped.png`
  - `/public/media/bisped/FS20BOY_720_logo.jpg`
  - `/public/media/bisped/gamingrig_header300positive_logo.png`
  - `/public/media/bisped/cropped-logobisped.png`
  - `/public/media/bisped/logo-1024x557.jpg`

## Implicazioni operative

- Il vecchio dump `old_bisped.net.db.sql` non va usato come sorgente ecommerce.
- Il dump `uu4c5pdm_wpb.sql` e la sorgente corretta per prodotti, pagine, ordini, coupon e configurazioni WooCommerce.
- Il CMS locale contiene ora una prima importazione reale del catalogo, da espandere con import completo prodotti/media/ordini.
- La produzione FTP non e stata modificata.
