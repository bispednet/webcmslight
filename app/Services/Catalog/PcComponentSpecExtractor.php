<?php
declare(strict_types=1);

namespace App\Services\Catalog;

use PDO;

final class PcComponentSpecExtractor
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * @return array{synced:int, skipped:int}
     */
    public function syncCatalog(bool $dryRun = false): array
    {
        $stmt = $this->db->query(
            "SELECT id, name, category, subcategory, subcategory_label, tags
             FROM products
             WHERE category IN ('componenti','gaming','monitor','accessori','server')
                OR subcategory IN ('cpu','mainboard','memorie-ram','ssd-interni','hard-disk-interni','schede-video')"
        );

        $stats = ['synced' => 0, 'skipped' => 0];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $product) {
            $spec = $this->infer($product);
            if ($spec === null) {
                if (!$dryRun) {
                    $this->db->prepare('DELETE FROM pc_component_specs WHERE product_id = :id')
                        ->execute(['id' => (int)$product['id']]);
                }
                $stats['skipped']++;
                continue;
            }
            if (!$dryRun) {
                $this->upsert((int)$product['id'], $spec);
            }
            $stats['synced']++;
        }

        return $stats;
    }

    /**
     * @param array<string,mixed> $product
     * @return array<string,mixed>|null
     */
    public function infer(array $product): ?array
    {
        $name = (string)($product['name'] ?? '');
        $category = (string)($product['category'] ?? '');
        $subcategory = (string)($product['subcategory'] ?? '');
        $label = (string)($product['subcategory_label'] ?? '');
        $tags = (string)($product['tags'] ?? '');
        $haystack = mb_strtolower($name . ' ' . $category . ' ' . $subcategory . ' ' . $label . ' ' . $tags, 'UTF-8');

        $type = $this->componentType($haystack, $subcategory);
        if ($type === null) {
            return null;
        }

        $socket = $this->socket($haystack, $type);
        $chipset = $this->chipset($haystack);
        $memoryType = $this->memoryType($haystack, $socket, $chipset, $type);
        $formFactor = $this->formFactor($haystack, $type);
        $wattage = $this->wattage($haystack, $type);
        $capacity = $this->capacityGb($haystack, $type);
        $interface = $this->interfaceType($haystack, $type);
        $platform = $this->platformBrand($haystack, $socket, $chipset);
        $metadata = [
            'supports_form_factors' => $type === 'case' ? $this->supportedFormFactors($formFactor) : [],
            'integrated_graphics' => $type === 'cpu' ? $this->integratedGraphics($haystack, $socket) : null,
            'raw_name' => $name,
        ];

        return [
            'component_type' => $type,
            'platform_brand' => $platform,
            'socket' => $socket,
            'chipset' => $chipset,
            'memory_type' => $memoryType,
            'form_factor' => $formFactor,
            'wattage' => $wattage,
            'capacity_gb' => $capacity,
            'interface_type' => $interface,
            'metadata_json' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'confidence' => $this->confidence($type, $socket, $memoryType, $wattage, $formFactor),
        ];
    }

    /**
     * @param array<string,mixed> $spec
     */
    private function upsert(int $productId, array $spec): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO pc_component_specs
                (product_id, component_type, platform_brand, socket, chipset, memory_type, form_factor, wattage, capacity_gb, interface_type, metadata_json, confidence)
             VALUES
                (:product_id, :component_type, :platform_brand, :socket, :chipset, :memory_type, :form_factor, :wattage, :capacity_gb, :interface_type, :metadata_json, :confidence)
             ON DUPLICATE KEY UPDATE
                component_type = VALUES(component_type),
                platform_brand = VALUES(platform_brand),
                socket = VALUES(socket),
                chipset = VALUES(chipset),
                memory_type = VALUES(memory_type),
                form_factor = VALUES(form_factor),
                wattage = VALUES(wattage),
                capacity_gb = VALUES(capacity_gb),
                interface_type = VALUES(interface_type),
                metadata_json = VALUES(metadata_json),
                confidence = VALUES(confidence)'
        );
        $stmt->execute(['product_id' => $productId] + $spec);
    }

    private function componentType(string $haystack, string $subcategory): ?string
    {
        $sub = mb_strtolower($subcategory, 'UTF-8');
        if (in_array($sub, ['case-accessori', 'hard-disk-accessori', 'tastiere-e-mouse-accessori'], true)) {
            return null;
        }
        $portableBundle = preg_match('/\b(notebook|laptop|loq\s*15|tuf\s+gaming\s+a15|vivobook|ideapad|macbook)\b/u', $haystack)
            || preg_match('/\b(rtx|radeon|geforce).{0,24}\/\s*(?:ryzen|i[3579]|core)\b/u', $haystack)
            || preg_match('/\b(?:ryzen|i[3579]|core).{0,24}\/\s*(?:rtx|radeon|geforce)\b/u', $haystack);

        if ($sub === 'case-ventole' || str_contains($haystack, 'ventola')) return 'fan';
        if ($sub === 'cpu-dissipatori' || str_contains($haystack, 'dissipatore') || str_contains($haystack, 'aio ')) return 'cooler';
        if ($sub === 'cpu' || preg_match('/\b(cpu|processore|processor)\b/u', $haystack)) return 'cpu';
        if ($sub === 'mainboard' || str_contains($haystack, 'mainboard') || str_contains($haystack, 'motherboard') || str_contains($haystack, 'scheda madre')) return 'motherboard';
        if ($sub === 'memorie-ram' || preg_match('/\b(ddr[45]|dimm|sodimm)\b/u', $haystack)) return 'ram';
        if (str_contains($sub, 'ssd') || str_contains($sub, 'hard-disk') || preg_match('/\b(nvme|m\.2|sata|ssd|hdd)\b/u', $haystack)) return 'storage';
        if ($sub === 'schede-video' || (!$portableBundle && preg_match('/\b(scheda video|geforce\s+rtx|rtx\s*\d{4}|radeon\s+rx|rx\s*\d{4}|arc\s+[ab])\b/u', $haystack))) return 'gpu';
        if ($sub === 'alimentatori' || preg_match('/\b(psu|alimentatore|80\+|80 plus)\b/u', $haystack)) return 'psu';
        if ($sub === 'case' || preg_match('/\b(case|tower)\b/u', $haystack)) return 'case';
        if (str_contains($sub, 'monitor') || preg_match('/\bmonitor\b/u', $haystack)) return 'monitor';
        if (str_contains($sub, 'tastiere') || preg_match('/\b(tastiera|keyboard)\b/u', $haystack)) return 'keyboard';
        if ($sub === 'mouse' || preg_match('/\bmouse\b/u', $haystack)) return 'mouse';
        if (str_contains($sub, 'cuffie') || preg_match('/\b(cuffie|headset)\b/u', $haystack)) return 'headset';

        return null;
    }

    private function socket(string $haystack, string $type): ?string
    {
        if (preg_match('/\b(am5|am4|lga\s*1851|lga\s*1700|swrx8|strx4|tr5)\b/u', $haystack, $m)) {
            return strtoupper(str_replace(' ', '', $m[1]));
        }
        if ($type === 'cpu') {
            if (preg_match('/\bryzen\s+(?:[3579]\s*)?(?:7|8|9)\d{3}\b/u', $haystack)) return 'AM5';
            if (preg_match('/\bryzen\s+(?:[3579]\s*)?[1-5]\d{3}\b/u', $haystack)) return 'AM4';
            if (preg_match('/\bcore\s+ultra\s+[3579]\s+2\d{2}/u', $haystack)) return 'LGA1851';
            if (preg_match('/\bi[3579]-1[234]\d{3}\b/u', $haystack)) return 'LGA1700';
        }
        if ($type === 'motherboard') {
            $chipset = $this->chipset($haystack);
            if ($chipset !== null) {
                if (in_array($chipset, ['A620', 'B650', 'B850', 'X670', 'X670E', 'X870', 'X870E'], true)) return 'AM5';
                if (in_array($chipset, ['A520', 'B450', 'B550', 'X570'], true)) return 'AM4';
                if (in_array($chipset, ['H810', 'B860', 'H870', 'Z890'], true)) return 'LGA1851';
                if (in_array($chipset, ['H610', 'B660', 'B760', 'H670', 'H770', 'Z690', 'Z790'], true)) return 'LGA1700';
            }
        }

        return null;
    }

    private function chipset(string $haystack): ?string
    {
        if (preg_match('/\b(x870e|x870|b850|x670e|x670|b650|a620|x570|b550|b450|a520|z890|h870|b860|h810|z790|z690|h770|h670|b760|b660|h610)\b/u', $haystack, $m)) {
            return strtoupper($m[1]);
        }

        return null;
    }

    private function memoryType(string $haystack, ?string $socket, ?string $chipset, string $type): ?string
    {
        if (preg_match('/\bddr\s*5\b/u', $haystack)) return 'DDR5';
        if (preg_match('/\bddr\s*4\b/u', $haystack)) return 'DDR4';
        if ($type === 'cpu' || $type === 'motherboard') {
            if ($socket === 'AM5' || $socket === 'LGA1851') return 'DDR5';
            if ($socket === 'AM4') return 'DDR4';
            if ($chipset !== null && in_array($chipset, ['Z890', 'B860', 'H810', 'X870', 'B850', 'X670', 'B650', 'A620'], true)) return 'DDR5';
        }

        return null;
    }

    private function formFactor(string $haystack, string $type): ?string
    {
        if (!in_array($type, ['motherboard', 'case'], true)) {
            return null;
        }
        if (preg_match('/\b(e-?atx|extended\s+atx)\b/u', $haystack)) return 'E-ATX';
        if (preg_match('/\b(micro\s*atx|m-atx|matx)\b/u', $haystack)) return 'Micro-ATX';
        if (preg_match('/\b(mini\s*itx|itx)\b/u', $haystack)) return 'Mini-ITX';
        if (preg_match('/\batx\b/u', $haystack)) return 'ATX';

        return null;
    }

    private function wattage(string $haystack, string $type): ?int
    {
        if ($type === 'psu' && preg_match('/\b([4-9]\d{2}|1[0-9]{3})\s*w\b/u', $haystack, $m)) {
            return (int)$m[1];
        }
        if ($type === 'gpu') {
            if (preg_match('/\brtx\s*50(90|80|70|60)\b/u', $haystack, $m)) return ['90' => 575, '80' => 360, '70' => 250, '60' => 180][$m[1]];
            if (preg_match('/\brtx\s*40(90|80|70|60)\b/u', $haystack, $m)) return ['90' => 450, '80' => 320, '70' => 220, '60' => 160][$m[1]];
            if (preg_match('/\brx\s*9(900|800|700|600)\b/u', $haystack, $m)) return ['900' => 320, '800' => 300, '700' => 220, '600' => 160][$m[1]];
        }
        if ($type === 'cpu') {
            if (str_contains($haystack, 'x3d')) return 140;
            if (preg_match('/\b(ryzen\s+9|core\s+(?:ultra\s+)?9|i9-)\b/u', $haystack)) return 170;
            if (preg_match('/\b(ryzen\s+7|core\s+(?:ultra\s+)?7|i7-)\b/u', $haystack)) return 125;
            if (preg_match('/\b(ryzen\s+5|core\s+(?:ultra\s+)?5|i5-)\b/u', $haystack)) return 95;
            if (preg_match('/\b(ryzen\s+3|core\s+(?:ultra\s+)?3|i3-)\b/u', $haystack)) return 75;
        }

        return null;
    }

    private function capacityGb(string $haystack, string $type): ?int
    {
        if (!in_array($type, ['ram', 'storage'], true)) {
            return null;
        }
        if (preg_match('/\b(\d+)\s*tb\b/u', $haystack, $m)) return (int)$m[1] * 1024;
        if (preg_match('/\b(\d+)\s*gb\b/u', $haystack, $m)) return (int)$m[1];

        return null;
    }

    private function interfaceType(string $haystack, string $type): ?string
    {
        if ($type !== 'storage') {
            return null;
        }
        if (str_contains($haystack, 'nvme') || str_contains($haystack, 'm.2')) return 'NVMe M.2';
        if (str_contains($haystack, 'sata')) return 'SATA';

        return null;
    }

    private function platformBrand(string $haystack, ?string $socket, ?string $chipset): ?string
    {
        if (str_contains($haystack, 'amd') || in_array($socket, ['AM4', 'AM5'], true) || in_array((string)$chipset, ['A620', 'B650', 'B850', 'X670', 'X670E', 'X870', 'X870E', 'B550', 'X570'], true)) return 'amd';
        if (str_contains($haystack, 'intel') || in_array($socket, ['LGA1700', 'LGA1851'], true) || in_array((string)$chipset, ['Z890', 'B860', 'H810', 'Z790', 'B760'], true)) return 'intel';

        return null;
    }

    private function integratedGraphics(string $haystack, ?string $socket): ?bool
    {
        if (preg_match('/\b(?:i[3579]|core\s+(?:ultra\s+)?[3579])[-\s]?\d{4,5}(?:kf|f)\b/u', $haystack)) {
            return false;
        }
        if (preg_match('/\b(?:i[3579]|core\s+(?:ultra\s+)?[3579])[-\s]?\d{4,5}[a-z]*\b/u', $haystack)) {
            return true;
        }
        if (preg_match('/\bryzen\b.*\b(?:7500f|8400f)\b/u', $haystack)) {
            return false;
        }
        if (preg_match('/\bryzen\b.*\b(?:\d{4}g|apu|vega|radeon graphics)\b/u', $haystack)) {
            return true;
        }
        if ($socket === 'AM5') {
            return true;
        }
        if ($socket === 'AM4') {
            return false;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function supportedFormFactors(?string $formFactor): array
    {
        return match ($formFactor) {
            'E-ATX' => ['E-ATX', 'ATX', 'Micro-ATX', 'Mini-ITX'],
            'ATX' => ['ATX', 'Micro-ATX', 'Mini-ITX'],
            'Micro-ATX' => ['Micro-ATX', 'Mini-ITX'],
            'Mini-ITX' => ['Mini-ITX'],
            default => [],
        };
    }

    private function confidence(string $type, ?string $socket, ?string $memoryType, ?int $wattage, ?string $formFactor): int
    {
        $score = 50;
        if (in_array($type, ['cpu', 'motherboard'], true) && $socket !== null) $score += 30;
        if (in_array($type, ['ram', 'cpu', 'motherboard'], true) && $memoryType !== null) $score += 20;
        if (in_array($type, ['psu', 'cpu', 'gpu'], true) && $wattage !== null) $score += 20;
        if (in_array($type, ['case', 'motherboard'], true) && $formFactor !== null) $score += 20;

        return min(100, $score);
    }
}
