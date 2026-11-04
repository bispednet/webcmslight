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
 *   arrotondato verso l'alto a ,90. Ricarico graduato sul costo netto.
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
    /**
     * Mappa esplicita: categoria merceologica Runner (DescCatMerc, UPPERCASE)
     * => macro Bisped. Le DescCatMerc non presenti qui vengono SCARTATE
     * (catalogo pulito, niente roba fuori target).
     * La sotto-categoria mostrata è la DescCatMerc stessa (label leggibile).
     */
    private const SUBCAT_MAP = [
        // ── Smartphone ──
        'CELLULARI'                          => 'smartphone',
        'SMARTPHONE'                         => 'smartphone',
        'SMARTWATCH'                         => 'smartphone',
        'TELEFONIA FISSA E CORDLESS'         => 'smartphone',
        // ── Notebook ──
        'NOTEBOOK'                           => 'notebook',
        // ── PC assemblati ──
        'PC'                                 => 'pc-assemblati',
        'ALL IN ONE'                         => 'pc-assemblati',
        'MINI PC'                            => 'pc-assemblati',
        'MINI PC BAREBONE'                   => 'pc-assemblati',
        // ── Componenti ──
        'ALIMENTATORI'                       => 'componenti',
        'CASE'                               => 'componenti',
        'CASE ACCESSORI'                     => 'componenti',
        'CASE VENTOLE'                       => 'componenti',
        'CPU'                                => 'componenti',
        'CPU DISSIPATORI'                    => 'componenti',
        'MAINBOARD'                          => 'componenti',
        'MEMORIE RAM'                        => 'componenti',
        'HARD DISK INTERNI'                  => 'componenti',
        'HARD DISK HDD INTERNI ENTRY'        => 'componenti',
        'SSD INTERNI'                        => 'componenti',
        'MEMORIE FLASH'                      => 'componenti',
        'LETTORI E MASTERIZZATORI'           => 'componenti',
        // ── Monitor ──
        'MONITOR LED'                        => 'monitor',
        'MONITOR DESIGNER'                   => 'monitor',
        'MONITOR TOUCH'                      => 'monitor',
        'WALL MOUNT'                         => 'monitor',
        // ── Gaming ──
        'SCHEDE VIDEO'                       => 'gaming',
        'ACCESSORI GAMING'                   => 'gaming',
        'MONITOR GAMING'                     => 'gaming',
        // ── Connettività ──
        'ROUTER'                             => 'connettivita',
        'SWITCH E HUB'                       => 'connettivita',
        'ACCESS POINT'                       => 'connettivita',
        'FIREWALL'                           => 'connettivita',
        'ADATTATORI WIRELESS'                => 'connettivita',
        'SCHEDE DI RETE INTERNE'             => 'connettivita',
        'ACCESSORI NETWORKING'               => 'connettivita',
        'VOIP SOLUTION'                      => 'connettivita',
        'TELECAMERE IP'                      => 'connettivita',
        'ACCESSORI VIDEOSORVEGLIANZA'        => 'connettivita',
        // ── Server ──
        'SERVER'                             => 'server',
        'SERVER ACCESSORI'                   => 'server',
        'HARD DISK SERVER'                   => 'server',
        'RAM PER SERVER'                     => 'server',
        'ARMADI RACK ACCESSORI'              => 'server',
        'CONTROLLER'                         => 'server',
        'NAS'                                => 'server',
        // ── Stampa ──
        'STAMPANTI LASER'                    => 'stampa',
        'STAMPANTI INKJET'                   => 'stampa',
        'STAMPANTI MULTIFUNZIONE INKJET'     => 'stampa',
        'MULTIFUNZIONE LASER'                => 'stampa',
        'MULTIFUNZIONE LASER A3'             => 'stampa',
        'MULTIFUNZIONE INKJET A3'            => 'stampa',
        'STAMPANTI TERMICHE E CARD'          => 'stampa',
        'SCANNER DOCUMENTALI'                => 'stampa',
        'SCANNER PIANI'                      => 'stampa',
        'PLOTTER'                            => 'stampa',
        'STAMPANTI ACCESSORI'                => 'stampa',
        // ── Audio & TV ──
        'CUFFIE E MICROFONI'                 => 'audio-video',
        'SPEAKER'                            => 'audio-video',
        'ACCESSORI TV E MONITOR SOUNDBAR'    => 'audio-video',
        'ACCESSORI TV'                       => 'audio-video',
        'TELEVISORI LED'                     => 'audio-video',
        'VIDEOPROIETTORI'                    => 'audio-video',
        'ACCESSORI VIDEOPROIETTORI'          => 'audio-video',
        // ── Accessori (tutto il resto utile) ──
        'TABLET PC'                          => 'accessori',
        'TABLET ACCESSORI'                   => 'accessori',
        'MOUSE'                              => 'accessori',
        'TASTIERE'                           => 'accessori',
        'TASTIERE E MOUSE ACCESSORI'         => 'accessori',
        'WEB CAM'                            => 'accessori',
        'WEB CAM VIDEOCONFERENZA'            => 'accessori',
        'MICROFONI VIDEOCONFERENZA'          => 'accessori',
        'ACCESSORI VIDEOCONFERENZA'          => 'accessori',
        'HUB USB'                            => 'accessori',
        'CARD READER'                        => 'accessori',
        'ADATTATORI'                         => 'accessori',
        'MEMORIE USB'                        => 'accessori',
        'HARD DISK ESTERNI'                  => 'accessori',
        'SSD ESTERNI'                        => 'accessori',
        'HARD DISK ACCESSORI'                => 'accessori',
        'BORSE PER NOTEBOOK'                 => 'accessori',
        'DOCKING STATION'                    => 'accessori',
        'ALIMENTATORI NOTEBOOK'              => 'accessori',
        'ACCESSORI NET/NOTEBOOK'             => 'accessori',
        'ACCESSORI PC'                       => 'accessori',
        'GRUPPI DI CONTINUITA'               => 'accessori',
        'GRUPPI DI CONTINUITA ACCESSORI UPS' => 'accessori',
        'BATTERIE UPS'                       => 'accessori',
        'CUSTODIE E COVER'                   => 'accessori',
        'FILM PROTETTIVI SMARTPHONE'         => 'accessori',
        'SMARTPHONE ACCESSORI'               => 'accessori',
        'CAVI ANTENNA'                       => 'accessori',
        'CAVI AUDIO'                         => 'accessori',
        'CAVI DI ALIMENTAZIONE'              => 'accessori',
        'CAVI DI RETE'                       => 'accessori',
        'CAVI FIBRA'                         => 'accessori',
        'CAVI HDMI/VGA/DVI/DP'               => 'accessori',
        'CAVI USB'                           => 'accessori',
        'CAVI VARI'                          => 'accessori',
        'SPLITTER / EXTENDER'                => 'accessori',
        'TAVOLETTE GRAFICHE'                 => 'accessori',
    ];

    /** Icona (icon_key fallback) per macro. */
    private const MACRO_ICON = [
        'smartphone'    => 'smartphone',
        'notebook'      => 'laptop',
        'pc-assemblati' => 'desktop',
        'componenti'    => 'desktop',
        'monitor'       => 'desktop',
        'gaming'        => 'gaming',
        'connettivita'  => 'wifi',
        'server'        => 'desktop',
        'stampa'        => 'desktop',
        'audio-video'   => 'desktop',
        'accessori'     => 'desktop',
    ];

    public function __construct(private PDO $db, private array $config)
    {
    }

    private ?\PDOStatement $commercialUpdate = null;

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
        return $this->synchronize($adapter->fetchProducts(), $adapter->key(), $dryRun, $limit, true,
            $adapter instanceof \App\Services\Catalog\Suppliers\RunnerAdapter ? $adapter->capturedAt() : time());
    }

    public function syncCommercial(\App\Services\Catalog\Suppliers\RunnerAdapter $adapter, bool $dryRun = false): array
    {
        return $this->synchronize($adapter->fetchCommercialProducts(), $adapter->key(), $dryRun, 0, false, $adapter->capturedAt());
    }

    /** Aggiorna un articolo al checkout usando la stessa fotografia validata. */
    public function refreshOne(array $raw, string $supplier = 'runner', ?int $capturedAt = null): void
    {
        $row = $this->db->prepare('SELECT * FROM products WHERE sku=:sku LIMIT 1');
        $row->execute(['sku' => $raw['sku']]); $existing = $row->fetch(PDO::FETCH_ASSOC);
        if (!$existing || (!empty($existing['supplier_key']) && $existing['supplier_key'] !== $supplier)) throw new \RuntimeException('Articolo fornitore non riconosciuto');
        $this->saveCommercial((int)$existing['id'], $raw, $supplier, $capturedAt ?? time());
    }

    private function synchronize(array $products, string $supplier, bool $dryRun, int $limit, bool $details, int $capturedAt): array
    {
        if (!$this->db->query("SHOW COLUMNS FROM products LIKE 'supplier_key'")->fetch()) throw new \RuntimeException('Eseguire migrate-catalog-safety.php prima della sincronizzazione');
        if ($products === []) throw new \RuntimeException('Fotografia fornitore vuota: sincronizzazione annullata');
        $stats = ['created'=>0,'updated'=>0,'depleted'=>0,'skipped'=>0,'errors'=>0,'pruned'=>0,'image_errors'=>0,'invalid_prices'=>0,'input_count'=>count($products)];
        $seen = [];
        foreach ($products as $raw) {
            $sku = trim((string)($raw['sku'] ?? ''));
            if ($sku === '' || isset($seen[strtoupper($sku)])) throw new \RuntimeException('SKU mancante o duplicato nella fotografia fornitore');
            $seen[strtoupper($sku)] = true;
        }
        $count = $this->db->prepare("SELECT COUNT(*) FROM products WHERE supplier_key=:supplier AND supplier_status<>'retired'");
        $count->execute(['supplier'=>$supplier]); $existingCount = (int)$count->fetchColumn();
        if (count($products) < max(1, (int)($this->config['min_catalog_items'] ?? ($supplier === 'runner' ? self::PRUNE_SAFETY_THRESHOLD : 1)))
            || ($existingCount > 0 && count($products) < $existingCount * (float)($this->config['min_snapshot_ratio'] ?? 0.7))) {
            throw new \RuntimeException('Fotografia fornitore sospetta o incompleta: nessun prodotto modificato');
        }
        $lock = $this->db->prepare('SELECT GET_LOCK(:name,0)');
        $lockName = 'bisped-catalog-' . substr(hash('sha256', $this->db->query('SELECT DATABASE()')->fetchColumn() . ':' . $supplier), 0, 32);
        $lock->execute(['name'=>$lockName]);
        if ((int)$lock->fetchColumn() !== 1) throw new \RuntimeException('Sincronizzazione catalogo gia in corso');
        $records = $limit > 0 ? array_slice($products, 0, $limit) : $products;
        $pending = [];
        $existingBySku = [];
        foreach ($this->db->query("SELECT * FROM products WHERE sku IS NOT NULL AND sku<>''")->fetchAll(PDO::FETCH_ASSOC) as $existing) $existingBySku[strtoupper((string)$existing['sku'])] = $existing;
        try {
            if (!$dryRun) $this->db->beginTransaction();
            foreach ($records as $raw) {
                $existing = $existingBySku[strtoupper((string)$raw['sku'])] ?? null;
                if ($existing && !empty($existing['supplier_key']) && $existing['supplier_key'] !== $supplier) { $stats['skipped']++; continue; }
                if ((float)($raw['cost'] ?? 0) <= 0) $stats['invalid_prices']++;
                if ($existing) {
                    if (!$dryRun) $this->saveCommercial((int)$existing['id'], $raw, $supplier, $capturedAt);
                    $stats[(int)($raw['stock'] ?? 0) > 0 ? 'updated' : 'depleted']++;
                }
                if ($details) $pending[] = [$raw, $existing ?: null];
                elseif (!$existing) $stats['skipped']++;
            }
            if ($limit === 0 && !$dryRun) $stats['pruned'] = $this->pruneMissing(array_keys($seen), $supplier, $capturedAt);
            if (!$dryRun) $this->db->commit();
            // Foto e descrizioni non possono impedire un aggiornamento di costo/stock.
            foreach ($pending as [$raw, $existing]) {
                $family = mb_strtoupper((string)($raw['family'] ?? ''), 'UTF-8');
                $mapped = $this->familyExcluded($family) ? null : $this->mapCategory($family, (string)($raw['category'] ?? ''), (string)($raw['name'] ?? ''));
                if (!$mapped || (float)($raw['cost'] ?? 0) <= 0 || (int)($raw['stock'] ?? 0) <= 0) { $stats['skipped']++; continue; }
                $mapped['subcategory'] = $this->slugify((string)$raw['category']);
                $mapped['subcategory_label'] = $this->prettyLabel((string)$raw['category']);
                try {
                    $image = $this->localizeImage((string)($raw['image_url'] ?? ''), (string)$raw['sku'], $dryRun);
                    if ($existing) {
                        if (!$dryRun) {
                            $sets = ['category=:category','subcategory=:subcategory','subcategory_label=:label'];
                            $params = ['category'=>$mapped['category'],'subcategory'=>$mapped['subcategory'],'label'=>$mapped['subcategory_label'],'id'=>$existing['id']];
                            if ($image) { $sets[]='image_url=:image'; $params['image']=$image; }
                            $description = $this->cleanDescription((string)($raw['description'] ?? ''));
                            if ($description !== '') { $sets[]='description=:description'; $params['description']=$description; }
                            $this->db->prepare('UPDATE products SET '.implode(',',$sets).' WHERE id=:id')->execute($params);
                        }
                    } elseif ($image || empty($this->config['require_image'])) {
                        if (!$dryRun) {
                            $this->createProduct((string)$raw['sku'], $this->computeSalePrice((float)$raw['cost'], $mapped['category']), (int)$raw['stock'], $mapped, $raw, $image);
                            $this->saveCommercial((int)$this->db->lastInsertId(), $raw, $supplier, $capturedAt);
                        }
                        $stats['created']++;
                    } else $stats['skipped']++;
                } catch (\Throwable $error) {
                    $stats['image_errors']++;
                    \App\Core\Logger::error('Supplier image/details update failed', ['supplier'=>$supplier,'sku'=>$raw['sku'],'type'=>get_class($error)]);
                }
            }
            return $stats;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        } finally { $release=$this->db->prepare('SELECT RELEASE_LOCK(:name)'); $release->execute(['name'=>$lockName]); }
    }

    private function saveCommercial(int $id, array $raw, string $supplier, int $capturedAt): void
    {
        $family = mb_strtoupper((string)($raw['family'] ?? ''), 'UTF-8');
        $mapped = $this->familyExcluded($family) ? null : $this->mapCategory($family, (string)($raw['category'] ?? ''), (string)($raw['name'] ?? ''));
        $cost = (float)($raw['cost'] ?? 0); $quantity = max(0, (int)($raw['stock'] ?? 0));
        $valid = is_finite($cost) && $cost > 0;
        $reason = !$mapped ? 'excluded_category' : (!$valid ? 'invalid_supplier_price' : ($quantity <= 0 ? 'out_of_stock' : null));
        $price = $valid ? $this->computeSalePrice($cost, $mapped['category'] ?? '') : null;
        $minimum = $valid ? CatalogPricing::minimumPrice($cost, $this->config) : null;
        $status = $reason === null ? 'active' : ($reason === 'out_of_stock' ? 'out_of_stock' : 'blocked');
        $checked = gmdate('Y-m-d H:i:s', $capturedAt);
        $statement = $this->commercialUpdate ??= $this->db->prepare("UPDATE products SET supplier_key=:supplier,supplier_cost=:cost,supplier_status=:status,
            supplier_seen_at=:seen,supplier_price_checked_at=:price_checked,supplier_stock_checked_at=:stock_checked,
            min_sell_price=:minimum,catalog_block_reason=:reason,price=:price,sale_price=NULL,
            stock_status=:stock_status,stock_qty=:qty,updated_at=NOW() WHERE id=:id
            AND (supplier_stock_checked_at IS NULL OR supplier_stock_checked_at <= :comparison_time)");
        $statement->execute([
                'supplier'=>$supplier,'cost'=>$valid?$cost:null,'status'=>$status,'seen'=>$checked,
                'price_checked'=>$valid?$checked:null,'stock_checked'=>$checked,'minimum'=>$minimum,'reason'=>$reason,
                'price'=>$price,'stock_status'=>$reason===null?'disponibile':($reason==='out_of_stock'?'esaurito':'sospeso'),
                'qty'=>$reason===null?$quantity:0,'id'=>$id,'comparison_time'=>$checked,
            ]);
    }

    public function pruneMissing(array $seenSkus, string $supplierTag = 'runner', ?int $capturedAt = null): int
    {
        if ($seenSkus === []) throw new \RuntimeException('Riconciliazione rifiutata: elenco SKU vuoto');
        $seen = array_fill_keys(array_map('strtoupper', $seenSkus), true);
        $query = $this->db->prepare("SELECT id,sku FROM products WHERE supplier_key=:supplier AND (supplier_status<>'retired' OR stock_qty>0 OR stock_status<>'ritirato')");
        $query->execute(['supplier'=>$supplierTag]); $missing=[];
        foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) if (!isset($seen[strtoupper((string)$row['sku'])])) $missing[]=(int)$row['id'];
        $count=0;
        foreach (array_chunk($missing,500) as $ids) {
            $placeholders=implode(',',array_fill(0,count($ids),'?'));
            $statement=$this->db->prepare("UPDATE products SET stock_status='ritirato',stock_qty=0,supplier_status='retired',catalog_block_reason='missing_from_supplier',supplier_stock_checked_at=UTC_TIMESTAMP(),sale_price=NULL,updated_at=NOW() WHERE id IN ({$placeholders}) AND (supplier_stock_checked_at IS NULL OR supplier_stock_checked_at <= ?)");
            $statement->execute([...$ids, gmdate('Y-m-d H:i:s', $capturedAt ?? time())]); $count+=$statement->rowCount();
        }
        return $count;
    }

    /** Compatibilita: lo stock da solo puo esaurire, mai convalidare un prezzo. */
    public function syncAvailability(array $availability, bool $dryRun = false): array
    {
        if ($availability === []) throw new \RuntimeException('Disponibilita vuota: aggiornamento rifiutato');
        $stats=['available'=>0,'depleted'=>0];
        $query=$this->db->query("SELECT * FROM products WHERE supplier_key='runner'");
        foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $qty=max(0,(int)($availability[$row['sku']]??0));
            $canRemain=$qty>0 && CatalogSaleGuard::reason($row,$this->config)===null;
            if (!$dryRun) {
                if ($qty===0) $this->markDepleted((int)$row['id']);
                elseif ($canRemain) $this->db->prepare('UPDATE products SET stock_qty=:qty WHERE id=:id')->execute(['qty'=>$qty,'id'=>$row['id']]);
            }
            $stats[$canRemain?'available':'depleted']++;
        }
        return $stats;
    }

    public function computeSalePrice(float $cost, string $category): float
    {
        return CatalogPricing::salePrice($cost,$category,$this->config);
    }

    /** Keyword che qualificano un prodotto come "gaming" (doppia visibilità). */
    private const GAMING_KEYWORDS = [
        'gaming', 'rog ', 'tuf gaming', 'predator', 'aorus', 'omen', 'nitro',
        'geforce rtx', 'radeon rx', 'gamer',
    ];

    // ── Categorie ────────────────────────────────────────────────────────────
    private function familyExcluded(string $family): bool
    {
        // Whitelist gestita per DescCatMerc (SUBCAT_MAP). Solo override da config.
        $extra = array_map(
            static fn ($f) => mb_strtoupper((string)$f, 'UTF-8'),
            (array)($this->config['family_exclude'] ?? [])
        );

        return $extra !== [] && in_array($family, $extra, true);
    }

    /**
     * Mappa la categoria merceologica Runner (DescCatMerc) sulla macro Bisped.
     * Le DescCatMerc non in whitelist vengono scartate (return null).
     *
     * @return array{category:string, icon:string}|null
     */
    private function mapCategory(string $family, string $descCatMerc, string $name): ?array
    {
        $key = mb_strtoupper(trim($descCatMerc), 'UTF-8');
        if (!isset(self::SUBCAT_MAP[$key])) {
            return null; // fuori catalogo
        }
        $macro = self::SUBCAT_MAP[$key];

        // Refinement gaming: periferiche/componenti/monitor/audio con keyword
        // gaming nel nome vanno nel reparto Gaming (mantenendo la sotto-categoria).
        if ($macro !== 'gaming'
            && in_array($macro, ['accessori', 'componenti', 'monitor', 'audio-video'], true)
            && $this->looksGaming(mb_strtolower($name, 'UTF-8'))) {
            $macro = 'gaming';
        }

        return ['category' => $macro, 'icon' => self::MACRO_ICON[$macro] ?? 'desktop'];
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
