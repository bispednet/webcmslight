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

    /** Immagini più piccole di così sono placeholder "no foto" del fornitore. */
    private const MIN_IMAGE_BYTES = 2500;

    /** Dove salviamo le immagini prodotto scaricate dal fornitore. */
    private const IMAGE_DIR = '/media/products/runner';

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

            $descCat = (string)($raw['category'] ?? '');
            $mapped = $this->mapCategory($family, $descCat, (string)($raw['name'] ?? ''));
            if ($mapped === null) {
                $stats['skipped']++;
                continue;
            }
            // Sotto-categoria = categoria merceologica Runner (DescCatMerc)
            $mapped['subcategory']       = $this->slugify($descCat);
            $mapped['subcategory_label'] = $this->prettyLabel($descCat);

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

                // Foto: scarica e valida. Senza foto vera, il prodotto non entra
                // a catalogo (richiesta: "non voglio prodotti senza foto").
                $localImage = null;
                if (!empty($this->config['require_image']) || !$dryRun) {
                    $localImage = $this->localizeImage((string)($raw['image_url'] ?? ''), $sku, $dryRun);
                    if ($localImage === null && !empty($this->config['require_image'])) {
                        $stats['skipped']++;
                        continue;
                    }
                }

                $price = $this->computeSalePrice((float)$raw['cost'], $mapped['category']);

                if ($existingId !== null) {
                    if (!$dryRun) {
                        $this->updateProduct($existingId, $price, $stock, $localImage, (string)($raw['description'] ?? ''), $mapped);
                    }
                    $stats['updated']++;
                } else {
                    if (!$dryRun) {
                        $this->createProduct($sku, $price, $stock, $mapped, $raw, $localImage);
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

        $up = $this->db->prepare('UPDATE products SET stock_status = :s, stock_qty = :q, updated_at = NOW() WHERE id = :id');
        foreach ($rows as $r) {
            $qty = $availability[$r['sku']] ?? 0;
            $new = $qty > 0 ? 'disponibile' : 'esaurito';
            if (!$dryRun) {
                $up->execute(['s' => $new, 'q' => max(0, $qty), 'id' => $r['id']]);
            }
            if ($new !== $r['stock_status']) {
                $res[$qty > 0 ? 'available' : 'depleted']++;
            }
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

    /** Keyword che qualificano un prodotto come "gaming" (doppia visibilità). */
    private const GAMING_KEYWORDS = [
        'gaming', 'rog ', 'tuf gaming', 'predator', 'aorus', 'omen', 'nitro',
        'geforce rtx', 'radeon rx', 'gamer',
    ];

    /**
     * @return array{category:string, icon:string}|null
     */
    private function mapCategory(string $family, string $descCatMerc, string $name): ?array
    {
        $haystack = mb_strtolower($descCatMerc . ' ' . $name, 'UTF-8');

        // Determina la macro base
        $base = null;
        foreach ($this->categoryMap as $needle => $target) {
            if (str_contains($haystack, $needle)) {
                $base = $target;
                break;
            }
        }
        if ($base === null && isset(self::FAMILY_MAP[$family])) {
            $base = self::FAMILY_MAP[$family];
        }
        if ($base === null) {
            if (!empty($this->config['import_unmapped'])) {
                $base = ['category' => 'accessori', 'icon' => 'desktop'];
            } else {
                return null;
            }
        }

        // Refinement gaming: schede video/console sono già gaming dal categoryMap.
        // Periferiche/componenti con keyword gaming nel nome → macro gaming
        // (così un mouse gaming appare nel reparto Gaming oltre che in Mouse).
        if ($base['category'] !== 'gaming' && $this->looksGaming($haystack)) {
            $base = ['category' => 'gaming', 'icon' => 'gaming'];
        }

        return $base;
    }

    private function looksGaming(string $haystack): bool
    {
        foreach (self::GAMING_KEYWORDS as $kw) {
            if (str_contains($haystack, $kw)) {
                return true;
            }
        }

        return false;
    }

    private function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = preg_replace('/[^a-z0-9]+/u', '-', $text) ?? $text;

        return trim($text, '-') ?: 'altro';
    }

    private function prettyLabel(string $text): string
    {
        $text = trim(mb_convert_case(mb_strtolower($text, 'UTF-8'), MB_CASE_TITLE, 'UTF-8'));
        // Acronimi che vanno in maiuscolo
        foreach (['Cpu' => 'CPU', 'Ram' => 'RAM', 'Ssd' => 'SSD', 'Hdd' => 'HDD', 'Usb' => 'USB', 'Pc' => 'PC', 'Tv' => 'TV', 'Nas' => 'NAS', 'Poe' => 'PoE', 'Ups' => 'UPS'] as $from => $to) {
            $text = preg_replace('/\b' . $from . '\b/u', $to, $text) ?? $text;
        }

        return $text ?: 'Altro';
    }

    // ── DB ───────────────────────────────────────────────────────────────────
    private function findBySku(string $sku): ?int
    {
        $stmt = $this->db->prepare('SELECT id FROM products WHERE sku = :sku LIMIT 1');
        $stmt->execute(['sku' => $sku]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int)$id;
    }

    /**
     * @param array{category:string,icon:string,subcategory:string,subcategory_label:string} $mapped
     */
    private function updateProduct(int $id, float $price, int $stock, ?string $localImage, string $description, array $mapped): void
    {
        $sets = [
            'price = :price', 'stock_status = :ss', 'stock_qty = :qty',
            'category = :cat', 'subcategory = :sub', 'subcategory_label = :subl', 'updated_at = NOW()',
        ];
        $params = [
            'price' => $price, 'ss' => 'disponibile', 'qty' => $stock,
            'cat' => $mapped['category'], 'sub' => $mapped['subcategory'], 'subl' => $mapped['subcategory_label'],
            'id' => $id,
        ];
        if ($localImage !== null && $localImage !== '') {
            $sets[] = 'image_url = :img';
            $params['img'] = $localImage;
        }
        $cleanDesc = $this->cleanDescription($description);
        if ($cleanDesc !== '') {
            $sets[] = 'description = :descr';
            $params['descr'] = $cleanDesc;
        }
        $this->db->prepare('UPDATE products SET ' . implode(', ', $sets) . ' WHERE id = :id')->execute($params);
    }

    private function markDepleted(int $id): void
    {
        $this->db->prepare(
            "UPDATE products SET stock_status = 'esaurito', stock_qty = 0, updated_at = NOW() WHERE id = :id"
        )->execute(['id' => $id]);
    }

    /**
     * @param array{category:string, icon:string} $mapped
     * @param array<string,mixed> $raw
     */
    private function createProduct(string $sku, float $price, int $stock, array $mapped, array $raw, ?string $localImage): void
    {
        $name        = mb_substr((string)$raw['name'], 0, 150, 'UTF-8');
        $slug        = $this->uniqueSlug($name . '-' . $sku);
        $brand       = trim((string)($raw['brand'] ?? ''));
        $description = $this->cleanDescription((string)($raw['description'] ?? '')) ?: $name;

        $this->db->prepare(
            'INSERT INTO products (name, slug, description, icon_key, image_url, category, subcategory, subcategory_label, tags, sku, price, stock_status, stock_qty, featured_order)
             VALUES (:name, :slug, :description, :icon, :image, :category, :sub, :subl, :tags, :sku, :price, :stock_status, :qty, 100)'
        )->execute([
            'name'         => $name,
            'slug'         => $slug,
            'description'  => mb_substr($description, 0, 4000, 'UTF-8'),
            'icon'         => $mapped['icon'],
            'image'        => $localImage ?: null,
            'category'     => $mapped['category'],
            'sub'          => $mapped['subcategory'],
            'subl'         => $mapped['subcategory_label'],
            'tags'         => mb_substr(trim($brand . ',' . $mapped['subcategory_label']), 0, 255, 'UTF-8'),
            'sku'          => $sku,
            'price'        => $price,
            'stock_status' => 'disponibile',
            'qty'          => $stock,
        ]);
    }

    /**
     * Scarica l'immagine prodotto dal fornitore, la valida (no placeholder) e la
     * salva in public/media/products/runner/{sku}.jpg. Cache: se esiste già, la
     * riusa. Ritorna il path locale (/media/...) o null se non c'è foto valida.
     */
    private function localizeImage(string $url, string $sku, bool $dryRun): ?string
    {
        if ($url === '' || !preg_match('#^https?://#i', $url)) {
            return null;
        }
        $safeSku  = preg_replace('/[^A-Za-z0-9._-]+/', '_', $sku) ?: 'item';
        $relPath  = self::IMAGE_DIR . '/' . $safeSku . '.jpg';
        $absDir   = BASE_PATH . '/public' . self::IMAGE_DIR;
        $absPath  = BASE_PATH . '/public' . $relPath;

        // Cache: già scaricata e valida
        if (is_file($absPath) && filesize($absPath) >= self::MIN_IMAGE_BYTES) {
            return $relPath;
        }
        if ($dryRun) {
            return $relPath; // in dry-run non scarichiamo, assumiamo ok
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_USERAGENT      => 'Bisped-Catalog/1.0',
        ]);
        $data = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $type = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        // Scarta: errore HTTP, non immagine, o placeholder troppo piccolo
        if (!$data || $code < 200 || $code >= 300 || !str_starts_with($type, 'image/')) {
            return null;
        }
        if (strlen((string)$data) < self::MIN_IMAGE_BYTES) {
            return null; // placeholder "no foto"
        }

        if (!is_dir($absDir)) {
            @mkdir($absDir, 0755, true);
        }
        if (@file_put_contents($absPath, $data) === false) {
            return null;
        }

        return $relPath;
    }

    /**
     * Ripulisce la descrizione HTML del fornitore mantenendo formattazione base.
     */
    private function cleanDescription(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }
        // Tieni solo tag base sicuri
        $html = strip_tags($html, '<p><br><ul><li><strong><b><em><i>');
        $html = preg_replace('/\s+/', ' ', $html) ?? $html;

        return trim((string)$html);
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
