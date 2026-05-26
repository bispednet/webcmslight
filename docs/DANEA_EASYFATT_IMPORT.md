# Danea Easyfatt customer import

Obiettivo: predisporre Bisped.net a ricevere anagrafiche clienti da Danea Easyfatt senza mescolare dati amministrativi, ecommerce e richieste tecniche.

## Flusso consigliato

1. Esportare da Danea clienti e aziende in CSV/XML con codice cliente, ragione sociale, nome, cognome, email, telefono, indirizzo, partita IVA, codice fiscale e consenso comunicazioni.
2. Normalizzare email e codice cliente come chiavi di deduplica.
3. Importare in una tabella staging, validare righe incomplete e mostrare anteprima in admin.
4. Confermare l'import in area clienti, collegando eventuali richieste prodotto/assistenza gia presenti.
5. Conservare un log con file, data, righe importate, righe scartate e operatore.

## Ruoli

Admin: configura mapping, importa file, approva deduplica e vede tutti i clienti.

Commesso: consulta scheda cliente, apre richieste, collega preventivi e aggiorna recapiti operativi.

Cliente: accede alla propria area per richieste, preventivi, assistenze e documenti disponibili.

## Campi minimi

`external_customer_code`, `customer_type`, `business_name`, `first_name`, `last_name`, `email`, `phone`, `vat_number`, `tax_code`, `address`, `city`, `postal_code`, `province`, `country`, `marketing_consent`, `source`, `imported_at`.

## Stato attuale

La preview dispone gia di login separato admin/commesso/cliente e di area cliente. Il passo successivo e creare la tabella staging e il pannello di import quando verra definito il formato export effettivo usato da Bisped.
