<?php
declare(strict_types=1);

namespace App\Services\Catalog\Suppliers;

/**
 * Adapter che normalizza il listino di un fornitore B2B nel formato prodotto Bisped.
 *
 * Ogni prodotto restituito deve essere un array con queste chiavi (le opzionali
 * possono mancare):
 *   - sku            (string)  identificativo univoco fornitore — OBBLIGATORIO
 *   - name           (string)  nome prodotto — OBBLIGATORIO
 *   - cost           (float)   prezzo di acquisto B2B (netto) — OBBLIGATORIO
 *   - category       (string)  categoria grezza del fornitore
 *   - brand          (string)
 *   - description    (string)
 *   - stock          (int)     quantità disponibile
 *   - image_url      (string)  URL immagine remota
 *   - ean            (string)
 */
interface SupplierAdapterInterface
{
    /**
     * Identificatore del fornitore (es. "nexths").
     */
    public function key(): string;

    /**
     * Restituisce la lista normalizzata dei prodotti dal listino fornitore.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchProducts(): array;
}
