<?php
declare(strict_types=1);

namespace App\Services\Catalog;

use Closure;
use PDO;

final class PcBuildPlanner
{
    private const PROFILES = [
        ['key' => 'office-500', 'label' => 'Office 500', 'budget' => 500, 'use' => 'office entry', 'gpu' => false, 'platforms' => ['amd', 'intel']],
        ['key' => 'office-800', 'label' => 'Office 800', 'budget' => 800, 'use' => 'office', 'gpu' => false, 'platforms' => ['amd', 'intel']],
        ['key' => 'workstation-1200', 'label' => 'Workstation 1200', 'budget' => 1200, 'use' => 'workstation office', 'gpu' => false, 'platforms' => ['amd', 'intel']],
        ['key' => 'entry-gaming-1000', 'label' => 'Entry Gaming 1000', 'budget' => 1000, 'use' => 'gaming', 'gpu' => true, 'platforms' => ['amd', 'intel']],
        ['key' => 'gaming-1500', 'label' => 'Gaming 1500', 'budget' => 1500, 'use' => 'gaming', 'gpu' => true, 'platforms' => ['amd', 'intel']],
        ['key' => 'gaming-2000', 'label' => 'Gaming 2000', 'budget' => 2000, 'use' => 'gaming', 'gpu' => true, 'platforms' => ['amd', 'intel']],
        ['key' => 'gaming-3000', 'label' => 'Gaming 3000', 'budget' => 3000, 'use' => 'gaming', 'gpu' => true, 'platforms' => ['amd', 'intel']],
        ['key' => 'gaming-4000', 'label' => 'Gaming 4000', 'budget' => 4000, 'use' => 'gaming', 'gpu' => true, 'platforms' => ['amd', 'intel']],
        ['key' => 'gaming-5000', 'label' => 'Gaming 5000', 'budget' => 5000, 'use' => 'gaming', 'gpu' => true, 'platforms' => ['amd', 'intel']],
        ['key' => 'gaming-mostruoso', 'label' => 'Gaming Mostruoso', 'budget' => 8000, 'use' => 'gaming estremo', 'gpu' => true, 'platforms' => ['amd', 'intel']],
    ];

    public function __construct(
        private PDO $db,
        private PcCompatibilityService $compatibility,
        ?callable $nameGenerator = null,
        private ?PcCommercialPolicyService $commercialPolicyService = null,
    ) {
        $this->nameGenerator = $nameGenerator === null ? null : Closure::fromCallable($nameGenerator);
    }

    private ?Closure $nameGenerator;
    /** @var array<string,mixed> */
    private array $commercialPolicy = [];

    /**
     * @return array{created:int, updated:int, skipped:int}
     */
    public function generateDailyBuilds(bool $dryRun = false): array
    {
        $this->commercialPolicy = $this->commercialPolicyService?->currentPolicy() ?? [];
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        foreach (self::PROFILES as $profile) {
            foreach ($profile['platforms'] as $platform) {
                $build = $this->planBuild($profile, $platform);
                if ($build === null) {
                    $stats['skipped']++;
                    continue;
                }

                if ($dryRun) {
                    $stats['updated']++;
                    continue;
                }

                $result = $this->upsertBuildProduct($profile, $platform, $build);
                $stats[$result]++;
            }
        }
        if (!$dryRun) {
            $this->refreshStoredBuildProducts();
        }

        return $stats;
    }

    /**
     * @param array<string,mixed> $profile
     * @return array<string,mixed>|null
     */
    private function planBuild(array $profile, string $platform): ?array
    {
        $budget = (float)$profile['budget'];
        $requiresGpu = (bool)$profile['gpu'];
        $rules = $this->commercialRules($profile);
        $candidates = [];

        foreach ($this->cpuCandidates($platform, $budget) as $cpu) {
            if (!$requiresGpu && $this->cpuRequiresDiscreteGpu($cpu)) {
                continue;
            }
            $selected = ['cpu' => (int)$cpu['id']];

            $motherboard = $this->cheapestOption('motherboard', $selected, $budget * 0.26);
            if ($motherboard === null) {
                continue;
            }
            $selected['motherboard'] = (int)$motherboard['id'];

            $entryOffice = $this->isEntryOfficeProfile($profile);
            $ramAccept = static function (array $option) use ($rules): bool {
                $name = mb_strtolower((string)($option['name'] ?? ''), 'UTF-8');
                return (int)($option['specs']['capacity_gb'] ?? 0) >= $rules['min_ram_gb']
                    && !preg_match('/\b(ddr2|ddr3l?|sodimm|so-dimm|registered|ecc)\b/u', $name);
            };
            $ram = $entryOffice
                ? $this->cheapestAcceptedOption('ram', $selected, 130, $ramAccept)
                : $this->balancedOption('ram', $selected, $budget < 1200 ? 180 : ($budget < 2500 ? 360 : 900), $ramAccept);
            if ($ram === null) {
                continue;
            }
            $selected['ram'] = (int)$ram['id'];

            $storageAccept = function (array $option) use ($rules): bool {
                return $this->storageIsCommercial($option, $rules['min_storage_gb'], $rules['storage_interface']);
            };
            $storage = $entryOffice
                ? $this->cheapestAcceptedOption('storage', $selected, 130, $storageAccept)
                : $this->balancedOption('storage', $selected, $budget < 1200 ? 170 : ($budget < 2500 ? 280 : 700), $storageAccept);
            if ($storage === null) {
                continue;
            }
            $selected['storage'] = (int)$storage['id'];

            if ($requiresGpu || $this->cpuRequiresDiscreteGpu($cpu)) {
                $baseTotal = (float)$this->compatibility->selectionSummary($selected)['total'];
                $reserve = ($budget < 1200 ? 110 : ($budget < 2500 ? 270 : 420));
                $headroom = $budget < 1200 ? 1.08 : 1.05;
                $gpuMax = min($this->gpuCeiling($budget), max(0, ($budget * $headroom) - $baseTotal - $reserve));
                $gpu = $this->balancedOption('gpu', $selected, $gpuMax, function (array $option) use ($rules): bool {
                    return $this->gpuRank((string)$option['name']) >= $rules['min_gpu_rank'];
                });
                if ($gpu === null) {
                    continue;
                }
                $selected['gpu'] = (int)$gpu['id'];
            }

            $hasIntegratedPsuCase = false;
            if (!empty($rules['allow_integrated_psu'])) {
                $caseAccept = function (array $option) use ($rules): bool {
                    return $this->caseIsCommercial($option, $rules['min_case_price'], true, true) && $this->caseHasIntegratedPsu($option);
                };
                $case = $entryOffice
                    ? $this->cheapestAcceptedOption('case', $selected, 90, $caseAccept)
                    : $this->balancedOption('case', $selected, $budget < 1200 ? 120 : ($budget < 3000 ? 240 : 480), $caseAccept);
                if ($case !== null) {
                    $selected['case'] = (int)$case['id'];
                    $hasIntegratedPsuCase = true;
                }
            }

            if (!$hasIntegratedPsuCase) {
                $psuAccept = function (array $option) use ($rules): bool {
                    return $this->psuIsCommercial($option, $rules['min_psu_watt'], $rules['max_psu_watt'], !empty($rules['allow_basic_psu']));
                };
                $psu = $entryOffice
                    ? $this->cheapestAcceptedOption('psu', $selected, 80, $psuAccept)
                    : $this->balancedOption('psu', $selected, $budget < 1200 ? 140 : ($budget < 2500 ? 220 : 420), $psuAccept);
                if ($psu === null) {
                    continue;
                }
                $selected['psu'] = (int)$psu['id'];

                $caseAccept = function (array $option) use ($rules): bool {
                    return $this->caseIsCommercial($option, $rules['min_case_price'], !empty($rules['allow_integrated_psu']), !empty($rules['office_case_ok']));
                };
                $case = $entryOffice
                    ? $this->cheapestAcceptedOption('case', $selected, 90, $caseAccept)
                    : $this->balancedOption('case', $selected, $budget < 1200 ? 130 : ($budget < 3000 ? 240 : 480), $caseAccept);
                if ($case !== null) {
                    $selected['case'] = (int)$case['id'];
                }
            }

            if ($budget >= 1500) {
                $selected = $this->addOptionalIfAffordable($selected, 'cooler', $budget < 3000 ? 120 : 280, $budget * 1.08);
            }
            if ($budget >= 3000) {
                foreach (['monitor', 'keyboard', 'mouse', 'headset'] as $slot) {
                    $selected = $this->addOptionalIfAffordable($selected, $slot, $slot === 'monitor' ? 700 : 220, $budget * 1.08);
                }
            }

            if (!$this->compatibility->selectionIsValid($selected)) {
                continue;
            }

            $summary = $this->compatibility->selectionSummary($selected);
            $total = (float)$summary['total'];
            if ($total <= 0 || ($budget < 8000 && $total > $budget * 1.08)) {
                continue;
            }
            if (!$this->commercialBuildIsValid($selected, $profile)) {
                continue;
            }

            $score = $this->commercialScore($selected, $profile, $total);
            $candidates[] = [
                'selected' => $selected,
                'total' => $total,
                'recommended_wattage' => $summary['recommended_wattage'],
                'score' => $score,
            ];
        }

        usort($candidates, static fn (array $a, array $b): int => ($b['score'] <=> $a['score']));
        foreach (array_slice($candidates, 0, 2) as $candidate) {
            unset($candidate['score']);
            $review = $this->commercialReview($profile, $candidate);
            if ($review['approved']) {
                $candidate['commercial_review'] = $review;
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function cpuCandidates(string $platform, float $budget): array
    {
        $limit = $budget < 700 ? 220 : ($budget < 1000 ? 280 : ($budget < 2000 ? 420 : ($budget < 4000 ? 680 : 1100)));
        $direction = $budget < 1200 ? 'ASC' : 'DESC';
        $candidateLimit = $budget < 1200 ? 24 : 18;
        $stmt = $this->db->prepare(
            "SELECT p.id, p.name, COALESCE(p.sale_price, p.price, 0) price, s.socket, s.wattage, s.metadata_json
             FROM pc_component_specs s
             INNER JOIN products p ON p.id = s.product_id
             WHERE s.component_type = 'cpu'
               AND s.platform_brand = :platform
               AND s.socket IS NOT NULL
               AND COALESCE(p.sale_price, p.price, 0) > 0
               AND COALESCE(p.sale_price, p.price, 0) <= :max_price
               AND COALESCE(p.stock_status, '') NOT IN ('esaurito','ritirato','outofstock','non disponibile')
             ORDER BY COALESCE(p.sale_price, p.price, 0) {$direction}
             LIMIT {$candidateLimit}"
        );
        $stmt->execute(['platform' => $platform, 'max_price' => $limit]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function gpuCeiling(float $budget): float
    {
        return $budget < 1200 ? 390 : ($budget < 2000 ? 760 : ($budget < 3000 ? 1200 : ($budget < 5000 ? 2100 : 5200)));
    }

    /**
     * @param array<string,mixed> $profile
     */
    private function isEntryOfficeProfile(array $profile): bool
    {
        return (float)($profile['budget'] ?? 0) <= 600
            && str_contains((string)($profile['use'] ?? ''), 'office')
            && !str_contains((string)($profile['use'] ?? ''), 'workstation');
    }

    /**
     * @return array<string,mixed>|null
     */
    private function cheapestOption(string $slot, array $selected, float $maxPrice): ?array
    {
        $best = null;
        foreach ($this->compatibility->optionsForSlot($slot, $selected, 100) as $option) {
            $price = (float)($option['price'] ?? 0);
            if ($price <= 0 || $price > $maxPrice) {
                continue;
            }
            if ($best === null || $price < (float)$best['price']) {
                $best = $option;
            }
        }

        return $best;
    }

    /**
     * @param callable(array<string,mixed>):bool $accept
     * @return array<string,mixed>|null
     */
    private function cheapestAcceptedOption(string $slot, array $selected, float $maxPrice, callable $accept): ?array
    {
        $best = null;
        foreach ($this->compatibility->optionsForSlot($slot, $selected, 180) as $option) {
            $price = (float)($option['price'] ?? 0);
            if ($price <= 0 || $price > $maxPrice || !$accept($option)) {
                continue;
            }
            if ($best === null || $price < (float)$best['price']) {
                $best = $option;
            }
        }

        return $best;
    }

    /**
     * @param callable(array<string,mixed>):bool $accept
     * @return array<string,mixed>|null
     */
    private function balancedOption(string $slot, array $selected, float $maxPrice, callable $accept): ?array
    {
        $best = null;
        foreach ($this->compatibility->optionsForSlot($slot, $selected, 180) as $option) {
            $price = (float)($option['price'] ?? 0);
            if ($price <= 0 || $price > $maxPrice || !$accept($option)) {
                continue;
            }
            if ($best === null || $this->optionQuality($slot, $option) > $this->optionQuality($slot, $best)) {
                $best = $option;
            }
        }

        return $best;
    }

    /**
     * @param array<string,mixed> $option
     */
    private function optionQuality(string $slot, array $option): float
    {
        $price = (float)($option['price'] ?? 0);
        $specs = $option['specs'] ?? [];
        if ($slot === 'ram') {
            return ((int)($specs['capacity_gb'] ?? 0) * 1000) - $price;
        }
        if ($slot === 'storage') {
            $nvmeBonus = (string)($specs['interface_type'] ?? '') === 'NVMe M.2' ? 100000 : 0;
            return $nvmeBonus + ((int)($specs['capacity_gb'] ?? 0) * 10) - $price;
        }
        if ($slot === 'gpu') {
            return ($this->gpuRank((string)$option['name']) * 100) - $price;
        }
        if ($slot === 'psu') {
            return ($this->psuQualityBonus((string)$option['name']) * 120) + ((int)($specs['wattage'] ?? 0) * 0.08) - $price;
        }
        if ($slot === 'case') {
            return $this->caseQualityBonus((string)$option['name']) * 100 - $price;
        }

        return -$price;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function bestAffordableOption(string $slot, array $selected, float $maxPrice): ?array
    {
        $best = null;
        foreach ($this->compatibility->optionsForSlot($slot, $selected, 160) as $option) {
            $price = (float)($option['price'] ?? 0);
            if ($price <= 0 || $price > $maxPrice) {
                continue;
            }
            if ($best === null || $price > (float)$best['price']) {
                $best = $option;
            }
        }

        return $best;
    }

    /**
     * @param array<string,int> $selected
     * @return array<string,int>
     */
    private function addOptionalIfAffordable(array $selected, string $slot, float $maxPrice, float $maxTotal): array
    {
        $option = $this->bestAffordableOption($slot, $selected, $maxPrice);
        if ($option === null) {
            return $selected;
        }

        $candidate = $selected;
        $candidate[$slot] = (int)$option['id'];
        $summary = $this->compatibility->selectionSummary($candidate);
        if (!empty($summary['valid']) && (float)$summary['total'] <= $maxTotal) {
            return $candidate;
        }

        return $selected;
    }

    /**
     * @param array<string,mixed> $profile
     * @param array<string,mixed> $build
     * @return 'created'|'updated'
     */
    private function upsertBuildProduct(array $profile, string $platform, array $build): string
    {
        $sku = 'BISBUILD-' . strtoupper((string)$profile['key']) . '-' . strtoupper($platform);
        $existing = $this->productBySku($sku);
        $title = $this->buildName($profile, $platform, $build);
        $slug = $existing['slug'] ?? $this->uniqueSlug($title);
        $description = $this->description($title, $profile, $build);
        $content = $this->contentHtml($profile, $build);
        $image = $this->componentImage($build, 'case') ?: '/media/banners/pc-gaming-1.png';
        $price = number_format((float)$build['total'], 2, '.', '');
        $category = 'pc-custom';
        $typeLabel = isset($build['selected']['gpu']) || str_contains((string)$profile['use'], 'gaming') ? 'gaming' : 'workstation';
        $stock = $this->buildStock($build['selected']);

        if ($existing === null) {
            $stmt = $this->db->prepare(
                'INSERT INTO products
                    (name, slug, description, icon_key, image_url, category, subcategory, subcategory_label, tags, sku, price, stock_status, stock_qty, content_html, featured_order)
                 VALUES
                    (:name, :slug, :description, :icon, :image, :category, :subcategory, :subcategory_label, :tags, :sku, :price, :stock_status, :stock_qty, :content_html, 20)'
            );
            $stmt->execute([
                'name' => $title,
                'slug' => $slug,
                'description' => $description,
                'icon' => 'gaming',
                'image' => $image,
                'category' => $category,
                'subcategory' => 'pc-configurabili',
                'subcategory_label' => 'PC configurabili',
                'tags' => 'pc ' . $typeLabel . ',pc assemblato,configurabile,' . $platform,
                'sku' => $sku,
                'price' => $price,
                'stock_status' => $stock['status'],
                'stock_qty' => $stock['qty'],
                'content_html' => $content,
            ]);
            $productId = (int)$this->db->lastInsertId();
            $result = 'created';
        } else {
            $productId = (int)$existing['id'];
            $stmt = $this->db->prepare(
                "UPDATE products
                 SET name = :name, description = :description, price = :price, stock_status = :stock_status, stock_qty = :stock_qty,
                     sale_price = NULL,
                     image_url = :image,
                     category = :category, subcategory = 'pc-configurabili', subcategory_label = 'PC configurabili',
                     tags = :tags, content_html = :content_html, updated_at = NOW()
                 WHERE id = :id"
            );
            $stmt->execute([
                'name' => $title,
                'description' => $description,
                'price' => $price,
                'image' => $image,
                'category' => $category,
                'tags' => 'pc ' . $typeLabel . ',pc assemblato,configurabile,' . $platform,
                'stock_status' => $stock['status'],
                'stock_qty' => $stock['qty'],
                'content_html' => $content,
                'id' => $productId,
            ]);
            $result = 'updated';
        }

        $this->upsertBuildMetadata($productId, $profile, $platform, $build);
        $this->syncBuildItems($productId, $build['selected']);
        $this->syncFeatures($productId, $profile, $build);

        return $result;
    }

    private function refreshStoredBuildProducts(): void
    {
        $stmt = $this->db->query(
            'SELECT product_id, profile_key, budget_ceiling, target_use, base_platform
             FROM pc_builds'
        );
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $selected = $this->selectedBuildItems((int)$row['product_id']);
            if ($selected === []) {
                continue;
            }
            $profile = [
                'key' => (string)$row['profile_key'],
                'budget' => (float)$row['budget_ceiling'],
                'use' => (string)$row['target_use'],
            ];
            if (!$this->commercialBuildIsValid($selected, $profile)) {
                $this->deleteGeneratedBuildProduct((int)$row['product_id']);
                continue;
            }
            $summary = $this->compatibility->selectionSummary($selected);
            $build = [
                'selected' => $selected,
                'total' => (float)$summary['total'],
                'recommended_wattage' => (int)$summary['recommended_wattage'],
            ];

            $this->upsertBuildProduct($profile, (string)$row['base_platform'], $build);
        }
    }

    /**
     * @return array<string,int>
     */
    private function selectedBuildItems(int $productId): array
    {
        $stmt = $this->db->prepare(
            'SELECT component_type, component_product_id
             FROM pc_build_items
             WHERE build_product_id = :id
             ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute(['id' => $productId]);

        $selected = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $selected[(string)$row['component_type']] = (int)$row['component_product_id'];
        }

        return $selected;
    }

    private function deleteGeneratedBuildProduct(int $productId): void
    {
        $stmt = $this->db->prepare("DELETE FROM products WHERE id = :id AND sku LIKE 'BISBUILD-%'");
        $stmt->execute(['id' => $productId]);
    }

    /**
     * @param array<string,mixed> $profile
     * @param array<string,mixed> $build
     */
    private function upsertBuildMetadata(int $productId, array $profile, string $platform, array $build): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO pc_builds
                (product_id, profile_key, budget_ceiling, target_use, base_platform, metadata_json, last_generated_at)
             VALUES
                (:product_id, :profile_key, :budget_ceiling, :target_use, :base_platform, :metadata_json, NOW())
             ON DUPLICATE KEY UPDATE
                profile_key = VALUES(profile_key),
                budget_ceiling = VALUES(budget_ceiling),
                target_use = VALUES(target_use),
                base_platform = VALUES(base_platform),
                metadata_json = VALUES(metadata_json),
                last_generated_at = NOW()'
        );
        $stmt->execute([
            'product_id' => $productId,
            'profile_key' => $profile['key'],
            'budget_ceiling' => $profile['budget'],
            'target_use' => $profile['use'],
            'base_platform' => $platform,
            'metadata_json' => json_encode([
                'selected' => $build['selected'],
                'recommended_wattage' => $build['recommended_wattage'],
                'commercial_policy_summary' => $this->commercialPolicy['summary'] ?? null,
                'commercial_policy_source' => $this->commercialPolicy['source'] ?? null,
                'commercial_review' => $build['commercial_review'] ?? null,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    /**
     * @param array<string,int> $selected
     */
    private function syncBuildItems(int $productId, array $selected): void
    {
        $this->db->prepare('DELETE FROM pc_build_items WHERE build_product_id = :id')->execute(['id' => $productId]);
        $stmt = $this->db->prepare(
            'INSERT INTO pc_build_items
                (build_product_id, component_product_id, component_type, qty, is_required, is_user_configurable, sort_order)
             VALUES
                (:build_product_id, :component_product_id, :component_type, 1, :is_required, :is_user_configurable, :sort_order)'
        );
        $order = 0;
        foreach ($selected as $type => $componentId) {
            $isRequired = in_array($type, ['cpu', 'motherboard', 'ram', 'storage', 'psu', 'case'], true)
                || ($type === 'gpu' && (bool)($this->compatibility->selectionSummary($selected)['selected']['gpu'] ?? false));
            $stmt->execute([
                'build_product_id' => $productId,
                'component_product_id' => $componentId,
                'component_type' => $type,
                'is_required' => $isRequired ? 1 : 0,
                'is_user_configurable' => in_array($type, ['cpu', 'motherboard', 'ram', 'storage', 'gpu', 'psu', 'case', 'cooler', 'monitor', 'keyboard', 'mouse', 'headset'], true) ? 1 : 0,
                'sort_order' => $order++,
            ]);
        }
    }

    /**
     * @param array<string,mixed> $profile
     * @param array<string,mixed> $build
     */
    private function syncFeatures(int $productId, array $profile, array $build): void
    {
        $this->db->prepare('DELETE FROM product_features WHERE product_id = :id')->execute(['id' => $productId]);
        $features = [
            'Configurazione validata per socket, memoria, storage NVMe, form factor e alimentazione.',
            'Prezzo aggiornato dal catalogo componenti disponibile.',
            'Modificabile con alternative compatibili direttamente dalla scheda prodotto.',
        ];
        if ((float)$profile['budget'] >= 3000) {
            $features[] = 'Fascia alta: include attenzione a raffreddamento, periferiche e resa estetica.';
        }
        $stmt = $this->db->prepare('INSERT INTO product_features (product_id, feature_text, sort_order) VALUES (:id, :text, :sort)');
        foreach ($features as $index => $feature) {
            $stmt->execute(['id' => $productId, 'text' => $feature, 'sort' => $index]);
        }
    }

    /**
     * @param array<string,mixed> $profile
     * @return array{min_ram_gb:int,min_storage_gb:int,storage_interface:string,min_gpu_rank:int,min_psu_watt:int,max_psu_watt:int,min_case_price:int,allow_integrated_psu:bool,allow_basic_psu:bool,office_case_ok:bool}
     */
    private function commercialRules(array $profile): array
    {
        $budget = (float)$profile['budget'];
        $use = (string)$profile['use'];
        $gaming = str_contains($use, 'gaming');
        $office = str_contains($use, 'office') || str_contains($use, 'ufficio');
        $workstation = str_contains($use, 'workstation');

        $minRam = $office && !$workstation && $budget <= 600 ? 8 : 16;
        if ($gaming && $budget >= 1500) {
            $minRam = 32;
        }
        if ($workstation) {
            $minRam = 32;
        }
        if ($budget >= 5000) {
            $minRam = 64;
        }

        $minStorage = $gaming ? 1024 : ($workstation ? 1024 : ($budget <= 600 ? 240 : 512));
        if ($budget >= 3000) {
            $minStorage = 2048;
        }

        $minGpuRank = 0;
        if ($gaming) {
            $minGpuRank = $budget < 1500 ? 4060 : ($budget < 2200 ? 5060 : ($budget < 3500 ? 5070 : 5080));
        }
        $minPsu = $gaming ? ($budget >= 3500 ? 850 : ($budget >= 2200 ? 750 : 650)) : ($workstation ? 550 : 300);
        $maxPsu = $gaming ? ($budget >= 3500 ? 1200 : ($budget >= 2200 ? 1000 : 750)) : ($workstation ? 750 : 650);
        $minCasePrice = $gaming ? ($budget >= 3000 ? 80 : 55) : ($workstation ? 45 : 20);
        $policyRules = (array)($this->commercialPolicy['profiles'][(string)($profile['key'] ?? '')] ?? []);
        $storageInterface = $policyRules['storage_interface'] ?? ($gaming || $workstation ? 'NVMe M.2' : 'SSD');

        return [
            'min_ram_gb' => max(8, (int)($policyRules['min_ram_gb'] ?? $minRam)),
            'min_storage_gb' => max(128, (int)($policyRules['min_storage_gb'] ?? $minStorage)),
            'storage_interface' => (string)$storageInterface,
            'min_gpu_rank' => max(0, (int)($policyRules['gpu_min_rank'] ?? $minGpuRank)),
            'min_psu_watt' => max(300, (int)($policyRules['min_psu_watt'] ?? $minPsu)),
            'max_psu_watt' => max(450, (int)($policyRules['max_psu_watt'] ?? $maxPsu)),
            'min_case_price' => max(0, (int)($policyRules['min_case_price'] ?? $minCasePrice)),
            'allow_integrated_psu' => (bool)($policyRules['allow_integrated_psu'] ?? ($office && !$workstation && $budget <= 600)),
            'allow_basic_psu' => (bool)($policyRules['allow_basic_psu'] ?? ($office && !$workstation && $budget <= 600)),
            'office_case_ok' => (bool)($policyRules['office_case_ok'] ?? ($office && !$gaming)),
        ];
    }

    /**
     * @param array<string,int> $selected
     * @param array<string,mixed> $profile
     */
    private function commercialBuildIsValid(array $selected, array $profile): bool
    {
        if (!$this->compatibility->selectionIsValid($selected)) {
            return false;
        }

        $rules = $this->commercialRules($profile);
        $components = $this->selectedComponentRows($selected);
        $ram = $components['ram'] ?? null;
        $storage = $components['storage'] ?? null;
        if ($ram === null || (int)($ram['capacity_gb'] ?? 0) < $rules['min_ram_gb']) {
            return false;
        }
        if (!$this->ramIsCommercial($ram)) {
            return false;
        }
        if ($storage === null || !$this->storageIsCommercial($storage, $rules['min_storage_gb'], $rules['storage_interface'])) {
            return false;
        }

        if ($rules['min_gpu_rank'] > 0) {
            $gpu = $components['gpu'] ?? null;
            if ($gpu === null || $this->gpuRank((string)$gpu['name']) < $rules['min_gpu_rank']) {
                return false;
            }
        }
        $hasIntegratedPsuCase = !empty($components['case']) && $this->caseHasIntegratedPsu($components['case']);
        if (empty($components['psu']) && (empty($rules['allow_integrated_psu']) || !$hasIntegratedPsuCase)) {
            return false;
        }
        if (!empty($components['psu']) && !$this->psuIsCommercial($components['psu'], $rules['min_psu_watt'], $rules['max_psu_watt'], !empty($rules['allow_basic_psu']))) {
            return false;
        }
        if (empty($components['case']) || !$this->caseIsCommercial($components['case'], $rules['min_case_price'], !empty($rules['allow_integrated_psu']), !empty($rules['office_case_ok']))) {
            return false;
        }

        if ((float)$profile['budget'] >= 3000 && empty($components['cooler'])) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string,mixed> $profile
     * @param array<string,mixed> $build
     */
    /**
     * @return array{approved:bool,reason:string}
     */
    private function commercialReview(array $profile, array $build): array
    {
        if (!$this->commercialPolicyService instanceof PcCommercialPolicyService) {
            return ['approved' => true, 'reason' => 'Policy service non configurato: fallback locale.'];
        }

        $components = $this->selectedComponentRows($build['selected']);
        return $this->commercialPolicyService->reviewBuild($profile, $components, (float)$build['total'], $this->commercialPolicy);
    }

    /**
     * @param array<string,int> $selected
     * @param array<string,mixed> $profile
     */
    private function commercialScore(array $selected, array $profile, float $total): float
    {
        $components = $this->selectedComponentRows($selected);
        $budget = (float)$profile['budget'];
        $score = $total;
        $score += (int)($components['ram']['capacity_gb'] ?? 0) * 12;
        $score += min(4096, (int)($components['storage']['capacity_gb'] ?? 0)) * 0.18;
        $score += $this->gpuRank((string)($components['gpu']['name'] ?? '')) * 0.35;
        if (!empty($components['cooler'])) {
            $score += $budget >= 3000 ? 220 : 60;
        }
        if ($budget > 0) {
            $score -= abs($budget - $total) * 0.08;
        }

        return $score;
    }

    /**
     * @param array<string,mixed> $profile
     * @param array<string,mixed> $build
     */
    private function buildName(array $profile, string $platform, array $build): string
    {
        if ($this->nameGenerator !== null) {
            $generated = (string)call_user_func($this->nameGenerator, $profile, $platform, $build);
            $generated = trim(preg_replace('/\s+/', ' ', strip_tags($generated)) ?? $generated);
            if ($generated !== '') {
                return mb_substr($generated, 0, 150, 'UTF-8');
            }
        }

        $components = $this->selectedComponentRows($build['selected']);
        $hasGpu = isset($components['gpu']);
        $use = (string)($profile['use'] ?? '');
        if (str_contains($use, 'gaming')) {
            $class = 'GAMING';
            $prefix = 'GR';
        } elseif (str_contains($use, 'workstation') || $hasGpu) {
            $class = 'WORKSTATION';
            $prefix = 'WR';
        } else {
            $class = 'OFFICE';
            $prefix = 'OF';
        }
        $platformCode = $platform === 'amd' ? 'AM' : 'IN';
        $cpuGen = $this->cpuGeneration((string)($components['cpu']['name'] ?? ''), $platform);
        $gpuCode = $hasGpu ? $this->gpuCode((string)$components['gpu']['name']) : '';
        $ramCode = $this->ramCode($components['ram'] ?? null);

        return trim(preg_replace('/\s+/', ' ', sprintf(
            'PC %s %s%s%s %s %s',
            $class,
            $prefix,
            $platformCode,
            $cpuGen,
            $gpuCode,
            $ramCode
        )) ?? '');
    }

    /**
     * @param array<string,mixed> $profile
     * @param array<string,mixed> $build
     */
    private function description(string $title, array $profile, array $build): string
    {
        return sprintf(
            '%s e una configurazione %s generata dal catalogo disponibile, con componenti compatibili e prezzo aggiornato. Budget indicativo: EUR %s.',
            $title,
            (string)$profile['use'],
            number_format((float)$profile['budget'], 0, ',', '.')
        );
    }

    /**
     * @param array<string,mixed> $profile
     * @param array<string,mixed> $build
     */
    private function contentHtml(array $profile, array $build): string
    {
        $componentsHtml = $this->componentsHtml($build['selected']);

        return '<p>PC assemblato con componenti selezionati dal listino aggiornato. La configurazione viene proposta solo quando CPU, scheda madre, RAM, alimentatore e formato fisico risultano coerenti.</p>'
            . $componentsHtml
            . '<p>Totale componenti attuale: <strong>EUR ' . number_format((float)$build['total'], 2, ',', '.') . '</strong>. Alimentatore consigliato: <strong>' . (int)$build['recommended_wattage'] . 'W o superiore</strong>.</p>'
            . (((float)$profile['budget'] >= 3000)
                ? '<p>Per questa fascia il preventivo considera anche raffreddamento, periferiche e resa estetica complessiva.</p>'
                : '');
    }

    /**
     * @param array<string,int> $selected
     * @return array<string,array<string,mixed>>
     */
    private function selectedComponentRows(array $selected): array
    {
        if ($selected === []) {
            return [];
        }

        $ids = array_values(array_unique(array_map('intval', $selected)));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            "SELECT p.id, p.name, p.image_url, p.stock_status, p.stock_qty, COALESCE(p.sale_price, p.price, 0) price,
                    s.component_type, s.capacity_gb, s.interface_type, s.wattage, s.metadata_json
             FROM products p
             LEFT JOIN pc_component_specs s ON s.product_id = p.id
             WHERE p.id IN ({$placeholders})"
        );
        $stmt->execute($ids);

        $byId = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $byId[(int)$row['id']] = $row;
        }

        $out = [];
        foreach ($selected as $slot => $id) {
            if (isset($byId[(int)$id])) {
                $out[$slot] = $byId[(int)$id];
            }
        }

        return $out;
    }

    /**
     * @param array<string,mixed> $build
     */
    private function componentImage(array $build, string $slot): string
    {
        $components = $this->selectedComponentRows($build['selected'] ?? []);
        return trim((string)($components[$slot]['image_url'] ?? ''));
    }

    /**
     * @param array<string,int> $selected
     */
    private function componentsHtml(array $selected): string
    {
        $components = $this->selectedComponentRows($selected);
        $pcSlots = ['cpu', 'motherboard', 'ram', 'storage', 'gpu', 'psu', 'case', 'cooler', 'fan'];
        $peripheralSlots = ['monitor', 'keyboard', 'mouse', 'headset'];
        $labels = PcCompatibilityService::SLOT_LABELS;

        $section = static function (array $slots, string $title) use ($components, $labels): string {
            $items = '';
            foreach ($slots as $slot) {
                if (empty($components[$slot])) {
                    continue;
                }
                $name = htmlspecialchars((string)$components[$slot]['name'], ENT_QUOTES, 'UTF-8');
                $label = htmlspecialchars((string)($labels[$slot] ?? $slot), ENT_QUOTES, 'UTF-8');
                $items .= '<li><strong>' . $label . ':</strong> ' . $name . '</li>';
            }

            return $items === '' ? '' : '<h3>' . $title . '</h3><ul>' . $items . '</ul>';
        };

        return $section($pcSlots, 'Componenti nel PC') . $section($peripheralSlots, 'Periferiche');
    }

    /**
     * @param array<string,int> $selected
     * @return array{status:string,qty:int}
     */
    private function buildStock(array $selected): array
    {
        $components = $this->selectedComponentRows($selected);
        if ($components === []) {
            return ['status' => 'su richiesta', 'qty' => 0];
        }

        $minQty = null;
        foreach ($components as $component) {
            $status = strtolower(trim((string)($component['stock_status'] ?? '')));
            if (!in_array($status, ['instock', 'in-stock', 'disponibile', '1', 'true'], true)) {
                return ['status' => 'su richiesta', 'qty' => 0];
            }
            $qty = max(0, (int)($component['stock_qty'] ?? 0));
            $minQty = $minQty === null ? $qty : min($minQty, $qty);
        }

        $qty = max(0, (int)($minQty ?? 0));
        return $qty > 0 ? ['status' => 'disponibile', 'qty' => $qty] : ['status' => 'su richiesta', 'qty' => 0];
    }

    private function cpuGeneration(string $name, string $platform): string
    {
        $haystack = mb_strtolower($name, 'UTF-8');
        if ($platform === 'amd' && preg_match('/\bryzen\s+[3579]\D+(\d{4})[a-z0-9]*\b/u', $haystack, $m)) {
            return substr($m[1], 0, 1);
        }
        if ($platform === 'amd' && preg_match('/\b(\d{4})[a-z0-9]*\b/u', $haystack, $m)) {
            return substr($m[1], 0, 1);
        }
        if ($platform === 'intel' && preg_match('/\bi[3579]-(\d{2})\d{3}[a-z]*\b/u', $haystack, $m)) {
            return ltrim($m[1], '0') ?: $m[1];
        }
        if ($platform === 'intel' && preg_match('/\b(?:core\s+)?ultra\s+[3579]\D+(\d)\d{2}[a-z]*\b/u', $haystack, $m)) {
            return $m[1];
        }

        return '';
    }

    private function gpuCode(string $name): string
    {
        $haystack = mb_strtolower($name, 'UTF-8');
        if (preg_match('/\b(?:geforce\s+)?rtx\s*(\d{4})\s*(ti|super)?\b/u', $haystack, $m)) {
            return strtoupper($m[1] . ($m[2] ?? ''));
        }
        if (preg_match('/\bradeon\s+rx\s*(\d{4})\s*(xtx|xt)?\b/u', $haystack, $m)
            || preg_match('/\brx\s*(\d{4})\s*(xtx|xt)?\b/u', $haystack, $m)) {
            return strtoupper($m[1] . ($m[2] ?? ''));
        }

        return '';
    }

    private function gpuRank(string $name): int
    {
        $haystack = mb_strtolower($name, 'UTF-8');
        if (preg_match('/\brtx\s*(\d{4})\s*(ti|super)?\b/u', $haystack, $m)) {
            return (int)$m[1] + (($m[2] ?? '') === 'ti' ? 5 : 0) + (($m[2] ?? '') === 'super' ? 3 : 0);
        }
        if (preg_match('/\brx\s*(\d{4})\s*(xtx|xt)?\b/u', $haystack, $m)
            || preg_match('/\bradeon\s+rx\s*(\d{4})\s*(xtx|xt)?\b/u', $haystack, $m)) {
            $series = (int)$m[1];
            $mapped = $series >= 9000 ? $series - 4000 : $series - 3000;
            return $mapped + (($m[2] ?? '') === 'xt' ? 5 : 0) + (($m[2] ?? '') === 'xtx' ? 8 : 0);
        }
        if (preg_match('/\barc\s+[ab](\d{3})\b/u', $haystack, $m)) {
            return 3500 + (int)$m[1];
        }

        return 0;
    }

    /**
     * @param array<string,mixed> $ram
     */
    private function ramIsCommercial(array $ram): bool
    {
        $name = mb_strtolower((string)($ram['name'] ?? ''), 'UTF-8');

        return !preg_match('/\b(ddr2|ddr3l?|sodimm|so-dimm|registered|ecc)\b/u', $name);
    }

    /**
     * @param array<string,mixed> $storage
     */
    private function storageIsCommercial(array $storage, int $minGb, string $interface): bool
    {
        $capacity = (int)($storage['capacity_gb'] ?? ($storage['specs']['capacity_gb'] ?? 0));
        $actualInterface = (string)($storage['interface_type'] ?? ($storage['specs']['interface_type'] ?? ''));
        $name = mb_strtolower((string)($storage['name'] ?? ''), 'UTF-8');
        if ($capacity < $minGb) {
            return false;
        }
        if ($interface === 'SSD') {
            return in_array($actualInterface, ['SATA', 'NVMe M.2'], true) || str_contains($name, 'ssd');
        }
        if ($interface !== '' && $actualInterface !== $interface) {
            return false;
        }

        return !preg_match('/\b(hdd|hard disk|harddisk|meccanico)\b/u', $name);
    }

    /**
     * @param array<string,mixed> $psu
     */
    private function psuIsCommercial(array $psu, int $minWatt, int $maxWatt, bool $allowBasic = false): bool
    {
        $name = mb_strtolower((string)($psu['name'] ?? ''), 'UTF-8');
        $wattage = (int)($psu['wattage'] ?? ($psu['specs']['wattage'] ?? 0));
        $price = (float)($psu['price'] ?? 0);
        if ($wattage < $minWatt || $wattage > $maxWatt) {
            return false;
        }
        if ($price < ($allowBasic ? 22 : 45)) {
            return false;
        }
        if ($allowBasic && $wattage <= 550) {
            return true;
        }

        return $this->psuQualityBonus($name) > 0;
    }

    private function psuQualityBonus(string $name): int
    {
        $haystack = mb_strtolower($name, 'UTF-8');
        if (preg_match('/\b(titanium|platinum)\b/u', $haystack)) return 5;
        if (preg_match('/\b(gold|cybenetics gold|80\+ gold|80 plus gold)\b/u', $haystack)) return 4;
        if (preg_match('/\b(bronze|80\+ bronze|80 plus bronze)\b/u', $haystack)) return 3;
        if (preg_match('/\b(80\+|80 plus|pfc attivo|active pfc|atx 3\.1|pcie5|pcie 5)\b/u', $haystack)) return 2;

        return 0;
    }

    /**
     * @param array<string,mixed> $case
     */
    private function caseIsCommercial(array $case, int $minPrice, bool $allowIntegratedPsu = false, bool $officeCaseOk = false): bool
    {
        $name = mb_strtolower((string)($case['name'] ?? ''), 'UTF-8');
        $price = (float)($case['price'] ?? 0);
        if ($price < $minPrice) {
            return false;
        }
        if (!$allowIntegratedPsu && preg_match('/\b(con alimentatore|psu\s*\d+w|\d{3,4}w\s*psu)\b/u', $name)) {
            return false;
        }
        if ($officeCaseOk && preg_match('/\b(office|micro|mini tower|matx|slim|desktop)\b/u', $name)) {
            return true;
        }
        if ($allowIntegratedPsu && $this->caseHasIntegratedPsu($case)) {
            return true;
        }

        return $this->caseQualityBonus($name) > 0;
    }

    /**
     * @param array<string,mixed> $case
     */
    private function caseHasIntegratedPsu(array $case): bool
    {
        $name = mb_strtolower((string)($case['name'] ?? ''), 'UTF-8');

        return (bool)preg_match('/\b(con alimentatore|alimentatore incluso|psu\s*\d+w|\d{3,4}w\s*psu|with psu)\b/u', $name);
    }

    private function caseQualityBonus(string $name): int
    {
        $haystack = mb_strtolower($name, 'UTF-8');
        $score = 0;
        if (preg_match('/\b(gaming|rgb|argb|airflow|mesh|forge|rebel|dark cave|showbui|masterbox|tuf)\b/u', $haystack)) $score += 2;
        if (preg_match('/\b(3x|4x|3 fan|4 fan|vetro temperato|temp glass|tempered glass|type-c)\b/u', $haystack)) $score += 1;

        return $score;
    }

    /**
     * @param array<string,mixed>|null $ram
     */
    private function ramCode(?array $ram): string
    {
        if ($ram === null) {
            return '';
        }
        $capacity = (int)($ram['capacity_gb'] ?? 0);
        if ($capacity <= 0 && preg_match('/\b(\d+)\s*gb\b/u', mb_strtolower((string)($ram['name'] ?? ''), 'UTF-8'), $m)) {
            $capacity = (int)$m[1];
        }

        return $capacity > 0 ? (string)$capacity : '';
    }

    /**
     * @return array<string,mixed>|null
     */
    private function productBySku(string $sku): ?array
    {
        $stmt = $this->db->prepare('SELECT id, slug FROM products WHERE sku = :sku LIMIT 1');
        $stmt->execute(['sku' => $sku]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function uniqueSlug(string $base): string
    {
        $slug = mb_strtolower($base, 'UTF-8');
        $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug) ?? $slug;
        $slug = trim($slug, '-');
        $slug = mb_substr($slug, 0, 140, 'UTF-8') ?: 'pc-bisped';
        $seed = $slug;
        $n = 2;
        while (true) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM products WHERE slug = :slug');
            $stmt->execute(['slug' => $slug]);
            if ((int)$stmt->fetchColumn() === 0) {
                return $slug;
            }
            $slug = $seed . '-' . $n++;
        }
    }

    /**
     * @param array<string,mixed> $cpu
     */
    private function cpuRequiresDiscreteGpu(array $cpu): bool
    {
        $name = mb_strtolower((string)($cpu['name'] ?? ''), 'UTF-8');
        if (preg_match('/\bryzen\b.*\b\d{4}\s*(g|gt|ge)\b/u', $name)) {
            return false;
        }
        if (preg_match('/\b(?:i[3579]|core\s+(?:ultra\s+)?[3579])[-\s]?\d{4,5}(?:kf|f)\b/u', $name)) {
            return true;
        }
        $metadata = json_decode((string)($cpu['metadata_json'] ?? ''), true);
        if (!is_array($metadata) || !array_key_exists('integrated_graphics', $metadata)) {
            if (preg_match('/\bryzen\b.*\b(?:7500f|8400f)\b/u', $name)) {
                return true;
            }

            return false;
        }

        return $metadata['integrated_graphics'] === false;
    }
}
