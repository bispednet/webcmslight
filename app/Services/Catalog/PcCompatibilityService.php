<?php
declare(strict_types=1);

namespace App\Services\Catalog;

use PDO;

final class PcCompatibilityService
{
    public const SLOT_LABELS = [
        'cpu' => 'Processore',
        'motherboard' => 'Scheda madre',
        'ram' => 'Memoria RAM',
        'storage' => 'SSD / storage',
        'gpu' => 'Scheda video',
        'psu' => 'Alimentatore',
        'case' => 'Case',
        'cooler' => 'Raffreddamento CPU',
        'fan' => 'Ventole',
        'monitor' => 'Monitor',
        'keyboard' => 'Tastiera',
        'mouse' => 'Mouse',
        'headset' => 'Cuffie',
    ];

    private const SLOT_ORDER = [
        'cpu', 'motherboard', 'ram', 'storage', 'gpu', 'psu', 'case', 'cooler', 'fan', 'monitor', 'keyboard', 'mouse', 'headset',
    ];

    public function __construct(private PDO $db)
    {
    }

    /**
     * @return array<string,mixed>|null
     */
    public function configuratorForProduct(int $buildProductId): ?array
    {
        $build = $this->build($buildProductId);
        if ($build === null) {
            return null;
        }

        $items = $this->buildItems($buildProductId);
        $selected = [];
        $components = [];
        foreach ($items as $item) {
            $type = (string)$item['component_type'];
            $selected[$type] = (int)$item['component_product_id'];
            $components[$type] = $item;
        }

        return [
            'build' => $build,
            'slot_labels' => self::SLOT_LABELS,
            'selected' => $selected,
            'components' => $components,
            'options' => $this->optionsForSelection($selected),
            'total' => $this->selectionTotal($selected),
            'recommended_wattage' => $this->recommendedPsuWattage($this->selectedSpecs($selected)),
            'stock' => $this->selectionStock($selected),
        ];
    }

    /**
     * @param array<string,int> $selected
     * @return array<string,array<int,array<string,mixed>>>
     */
    public function optionsForSelection(array $selected): array
    {
        $options = [];
        foreach (self::SLOT_ORDER as $type) {
            $slotOptions = $this->optionsForSlot($type, $selected, 80);
            $selectedId = (int)($selected[$type] ?? 0);
            $selectedOption = $selectedId > 0 ? $this->selectedOptionForSlot($type, $selectedId, $selected) : null;
            if ($selectedOption !== null && !in_array($selectedId, array_column($slotOptions, 'id'), true)) {
                $slotOptions[] = $selectedOption;
            }
            $this->sortOptionsByPrice($slotOptions);
            $options[$type] = $slotOptions;
        }

        return $options;
    }

    /**
     * @param array<string,int> $selected
     * @return array<string,mixed>
     */
    public function selectionSummary(array $selected): array
    {
        return [
            'selected' => $selected,
            'options' => $this->optionsForSelection($selected),
            'total' => $this->selectionTotal($selected),
            'recommended_wattage' => $this->recommendedPsuWattage($this->selectedSpecs($selected)),
            'stock' => $this->selectionStock($selected),
            'valid' => $this->selectionIsValid($selected),
        ];
    }

    /**
     * @param array<string,int> $selected
     */
    public function selectionIsValid(array $selected): bool
    {
        $selectedSpecs = $this->selectedSpecs($selected);
        if (isset($selectedSpecs['cpu']) && empty($selectedSpecs['gpu']) && $this->cpuRequiresDiscreteGpu($selectedSpecs['cpu'])) {
            return false;
        }
        if (isset($selectedSpecs['motherboard'], $selectedSpecs['ram'])
            && !$this->sameKnown(
                (string)($selectedSpecs['motherboard']['memory_type'] ?? ''),
                (string)($selectedSpecs['ram']['memory_type'] ?? '')
            )) {
            return false;
        }

        foreach ($selected as $type => $id) {
            $spec = $this->specForProduct($id);
            if ($spec === null || (string)$spec['component_type'] !== $type) {
                return false;
            }
            $other = $selected;
            unset($other[$type]);
            if (!$this->candidateCompatible($spec, $type, $this->selectedSpecs($other))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string,int> $selected
     * @return array<int,array<string,mixed>>
     */
    public function optionsForSlot(string $type, array $selected, int $limit = 60): array
    {
        $baseRamCapacity = 0;
        if ($type === 'ram' && !empty($selected['ram'])) {
            $baseRamCapacity = (int)(($this->specForProduct((int)$selected['ram'])['capacity_gb'] ?? 0));
        }
        $otherSelected = $selected;
        unset($otherSelected[$type]);
        $selectedSpecs = $this->selectedSpecs($otherSelected);

        $stmt = $this->db->prepare(
            "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.stock_status, p.stock_qty, p.image_url,
                    s.component_type, s.platform_brand, s.socket, s.chipset, s.memory_type, s.form_factor,
                    s.wattage, s.capacity_gb, s.interface_type, s.metadata_json, s.confidence
             FROM pc_component_specs s
             INNER JOIN products p ON p.id = s.product_id
             WHERE s.component_type = :type
               AND COALESCE(p.stock_status, '') NOT IN ('esaurito','ritirato','outofstock','non disponibile')
             ORDER BY CASE WHEN COALESCE(p.sale_price, p.price, 0) > 0 THEN 0 ELSE 1 END,
                      COALESCE(p.sale_price, p.price, 999999) ASC,
                      p.name ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':type', $type);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            if ($type === 'ram' && $this->ramLooksUnsuitable((string)($row['name'] ?? ''))) {
                continue;
            }
            if ($type === 'ram' && $baseRamCapacity > 0 && (int)($row['capacity_gb'] ?? 0) < $baseRamCapacity) {
                continue;
            }
            if ($type === 'gpu' && $this->gpuLooksUnsuitable((string)($row['name'] ?? ''))) {
                continue;
            }
            if (!$this->candidateCompatible($row, $type, $selectedSpecs)) {
                continue;
            }
            $out[] = $this->formatOption($row);
        }

        return $out;
    }

    /** @param array<int,array<string,mixed>> $options */
    private function sortOptionsByPrice(array &$options): void
    {
        usort($options, static function (array $a, array $b): int {
            $priceA = (float)($a['price'] ?? 0);
            $priceB = (float)($b['price'] ?? 0);
            $knownA = $priceA > 0;
            $knownB = $priceB > 0;
            if ($knownA !== $knownB) {
                return $knownA ? -1 : 1;
            }
            if ($priceA !== $priceB) {
                return $priceA <=> $priceB;
            }

            return strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
        });
    }

    /**
     * A valid base component must remain selectable even when it falls below
     * the option list limit; otherwise the browser silently selects option one.
     *
     * @param array<string,int> $selected
     * @return array<string,mixed>|null
     */
    private function selectedOptionForSlot(string $type, int $productId, array $selected): ?array
    {
        $row = $this->specForProduct($productId);
        if ($row === null || (string)$row['component_type'] !== $type) {
            return null;
        }
        if ($type === 'ram' && $this->ramLooksUnsuitable((string)($row['name'] ?? ''))) {
            return null;
        }
        if ($type === 'gpu' && $this->gpuLooksUnsuitable((string)($row['name'] ?? ''))) {
            return null;
        }

        $otherSelected = $selected;
        unset($otherSelected[$type]);
        if (!$this->candidateCompatible($row, $type, $this->selectedSpecs($otherSelected))) {
            return null;
        }

        return $this->formatOption($row);
    }

    /**
     * @param array<string,int> $selected
     * @return array<string,array<string,mixed>>
     */
    private function selectedSpecs(array $selected): array
    {
        $specs = [];
        foreach ($selected as $type => $id) {
            $spec = $this->specForProduct((int)$id);
            if ($spec !== null) {
                $specs[$type] = $spec;
            }
        }

        return $specs;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function specForProduct(int $productId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.stock_status, p.stock_qty, p.image_url,
                    s.component_type, s.platform_brand, s.socket, s.chipset, s.memory_type, s.form_factor,
                    s.wattage, s.capacity_gb, s.interface_type, s.metadata_json, s.confidence
             FROM pc_component_specs s
             INNER JOIN products p ON p.id = s.product_id
             WHERE s.product_id = :id
             LIMIT 1"
        );
        $stmt->execute(['id' => $productId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @param array<string,mixed> $candidate
     * @param array<string,array<string,mixed>> $selected
     */
    private function candidateCompatible(array $candidate, string $type, array $selected): bool
    {
        if (isset($selected['cpu']) && in_array($type, ['motherboard', 'cooler'], true)) {
            if ($type === 'cooler' && trim((string)($candidate['socket'] ?? '')) === '') {
                return true;
            }
            if (!$this->sameKnown((string)($candidate['socket'] ?? ''), (string)($selected['cpu']['socket'] ?? ''))) {
                return false;
            }
        }
        if (isset($selected['motherboard']) && $type === 'cpu') {
            if (!$this->sameKnown((string)($candidate['socket'] ?? ''), (string)($selected['motherboard']['socket'] ?? ''))) {
                return false;
            }
        }

        if (isset($selected['motherboard']) && $type === 'ram') {
            if (!$this->sameKnown((string)($candidate['memory_type'] ?? ''), (string)($selected['motherboard']['memory_type'] ?? ''))) {
                return false;
            }
        }
        if (isset($selected['ram']) && $type === 'motherboard') {
            if (!$this->sameKnown((string)($candidate['memory_type'] ?? ''), (string)($selected['ram']['memory_type'] ?? ''))) {
                return false;
            }
        }

        if (isset($selected['motherboard']) && $type === 'case') {
            $boardForm = (string)($selected['motherboard']['form_factor'] ?? '');
            if ($boardForm !== '' && !$this->caseSupports($candidate, $boardForm)) {
                return false;
            }
        }
        if (isset($selected['case']) && $type === 'motherboard') {
            $boardForm = (string)($candidate['form_factor'] ?? '');
            if ($boardForm !== '' && !$this->caseSupports($selected['case'], $boardForm)) {
                return false;
            }
        }

        if ($type === 'psu') {
            $required = $this->recommendedPsuWattage($selected);
            $wattage = (int)($candidate['wattage'] ?? 0);
            if ($required > 0 && $wattage < $required) {
                return false;
            }
        }

        return true;
    }

    private function sameKnown(string $a, string $b): bool
    {
        $a = strtoupper(trim($a));
        $b = strtoupper(trim($b));

        return $a !== '' && $b !== '' && $a === $b;
    }

    private function ramLooksUnsuitable(string $name): bool
    {
        return (bool)preg_match('/\b(ddr2|ddr3l?|sodimm|so-dimm|registered|ecc)\b/u', mb_strtolower($name, 'UTF-8'));
    }

    private function gpuLooksUnsuitable(string $name): bool
    {
        return (bool)preg_match('/\b(gt\s*710|gt\s*730|gt\s*1030)\b/u', mb_strtolower($name, 'UTF-8'));
    }

    /**
     * @param array<string,mixed> $caseSpec
     */
    private function caseSupports(array $caseSpec, string $boardForm): bool
    {
        $metadata = json_decode((string)($caseSpec['metadata_json'] ?? ''), true);
        $supported = is_array($metadata) ? (array)($metadata['supports_form_factors'] ?? []) : [];
        if ($supported === []) {
            return false;
        }

        return in_array($boardForm, $supported, true);
    }

    /**
     * @param array<string,array<string,mixed>> $selectedSpecs
     */
    private function recommendedPsuWattage(array $selectedSpecs): int
    {
        $cpu = (int)($selectedSpecs['cpu']['wattage'] ?? 0);
        $gpu = (int)($selectedSpecs['gpu']['wattage'] ?? 0);
        if ($cpu === 0 && $gpu === 0) {
            return 0;
        }

        $raw = $cpu + $gpu + 150;
        return (int)(ceil($raw / 50) * 50);
    }

    /**
     * @param array<string,int> $selected
     */
    private function selectionTotal(array $selected): float
    {
        if ($selected === []) {
            return 0.0;
        }
        $ids = array_values(array_unique(array_map('intval', $selected)));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT id, COALESCE(sale_price, price, 0) price FROM products WHERE id IN ({$placeholders})");
        $stmt->execute($ids);
        $prices = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        $total = 0.0;
        foreach ($selected as $id) {
            $total += (float)($prices[$id] ?? 0);
        }

        return round($total, 2);
    }

    /**
     * @param array<string,int> $selected
     * @return array{available:bool,qty:int,label:string}
     */
    private function selectionStock(array $selected): array
    {
        if ($selected === []) {
            return ['available' => false, 'qty' => 0, 'label' => 'Su richiesta'];
        }

        $ids = array_values(array_unique(array_map('intval', $selected)));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT id, stock_status, stock_qty FROM products WHERE id IN ({$placeholders})");
        $stmt->execute($ids);

        $byId = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $byId[(int)$row['id']] = $row;
        }

        $minQty = null;
        foreach ($selected as $id) {
            $row = $byId[(int)$id] ?? null;
            if ($row === null || !$this->stockStatusIsAvailable((string)($row['stock_status'] ?? ''))) {
                return ['available' => false, 'qty' => 0, 'label' => 'Su richiesta'];
            }
            $qty = max(0, (int)($row['stock_qty'] ?? 0));
            $minQty = $minQty === null ? $qty : min($minQty, $qty);
        }

        $qty = max(0, (int)($minQty ?? 0));
        if ($qty <= 0) {
            return ['available' => false, 'qty' => 0, 'label' => 'Su richiesta'];
        }

        return ['available' => true, 'qty' => $qty, 'label' => 'Disponibile · ' . $qty . ' pz'];
    }

    private function stockStatusIsAvailable(string $status): bool
    {
        return in_array(strtolower(trim($status)), ['instock', 'in-stock', 'disponibile', '1', 'true'], true);
    }

    /**
     * @return array<string,mixed>|null
     */
    private function build(int $buildProductId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM pc_builds WHERE product_id = :id LIMIT 1');
        $stmt->execute(['id' => $buildProductId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buildItems(int $buildProductId): array
    {
        $stmt = $this->db->prepare(
            "SELECT bi.*, p.name, p.slug, p.price, p.sale_price, p.stock_status, p.stock_qty, p.image_url,
                    s.socket, s.memory_type, s.form_factor, s.wattage, s.capacity_gb, s.interface_type
             FROM pc_build_items bi
             INNER JOIN products p ON p.id = bi.component_product_id
             LEFT JOIN pc_component_specs s ON s.product_id = p.id
             WHERE bi.build_product_id = :id
             ORDER BY bi.sort_order ASC, bi.id ASC"
        );
        $stmt->execute(['id' => $buildProductId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function formatOption(array $row): array
    {
        $price = $row['sale_price'] ?? $row['price'] ?? null;
        $metadata = json_decode((string)($row['metadata_json'] ?? ''), true);
        $integratedGraphics = is_array($metadata) && array_key_exists('integrated_graphics', $metadata)
            ? (bool)$metadata['integrated_graphics']
            : null;

        return [
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
            'slug' => (string)$row['slug'],
            'price' => $price === null ? null : (float)$price,
            'price_label' => $price === null ? 'Prezzo su richiesta' : 'EUR ' . number_format((float)$price, 2, ',', '.'),
            'stock_status' => (string)($row['stock_status'] ?? ''),
            'stock_qty' => (int)($row['stock_qty'] ?? 0),
            'image_url' => (string)($row['image_url'] ?? ''),
            'search_text' => mb_strtolower(trim((string)$row['name'] . ' ' . (string)($row['socket'] ?? '') . ' ' . (string)($row['memory_type'] ?? '') . ' ' . (string)($row['form_factor'] ?? '') . ' ' . (string)($row['capacity_gb'] ?? '') . 'gb ' . (string)($row['wattage'] ?? '') . 'w'), 'UTF-8'),
            'specs' => [
                'socket' => $row['socket'] ?? null,
                'memory_type' => $row['memory_type'] ?? null,
                'form_factor' => $row['form_factor'] ?? null,
                'wattage' => $row['wattage'] ?? null,
                'capacity_gb' => $row['capacity_gb'] ?? null,
                'interface_type' => $row['interface_type'] ?? null,
                'integrated_graphics' => $integratedGraphics,
                'confidence' => (int)($row['confidence'] ?? 0),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $cpuSpec
     */
    private function cpuRequiresDiscreteGpu(array $cpuSpec): bool
    {
        $name = mb_strtolower((string)($cpuSpec['name'] ?? ''), 'UTF-8');
        if (preg_match('/\bryzen\b.*\b\d{4}\s*(g|gt|ge)\b/u', $name)) {
            return false;
        }
        if (preg_match('/\b(?:i[3579]|core\s+(?:ultra\s+)?[3579])[-\s]?\d{4,5}(?:kf|f)\b/u', $name)) {
            return true;
        }
        $metadata = json_decode((string)($cpuSpec['metadata_json'] ?? ''), true);
        if (!is_array($metadata) || !array_key_exists('integrated_graphics', $metadata)) {
            if (preg_match('/\bryzen\b.*\b(?:7500f|8400f)\b/u', $name)) {
                return true;
            }

            return false;
        }

        return $metadata['integrated_graphics'] === false;
    }
}
