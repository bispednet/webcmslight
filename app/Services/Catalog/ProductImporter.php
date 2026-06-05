<?php
declare(strict_types=1);

namespace App\Services\Catalog;

use App\Services\Catalog\Suppliers\SupplierAdapterInterface;
use PDO;

/**
 * Importa/aggiorna prodotti nel catalogo Bisped a partire da un adapter fornitore.
 *
 * Pricing (configurabile da dashboard):
 *   prezzo_vendita = ( costo_acquisto × (1 + markup%) + markup_fisso ) × (1 + IVA)
 *   arrotondato a ,90. markup% e markup_fisso hanno override per categoria.
 *   Il markup_fisso (default 5€) evita che convenga comprare minuteria da noi.
 *
 * Disponibilità:
 *   - prodotti con stock <= 0 NON vengono creati; se esistono già passano a "esaurito".
 *   - in modalità full, i prodotti Runner non più presenti nel listino vengono
 *     marcati "ritirato" (o rimossi, vedi pruneMissing()).
 *
 * Categorie:
 *   filtro per Famiglia merceologica Runner (whitelist), poi sotto-categoria
 *   per keyword sulla DescCatMerc/nome.
 */
final class ProductImporter
{
    /** Famiglie Runner da NON importare mai (fuori dal target Bisped). */
    private const FAMILY_EXCLUDE = [
        'ELETTRODOMESTICI ARTICOLI REGALO',
        'EDUCATIONAL',
        'DIGITAL SIGNAGE',
        'UFFICIO E CONSUMABILI',
        'SOFTWARE',
    ];

    /** Famiglia Runner => categoria Bisped di default (se nessuna keyword più specifica combacia). */
    private const FAMILY_MAP = [
        'SMARTPHONE E NAVIGATORI'      => ['category' => 'smartphone',    'icon' => 'smartphone'],
        'NOTEBOOK E TABLET'            => ['category' => 'notebook',      'icon' => 'laptop'],
        'MONITOR'                      => ['category' => 'componenti-pc', 'icon' => 'desktop'],
        'INFORMATICA E COMPONENTISTICA'=> ['category' => 'componenti-pc', 'icon' => 'desktop'],
        'SERVER E PERSONAL COMPUTER'   => ['category' => 'desktop',       'icon' => 'desktop'],
        'NETWORKING E SORVEGLIANZA'    => ['category' => 'connettivita',  'icon' => 'wifi'],
        'STAMPANTI FAX MULTIFUNZIONE'  => ['category' => 'accessori',     'icon' => 'desktop'],
        'HOME ENTERTAINMENT'           => ['category' => 'gaming',        'icon' => 'gaming'],
        'AUDIO E VIDEO'                => ['category' => 'accessori',     'icon' => 'desktop'],
        'VIDEOCONFERENZA'              => ['category' => 'accessori',     'icon' => 'desktop'],
        'FOTO E OTTICA'                => ['category' => 'accessori',     'icon' => 'desktop'],
        'CAVI'                         => ['category' => 'accessori',     'icon' => 'desktop'],
        'PREVENZIONE E SICUREZZA'      => ['category' => 'connettivita',  'icon' => 'wifi'],
        'RICONDIZIONATI'               => ['category' => 'accessori',     'icon' => 'desktop'],
    ];

    /**
     * Keyword su DescCatMerc/nome => categoria Bisped (sotto-categoria fine).
     * Vince sulla mappa famiglia. Le più specifiche vanno prima.
     */
    private array $categoryMap = [
        'scheda video' => ['category' => 'gaming',        'icon' => 'gaming'],
        'schede video' => ['category' => 'gaming',        'icon' => 'gaming'],
        'console'      => ['category' => 'gaming',        'icon' => 'gaming'],
        'gaming'       => ['category' => 'gaming',        'icon' => 'gaming'],
        'smartphone'   => ['category' => 'smartphone',    'icon' => 'smartphone'],
        'tablet'       => ['category' => 'tablet',        'icon' => 'smartphone'],
        'notebook'     => ['category' => 'notebook',      'icon' => 'laptop'],
        'monitor'      => ['category' => 'componenti-pc', 'icon' => 'desktop'],
        'processor'    => ['category' => 'componenti-pc', 'icon' => 'desktop'],
        'mainboard'    => ['category' => 'componenti-pc', 'icon' => 'desktop'],
        'ssd'          => ['category' => 'componenti-pc', 'icon' => 'desktop'],
        'hard disk'    => ['category' => 'componenti-pc', 'icon' => 'desktop'],
        'router'       => ['category' => 'connettivita',  'icon' => 'wifi'],
        'switch'       => ['category' => 'connettivita',  'icon' => 'wifi'],
        'nas'          => ['category' => 'connettivita',  'icon' => 'wifi'],
        'mouse'        => ['category' => 'accessori',     'icon' => 'desktop'],
        'tastier'      => ['category' => 'accessori',     'icon' => 'desktop'],
        'cuffi'        => ['category' => 'accessori',     'icon' => 'desktop'],
        'web cam'      => ['category' => 'accessori',     'icon' => 'desktop'],
        'webcam'       => ['category' => 'accessori',     'icon' => 'desktop'],
        'stampant'     => ['category' => 'accessori',     'icon' => 'desktop'],
    ];

    public function __construct(private PDO $db, private array $config)
    {
    }

    /** Sotto questa soglia di prodotti letti, il prune NON gira (feed sospetto). */
    private const PRUNE_SAFETY_THRESHOLD = 500;

    /**
     * @return array{created:int, updated:int, depleted:int, skipped:int, errors:int, pruned:int}
     */
    public function import(SupplierAdapterInterface $adapter, bool $dryRun = false, int $limit = 0): array
    {
        $products = $adapter->fetchProducts();
        $stats = ['created' => 0, 'updated' => 0, 'depleted' => 0, 'skipped' => 0, 'errors' => 0, 'pruned' => 0];
        $processed = 0;
        $seenSkus = [];

        foreach ($products as $raw) {
            if ($limit > 0 && $processed >= $limit) {
                break;
            }

            $family = mb_strtoupper((string)($raw['family'] ?? ''), 'UTF-8');
            if ($this->familyExcluded($family)) {
                $stats['skipped']++;
                continue;
            }

            $mapped = $this->mapCategory($family, (string)($raw['category'] ?? ''), (string)($raw['name'] ?? ''));
            if ($mapped === null) {
                $stats['skipped']++;
                continue;
            }

            $sku        = (string)$raw['sku'];
            $stock      = (int)($raw['stock'] ?? 0);
            $seenSkus[] = $sku;

            try {
                $existingId = $this->findBySku($sku);

                // Disponibilità: stock 0 = non vendibile.
                if ($stock <= 0) {
                    if ($existingId !== null && !$dryRun) {
                        $this->markDepleted($existingId);
                    }
                    $stats['depleted']++;
                    continue;
                }

                $price       = $this->computeSalePrice((float)$raw['cost'], $mapped['category']);
                $stockStatus = 'disponibile';

                if ($existingId !== null) {
                    if (!$dryRun) {
                        $this->updateProduct($existingId, $price, $stockStatus);
                    }
                    $stats['updated']++;
                } else {
                    if (!$dryRun) {
                        $this->createProduct($sku, $price, $mapped, $raw, $stockStatus);
                    }
                    $stats['created']++;
                }
            } catch (\Throwable $e) {
                $stats['errors']++;
            }
            $processed++;
        }

        // Prune: marca "ritirato" i prodotti non più presenti nel listino fornitore.
        // Solo in full (limit=0), non dry-run, e con abbastanza prodotti letti
        // (guardia anti-azzeramento se il feed arriva vuoto o corrotto).
        $allSkus = array_column($products, 'sku');
        if ($limit === 0 && !$dryRun && count($allSkus) >= self::PRUNE_SAFETY_THRESHOLD) {
            $stats['pruned'] = $this->pruneMissing($allSkus);
        }

        return $stats;
    }

    /**
     * Marca "ritirato" i prodotti del fornitore non più presenti nel listino.
     * Solo modalità full (limit=0) e non dry-run. Identifica i prodotti importati
     * (sku presente) confrontandoli con quelli visti in questo import.
     *
     * @param list<string> $seenSkus
     */
    public function pruneMissing(array $seenSkus, string $supplierTag = 'runner'): int
    {
        if (empty($seenSkus)) {
            return 0;
        }
        // Considera solo prodotti che hanno uno sku (creati da import fornitore).
        $placeholders = implode(',', array_fill(0, count($seenSkus), '?'));
        $sql = "UPDATE products SET stock_status='ritirato'
                WHERE sku IS NOT NULL AND sku <> ''
                  AND sku NOT IN ({$placeholders})
                  AND stock_status <> 'ritirato'
                  AND featured_order >= 100"; // 100 = soglia prodotti da import automatico
        $stmt = $this->db->prepare($sql);
        $stmt->execute($seenSkus);

        return $stmt->rowCount();
    }

    /**
     * Cron disponibilità (ogni 6h): aggiorna SOLO lo stock_status dei prodotti
     * già a catalogo, in base alla mappa [sku => qty] da dispo.txt.
     * - qty > 0  → disponibile
     * - qty <= 0 o sku non in mappa → esaurito
     * Tocca solo i prodotti da import automatico (featured_order >= 100).
     *
     * @param array<string,int> $availability
     * @return array{available:int, depleted:int}
     */
    public function syncAvailability(array $availability, bool $dryRun = false): array
    {
        $res = ['available' => 0, 'depleted' => 0];
        $stmt = $this->db->query("SELECT id, sku, stock_status FROM products WHERE sku IS NOT NULL AND sku <> '' AND featured_order >= 100");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $up = $this->db->prepare('UPDATE products SET stock_status = :s, updated_at = NOW() WHERE id = :id');
        foreach ($rows as $r) {
            $qty = $availability[$r['sku']] ?? 0;
            $new = $qty > 0 ? 'disponibile' : 'esaurito';
            if ($new === $r['stock_status']) {
                continue; // nessun cambiamento
            }
            if (!$dryRun) {
                $up->execute(['s' => $new, 'id' => $r['id']]);
            }
            $res[$qty > 0 ? 'available' : 'depleted']++;
        }

        return $res;
    }

    // ── Pricing ──────────────────────────────────────────────────────────────
    public function computeSalePrice(float $cost, string $category): float
    {
        $pct   = (float)($this->config['markup'][$category] ?? $this->config['markup_default'] ?? 0.10);
        $fixed = (float)($this->config['fixed'][$category] ?? $this->config['markup_fixed'] ?? 5.00);
        $vat   = (float)($this->config['vat'] ?? 0.22);

        // (costo × (1 + markup%) + fisso) × (1 + IVA)
        $price = ($cost * (1 + $pct) + $fixed) * (1 + $vat);

        return $this->roundPsychological($price);
    }

    private function roundPsychological(float $price): float
    {
        // Arrotonda a ",90" non sotto il prezzo calcolato.
        $candidate = floor($price) + 0.90;
        if ($candidate < $price) {
            $candidate += 1.0;
        }

        return round(max($candidate, 0.90), 2);
    }

    // ── Categorie ────────────────────────────────────────────────────────────
    private function familyExcluded(string $family): bool
    {
        $extra = array_map(
            static fn ($f) => mb_strtoupper((string)$f, 'UTF-8'),
            (array)($this->config['family_exclude'] ?? [])
        );
        $exclude = array_merge(self::FAMILY_EXCLUDE, $extra);

        return in_array($family, $exclude, true);
    }

    /**
     * @return array{category:string, icon:string}|null
     */
    private function mapCategory(string $family, string $descCatMerc, string $name): ?array
    {
        // 1) keyword fine su DescCatMerc + nome
        $haystack = mb_strtolower($descCatMerc . ' ' . $name, 'UTF-8');
        foreach ($this->categoryMap as $needle => $target) {
            if (str_contains($haystack, $needle)) {
                return $target;
            }
        }

        // 2) categoria di default della famiglia
        if (isset(self::FAMILY_MAP[$family])) {
            return self::FAMILY_MAP[$family];
        }

        // 3) famiglia sconosciuta: includi solo se import_unmapped
        if (!empty($this->config['import_unmapped'])) {
            return ['category' => 'accessori', 'icon' => 'desktop'];
        }

        return null;
    }

    // ── DB ───────────────────────────────────────────────────────────────────
    private function findBySku(string $sku): ?int
    {
        $stmt = $this->db->prepare('SELECT id FROM products WHERE sku = :sku LIMIT 1');
        $stmt->execute(['sku' => $sku]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int)$id;
    }

    private function updateProduct(int $id, float $price, string $stockStatus): void
    {
        $this->db->prepare(
            'UPDATE products SET price = :price, stock_status = :stock_status, updated_at = NOW() WHERE id = :id'
        )->execute(['price' => $price, 'stock_status' => $stockStatus, 'id' => $id]);
    }

    private function markDepleted(int $id): void
    {
        $this->db->prepare(
            "UPDATE products SET stock_status = 'esaurito', updated_at = NOW() WHERE id = :id"
        )->execute(['id' => $id]);
    }

    /**
     * @param array{category:string, icon:string} $mapped
     * @param array<string,mixed> $raw
     */
    private function createProduct(string $sku, float $price, array $mapped, array $raw, string $stockStatus): void
    {
        $name        = mb_substr((string)$raw['name'], 0, 150, 'UTF-8');
        $slug        = $this->uniqueSlug($name . '-' . $sku);
        $brand       = trim((string)($raw['brand'] ?? ''));
        $description = trim((string)($raw['description'] ?? '')) ?: $name;
        $imageUrl    = trim((string)($raw['image_url'] ?? ''));

        $this->db->prepare(
            'INSERT INTO products (name, slug, description, icon_key, external_link, category, tags, sku, price, stock_status, featured_order)
             VALUES (:name, :slug, :description, :icon, :image, :category, :tags, :sku, :price, :stock_status, 100)'
        )->execute([
            'name'         => $name,
            'slug'         => $slug,
            'description'  => mb_substr($description, 0, 2000, 'UTF-8'),
            'icon'         => $mapped['icon'],
            'image'        => $imageUrl ?: null,
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
