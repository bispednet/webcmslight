<?php
declare(strict_types=1);

namespace App\Services\Catalog;

use App\Services\Catalog\Suppliers\SupplierAdapterInterface;
use PDO;

/**
 * Importa/aggiorna prodotti nel catalogo Bisped a partire da un adapter fornitore.
 *
 * - Match per SKU: se esiste aggiorna prezzo/stock, altrimenti crea.
 * - Pricing: il listino fornitore è il COSTO B2B netto; il prezzo di vendita
 *   viene calcolato applicando un markup e l'IVA, con arrotondamento "psicologico".
 * - Filtro categorie: importa solo le categorie merceologiche vendute da Bisped.
 */
final class ProductImporter
{
    /**
     * Mappa categoria grezza fornitore => categoria Bisped + icona.
     * L'ordine conta: la prima keyword che combacia vince, quindi le più
     * specifiche (es. "scheda video"→gaming) vanno prima delle generiche.
     */
    private array $categoryMap = [
        // Mobile
        'smartphone'   => ['category' => 'smartphone',    'icon' => 'smartphone'],
        'telefon'      => ['category' => 'smartphone',    'icon' => 'smartphone'],
        'cellular'     => ['category' => 'smartphone',    'icon' => 'smartphone'],
        'tablet'       => ['category' => 'tablet',        'icon' => 'smartphone'],
        // Gaming (prima dei componenti generici)
        'scheda video' => ['category' => 'gaming',        'icon' => 'gaming'],
        'schede video' => ['category' => 'gaming',        'icon' => 'gaming'],
        'gaming'       => ['category' => 'gaming',        'icon' => 'gaming'],
        'console'      => ['category' => 'gaming',        'icon' => 'gaming'],
        // Computer
        'notebook'     => ['category' => 'notebook',      'icon' => 'laptop'],
        'laptop'       => ['category' => 'notebook',      'icon' => 'laptop'],
        'desktop'      => ['category' => 'desktop',       'icon' => 'desktop'],
        // Componenti PC
        'processor'    => ['category' => 'componenti-pc', 'icon' => 'desktop'],
        'scheda madre' => ['category' => 'componenti-pc', 'icon' => 'desktop'],
        'schede madri' => ['category' => 'componenti-pc', 'icon' => 'desktop'],
        'memoria'      => ['category' => 'componenti-pc', 'icon' => 'desktop'],
        'ram'          => ['category' => 'componenti-pc', 'icon' => 'desktop'],
        'ssd'          => ['category' => 'componenti-pc', 'icon' => 'desktop'],
        'hard disk'    => ['category' => 'componenti-pc', 'icon' => 'desktop'],
        'alimentator'  => ['category' => 'componenti-pc', 'icon' => 'desktop'],
        'case'         => ['category' => 'componenti-pc', 'icon' => 'desktop'],
        'ventol'       => ['category' => 'componenti-pc', 'icon' => 'desktop'],
        'monitor'      => ['category' => 'componenti-pc', 'icon' => 'desktop'],
        'scheda'       => ['category' => 'componenti-pc', 'icon' => 'desktop'],
        // Connettività
        'router'       => ['category' => 'connettivita',  'icon' => 'wifi'],
        'switch'       => ['category' => 'connettivita',  'icon' => 'wifi'],
        'access point' => ['category' => 'connettivita',  'icon' => 'wifi'],
        'modem'        => ['category' => 'connettivita',  'icon' => 'wifi'],
        'nas'          => ['category' => 'connettivita',  'icon' => 'wifi'],
        // Accessori e periferiche
        'mouse'        => ['category' => 'accessori',     'icon' => 'desktop'],
        'tastier'      => ['category' => 'accessori',     'icon' => 'desktop'],
        'cuffi'        => ['category' => 'accessori',     'icon' => 'desktop'],
        'auricolar'    => ['category' => 'accessori',     'icon' => 'desktop'],
        'webcam'       => ['category' => 'accessori',     'icon' => 'desktop'],
        'periferich'   => ['category' => 'accessori',     'icon' => 'desktop'],
        'stampant'     => ['category' => 'accessori',     'icon' => 'desktop'],
        'scanner'      => ['category' => 'accessori',     'icon' => 'desktop'],
        'ups'          => ['category' => 'accessori',     'icon' => 'desktop'],
        'borse'        => ['category' => 'accessori',     'icon' => 'desktop'],
        'accessor'     => ['category' => 'accessori',     'icon' => 'desktop'],
    ];

    public function __construct(private PDO $db, private array $config)
    {
    }

    /**
     * @return array{created:int, updated:int, skipped:int, errors:int}
     */
    public function import(SupplierAdapterInterface $adapter, bool $dryRun = false, int $limit = 0): array
    {
        $products = $adapter->fetchProducts();
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];
        $processed = 0;

        foreach ($products as $raw) {
            if ($limit > 0 && $processed >= $limit) {
                break;
            }

            $mapped = $this->mapCategory((string)($raw['category'] ?? ''), (string)($raw['name'] ?? ''));
            if ($mapped === null) {
                $stats['skipped']++; // categoria fuori dal catalogo Bisped
                continue;
            }

            $sku   = (string)$raw['sku'];
            $price = $this->computeSalePrice((float)$raw['cost'], $mapped['category']);
            $stock = (int)($raw['stock'] ?? 0);
            $stockStatus = $stock > 0 ? 'disponibile' : 'su ordinazione';
            $name  = mb_substr((string)$raw['name'], 0, 150, 'UTF-8');

            try {
                $existingId = $this->findBySku($sku);
                if ($existingId !== null) {
                    if (!$dryRun) {
                        $this->updateProduct($existingId, $price, $stockStatus, $stock);
                    }
                    $stats['updated']++;
                } else {
                    if (!$dryRun) {
                        $this->createProduct($sku, $name, $price, $mapped, $raw, $stockStatus);
                    }
                    $stats['created']++;
                }
            } catch (\Throwable $e) {
                $stats['errors']++;
            }
            $processed++;
        }

        return $stats;
    }

    private function computeSalePrice(float $cost, string $category): float
    {
        $markups = (array)($this->config['markup'] ?? []);
        $markup  = (float)($markups[$category] ?? $this->config['markup_default'] ?? 0.18); // +18% default
        $vat     = (float)($this->config['vat'] ?? 0.22); // IVA 22%

        $price = $cost * (1 + $markup) * (1 + $vat);

        // Arrotondamento psicologico a ,90
        $rounded = floor($price) + 0.90;
        if ($rounded < $price) {
            $rounded = ceil($price) + 0.90 - 1;
        }

        return round(max($rounded, 0.90), 2);
    }

    /**
     * @return array{category:string, icon:string}|null
     */
    private function mapCategory(string $rawCategory, string $name): ?array
    {
        $haystack = mb_strtolower($rawCategory . ' ' . $name, 'UTF-8');
        foreach ($this->categoryMap as $needle => $target) {
            if (str_contains($haystack, $needle)) {
                return $target;
            }
        }

        // Se è impostato import_all=true, le categorie non mappate finiscono in "accessori"
        if (!empty($this->config['import_unmapped'])) {
            return ['category' => 'accessori', 'icon' => 'desktop'];
        }

        return null;
    }

    private function findBySku(string $sku): ?int
    {
        $stmt = $this->db->prepare('SELECT id FROM products WHERE sku = :sku LIMIT 1');
        $stmt->execute(['sku' => $sku]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int)$id;
    }

    private function updateProduct(int $id, float $price, string $stockStatus, int $stock): void
    {
        $this->db->prepare(
            'UPDATE products SET price = :price, stock_status = :stock_status, updated_at = NOW() WHERE id = :id'
        )->execute(['price' => $price, 'stock_status' => $stockStatus, 'id' => $id]);
    }

    /**
     * @param array{category:string, icon:string} $mapped
     * @param array<string,mixed> $raw
     */
    private function createProduct(string $sku, string $name, float $price, array $mapped, array $raw, string $stockStatus): void
    {
        $slug = $this->uniqueSlug($name . '-' . $sku);
        $brand = trim((string)($raw['brand'] ?? ''));
        $description = trim((string)($raw['description'] ?? '')) ?: $name;

        $this->db->prepare(
            'INSERT INTO products (name, slug, description, icon_key, category, tags, sku, price, stock_status, featured_order)
             VALUES (:name, :slug, :description, :icon, :category, :tags, :sku, :price, :stock_status, 100)'
        )->execute([
            'name'         => $name,
            'slug'         => $slug,
            'description'  => mb_substr($description, 0, 2000, 'UTF-8'),
            'icon'         => $mapped['icon'],
            'category'     => $mapped['category'],
            'tags'         => mb_substr(trim($brand . ',' . $mapped['category']), 0, 255, 'UTF-8'),
            'sku'          => $sku,
            'price'        => $price,
            'stock_status' => $stockStatus,
        ]);
    }

    private function uniqueSlug(string $base): string
    {
        $slug = mb_strtolower($base, 'UTF-8');
        $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug) ?? $slug;
        $slug = trim($slug, '-');
        $slug = mb_substr($slug, 0, 140, 'UTF-8') ?: 'prodotto';
        $base = $slug;
        $n = 1;
        while (true) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM products WHERE slug = :s');
            $stmt->execute(['s' => $slug]);
            if ((int)$stmt->fetchColumn() === 0) {
                return $slug;
            }
            $slug = $base . '-' . (++$n);
        }
    }
}
