<?php
declare(strict_types=1);

namespace App\Services\Catalog;

/** Physical modules and purchasable kits are deliberately distinct. */
final class PcMemory
{
    public static function capacity(string $name, string $description = '', bool $ram = false): ?int
    {
        $text = self::normalize($name);
        if ($ram) {
            $layout = self::layout($text);
            if ($layout !== null) {
                $total = $layout['modules'] * $layout['per_module'];
                $remainder = str_replace($layout['match'], '', $text);
                if (preg_match('/(?<!\d)(\d+)\s*gb\b/u', $remainder, $m) && (int)$m[1] !== $total) return null;
                return $total;
            }
        }
        if (preg_match('/(?<!\d)(\d+(?:[.,]\d+)?)\s*(tb|gb)\b/u', $text, $m)) return (int)round((float)str_replace(',', '.', $m[1]) * ($m[2] === 'tb' ? 1024 : 1));
        // Only an explicit SKU attribute may fill a missing title value.
        // Generic descriptions often advertise the maximum of the whole range.
        if (preg_match('/(?:memoria interna|internal memory|capacità (?:ssd|del modulo|totale))\s*:\s*(\d+)\s*(gb|tb)\b/iu', $description, $m)) return (int)$m[1] * (strtolower($m[2]) === 'tb' ? 1024 : 1);
        return null;
    }

    public static function ram(string $name, string $description): array
    {
        $text = self::normalize($name);
        $details = self::normalize($description);
        $layout = self::layout($text);
        $modules = $layout['modules'] ?? 1;
        if ($layout === null && preg_match('/\bkit\s*(?:of\s*)?([1248])\b/u', $text, $m)) $modules = (int)$m[1];
        elseif ($layout === null && preg_match('/kit (?:di |of )?(due|quattro|2|4) modul/iu', $details, $m)) $modules = in_array(strtolower($m[1]), ['quattro', '4'], true) ? 4 : 2;
        $capacity = self::capacity($name, $description, true);
        $form = preg_match('/so[\s-]*dimm/u', $text . ' ' . $details) ? 'SO-DIMM' : (str_contains($text . ' ' . $details, 'dimm') || str_contains($text, 'desktop memory') ? 'DIMM' : null);
        $desktop = $form === 'DIMM' && !preg_match('/registered|\bl?rdimm\b|\bddr[23]l?\b/u', $text);
        $withoutNonEcc = preg_replace('/non[\s-]*ecc/u', '', $text) ?? $text;
        if (preg_match('/\becc\b/u', $withoutNonEcc)) $desktop = false;
        return ['ram_modules_per_unit' => $modules, 'ram_module_capacity_gb' => $capacity && $capacity % $modules === 0 ? (int)($capacity / $modules) : null,
            'ram_form_factor' => $form, 'desktop_ram' => $desktop, 'capacity_gb' => $capacity];
    }

    private static function layout(string $text): ?array
    {
        if (preg_match('/(?<!\d)([1248])\s*[x×]\s*(\d+)\s*(?:gb)?\b/u', $text, $m)) return ['modules' => (int)$m[1], 'per_module' => (int)$m[2], 'match' => $m[0]];
        if (preg_match('/(?<!\d)(\d+)\s*gb\s*[x×]\s*([1248])\b/u', $text, $m)) return ['modules' => (int)$m[2], 'per_module' => (int)$m[1], 'match' => $m[0]];
        return null;
    }

    public static function motherboard(string $name, string $description): array
    {
        $slots = null;
        foreach ([self::normalize($name), self::normalize($description)] as $text) {
            if (preg_match('/(?<![a-z0-9])([1248])\s*(?:x\s*)?(?:ddr[45]|dimms?(?=\b|ddr))/u', $text, $m)
                || preg_match('/(?:numero (?:di )?slot (?:di )?memoria|memory slots|slot dimm)\s*:\s*([1248])\b/u', $text, $m)) { $slots = (int)$m[1]; break; }
            if (preg_match('/\b(due|quattro)\s+slot\s+(?:ddr[45]|dimm|(?:di )?memoria)/u', $text, $m)) { $slots = $m[1] === 'due' ? 2 : 4; break; }
        }
        $maximum = null;
        if (preg_match('/(?:ram massima supportata|memoria interna massima|maximum memory capacity)\s*:?\s*(\d+)\s*gb\b/iu', $description, $m)) $maximum = (int)$m[1];
        elseif (preg_match('/(?:massimo di)\s+(\d+)\s*gb\s+di\s+ram\b/iu', $description, $m)) $maximum = (int)$m[1];
        return ['memory_slots' => $slots, 'memory_max_gb' => $maximum];
    }

    public static function metadata(array $spec): array
    {
        $metadata = $spec['metadata_json'] ?? [];
        return is_array($metadata) ? $metadata : (json_decode((string)$metadata, true) ?: []);
    }

    public static function allowedQuantities(array $board, array $ram): array
    {
        $m = self::metadata($ram); $b = self::metadata($board);
        if (empty($m['desktop_ram']) || empty($ram['capacity_gb']) || empty($m['ram_module_capacity_gb'])) return [];
        $modules = (int)($m['ram_modules_per_unit'] ?? 1);
        // Unknown slot counts never grant permission for multiple modules.
        $slots = (int)($b['memory_slots'] ?? 1);
        $maximum = (int)($b['memory_max_gb'] ?? 0);
        return array_values(array_filter([1, 2, 4], static fn($q) => $q * $modules <= $slots && ($maximum <= 0 || $q * (int)$ram['capacity_gb'] <= $maximum)));
    }

    private static function normalize(string $text): string
    {
        $text = mb_strtolower(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'UTF-8');
        return preg_replace('/(ddr[345])(?=\d)/u', '$1 ', $text) ?? $text;
    }
}
