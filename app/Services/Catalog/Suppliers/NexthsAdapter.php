<?php
declare(strict_types=1);

namespace App\Services\Catalog\Suppliers;

/**
 * Adapter per il distributore Nexths.
 *
 * Supporta due modalità, scelte in base alla config `catalog.nexths.mode`:
 *   - 'csv': legge un file di listino scaricato dal portale Nexths
 *   - 'api': interroga l'API Nexths (endpoint + credenziali da config)
 *
 * IMPORTANTE: il mapping delle colonne CSV e i campi API qui sotto sono
 * PRESUNTI. Vanno confermati con un file di esempio reale o la documentazione
 * Nexths e poi adattati nella sezione `$columnMap` / `parseApiRow()`.
 */
final class NexthsAdapter implements SupplierAdapterInterface
{
    /**
     * Mappa: chiave normalizzata Bisped => possibili intestazioni colonna nel CSV Nexths.
     * Al primo header che combacia (case-insensitive) viene usata quella colonna.
     * Aggiorna questi valori quando hai il tracciato reale Nexths.
     */
    private array $columnMap = [
        'sku'         => ['sku', 'codice', 'cod_articolo', 'codice articolo', 'item', 'partnumber', 'part number'],
        'name'        => ['descrizione', 'description', 'nome', 'titolo', 'denominazione'],
        'cost'        => ['prezzo', 'costo', 'price', 'net price', 'prezzo netto', 'prezzo acquisto', 'imponibile'],
        'category'    => ['categoria', 'category', 'famiglia', 'classe', 'gruppo'],
        'brand'       => ['marca', 'brand', 'produttore', 'vendor', 'manufacturer'],
        'stock'       => ['disponibilita', 'disponibilità', 'stock', 'qta', 'quantita', 'quantità', 'giacenza', 'qty'],
        'ean'         => ['ean', 'barcode', 'gtin', 'codice ean'],
        'image_url'   => ['immagine', 'image', 'url immagine', 'image_url', 'foto'],
        'description' => ['descrizione_estesa', 'long description', 'note', 'dettaglio'],
    ];

    public function __construct(private array $config)
    {
    }

    public function key(): string
    {
        return 'nexths';
    }

    public function fetchProducts(): array
    {
        $mode = (string)($this->config['mode'] ?? 'csv');

        return match ($mode) {
            'api'   => $this->fetchFromApi(),
            default => $this->fetchFromCsv((string)($this->config['csv_path'] ?? '')),
        };
    }

    // ── CSV ────────────────────────────────────────────────────────────────
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchFromCsv(string $path): array
    {
        if ($path === '' || !is_file($path)) {
            throw new \RuntimeException("Listino Nexths non trovato: {$path}. Scarica il CSV dal portale e imposta catalog.nexths.csv_path.");
        }

        $delimiter = (string)($this->config['csv_delimiter'] ?? ';'); // i listini IT italiani usano spesso ';'
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Impossibile aprire il listino Nexths: {$path}");
        }

        // Header
        $rawHeader = fgetcsv($handle, 0, $delimiter);
        if ($rawHeader === false) {
            fclose($handle);

            return [];
        }
        $header = array_map(static fn ($h) => mb_strtolower(trim((string)$h), 'UTF-8'), $rawHeader);
        $index  = $this->resolveColumnIndexes($header);

        $products = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $sku  = $this->cell($row, $index['sku'] ?? null);
            $name = $this->cell($row, $index['name'] ?? null);
            $cost = $this->parsePrice($this->cell($row, $index['cost'] ?? null));
            if ($sku === '' || $name === '' || $cost <= 0) {
                continue; // riga incompleta
            }
            $products[] = [
                'sku'         => $sku,
                'name'        => $name,
                'cost'        => $cost,
                'category'    => $this->cell($row, $index['category'] ?? null),
                'brand'       => $this->cell($row, $index['brand'] ?? null),
                'stock'       => (int)preg_replace('/\D+/', '', $this->cell($row, $index['stock'] ?? null) ?: '0'),
                'ean'         => $this->cell($row, $index['ean'] ?? null),
                'image_url'   => $this->cell($row, $index['image_url'] ?? null),
                'description' => $this->cell($row, $index['description'] ?? null),
            ];
        }
        fclose($handle);

        return $products;
    }

    // ── API ────────────────────────────────────────────────────────────────
    /**
     * Stub API Nexths. Da completare con endpoint e auth reali quando disponibili.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchFromApi(): array
    {
        $endpoint = (string)($this->config['api_url'] ?? '');
        $apiKey   = (string)($this->config['api_key'] ?? '');
        if ($endpoint === '' || $apiKey === '') {
            throw new \RuntimeException('API Nexths non configurata. Imposta catalog.nexths.api_url e catalog.nexths.api_key, oppure usa mode=csv.');
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
        ]);
        $raw  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if (!$raw || $code < 200 || $code >= 300) {
            throw new \RuntimeException("API Nexths HTTP {$code}");
        }
        $data = json_decode((string)$raw, true);
        if (!is_array($data)) {
            return [];
        }

        // Il path dei prodotti nella risposta va confermato (es. $data['products'])
        $rows = $data['products'] ?? $data['items'] ?? $data;
        $out  = [];
        foreach ((array)$rows as $row) {
            $p = $this->parseApiRow((array)$row);
            if ($p !== null) {
                $out[] = $p;
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    private function parseApiRow(array $row): ?array
    {
        // Mapping presunto dei campi API — da adattare alla risposta reale Nexths.
        $sku  = (string)($row['sku'] ?? $row['code'] ?? $row['itemCode'] ?? '');
        $name = (string)($row['description'] ?? $row['name'] ?? '');
        $cost = (float)($row['price'] ?? $row['netPrice'] ?? $row['cost'] ?? 0);
        if ($sku === '' || $name === '' || $cost <= 0) {
            return null;
        }

        return [
            'sku'         => $sku,
            'name'        => $name,
            'cost'        => $cost,
            'category'    => (string)($row['category'] ?? $row['family'] ?? ''),
            'brand'       => (string)($row['brand'] ?? $row['manufacturer'] ?? ''),
            'stock'       => (int)($row['stock'] ?? $row['availability'] ?? 0),
            'ean'         => (string)($row['ean'] ?? $row['gtin'] ?? ''),
            'image_url'   => (string)($row['image'] ?? $row['imageUrl'] ?? ''),
            'description' => (string)($row['longDescription'] ?? ''),
        ];
    }

    // ── Helpers ────────────────────────────────────────────────────────────
    /**
     * @param array<int,string> $header
     * @return array<string,int>
     */
    private function resolveColumnIndexes(array $header): array
    {
        $index = [];
        foreach ($this->columnMap as $field => $candidates) {
            foreach ($candidates as $cand) {
                $pos = array_search(mb_strtolower($cand, 'UTF-8'), $header, true);
                if ($pos !== false) {
                    $index[$field] = (int)$pos;
                    break;
                }
            }
        }

        return $index;
    }

    /**
     * @param array<int,string> $row
     */
    private function cell(array $row, ?int $idx): string
    {
        if ($idx === null || !isset($row[$idx])) {
            return '';
        }

        return trim((string)$row[$idx]);
    }

    private function parsePrice(string $value): float
    {
        if ($value === '') {
            return 0.0;
        }
        // Normalizza formati "1.234,56" e "1234.56"
        $value = preg_replace('/[^\d,.\-]/', '', $value) ?? $value;
        if (str_contains($value, ',') && str_contains($value, '.')) {
            // 1.234,56 -> 1234.56
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        }

        return (float)$value;
    }
}
