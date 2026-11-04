<?php
declare(strict_types=1);

namespace App\Services\Catalog;

use PDO;

final class CatalogPricing
{
    public static function configuration(PDO $db, array $catalog): array
    {
        $settings = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'catalog_%'")->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach (['markup_default', 'markup_fixed', 'vat', 'max_discount', 'minimum_markup', 'minimum_profit', 'max_age_seconds', 'markup_low', 'markup_high', 'low_cost_limit', 'high_cost_limit', 'minimum_sale_markup', 'minimum_sale_markup_from_cost'] as $key) {
            if (isset($settings['catalog_' . $key]) && is_numeric($settings['catalog_' . $key])) {
                $catalog[$key] = (float)$settings['catalog_' . $key];
            }
        }
        if (isset($settings['catalog_pricing_mode'])) $catalog['pricing_mode'] = $settings['catalog_pricing_mode'];
        return $catalog;
    }

    public static function markupForCost(float $cost, array $config): float
    {
        $low = (float)($config['low_cost_limit'] ?? 150);
        $high = (float)($config['high_cost_limit'] ?? 1000);
        $lowRate = (float)($config['markup_low'] ?? 0.20);
        $highRate = (float)($config['markup_high'] ?? 0.055);
        if (!is_finite($cost) || $cost <= 0 || !is_finite($low) || !is_finite($high) || $low < 0 || $high <= $low
            || !is_finite($lowRate) || !is_finite($highRate) || $lowRate < 0 || $highRate < 0 || $lowRate > 2 || $highRate > $lowRate) {
            throw new \InvalidArgumentException('Fasce di ricarico non valide');
        }
        if ($cost <= $low) return $lowRate;
        if ($cost >= $high) return $highRate;
        return $lowRate + ($highRate - $lowRate) * (($cost - $low) / ($high - $low));
    }

    public static function minimumPrice(float $cost, array $config): float
    {
        if (!is_finite($cost) || $cost <= 0) throw new \InvalidArgumentException('Costo fornitore non valido');
        $vat = (float)($config['vat'] ?? 0.22);
        $markup = (float)($config['minimum_markup'] ?? 0.055);
        $profit = max((float)($config['minimum_profit'] ?? 5), (float)($config['markup_fixed'] ?? 5));
        $absolute = (float)($config['minimum_sale_markup'] ?? 50);
        $fromCost = (float)($config['minimum_sale_markup_from_cost'] ?? 1000);
        if (!is_finite($vat) || $vat < 0 || $vat > 1 || !is_finite($markup) || $markup < 0 || !is_finite($profit) || $profit < 0
            || !is_finite($absolute) || $absolute < 0 || !is_finite($fromCost) || $fromCost < 0) {
            throw new \InvalidArgumentException('Soglia di sicurezza prezzi non valida');
        }
        $increment = max($cost * $markup + $profit, $cost >= $fromCost ? $absolute : 0);
        return ceil(($cost + $increment) * (1 + $vat) * 100 - 0.000001) / 100;
    }

    public static function minimumPriceSql(string $costColumn, array $config): string
    {
        if (!preg_match('/^(?:[a-z_][a-z0-9_]*\.)?supplier_cost$/i', $costColumn)) throw new \InvalidArgumentException('Colonna costo non valida');
        self::minimumPrice(1, $config);
        $rate = (float)($config['minimum_markup'] ?? 0.055);
        $fixed = max((float)($config['minimum_profit'] ?? 5), (float)($config['markup_fixed'] ?? 5));
        $absolute = (float)($config['minimum_sale_markup'] ?? 50);
        $from = (float)($config['minimum_sale_markup_from_cost'] ?? 1000);
        $vat = (float)($config['vat'] ?? 0.22);
        return "CEILING(({$costColumn} + GREATEST({$costColumn} * {$rate} + {$fixed}, CASE WHEN {$costColumn} >= {$from} THEN {$absolute} ELSE 0 END)) * (1 + {$vat}) * 100) / 100";
    }

    public static function salePrice(float $cost, string $category, array $config): float
    {
        $minimum = self::minimumPrice($cost, $config);
        $graduated = ($config['pricing_mode'] ?? 'graduated') === 'graduated';
        $markup = $graduated
            ? self::markupForCost($cost, $config)
            : (float)($config['markup'][$category] ?? $config['markup_default'] ?? 0.10);
        $fixed = (float)($graduated ? ($config['markup_fixed'] ?? 5) : ($config['fixed'][$category] ?? $config['markup_fixed'] ?? 5));
        if (!is_finite($markup) || $markup < 0 || !is_finite($fixed) || $fixed < 0) throw new \InvalidArgumentException('Ricarico non valido');
        $value = max($minimum, ($cost * (1 + $markup) + $fixed) * (1 + (float)($config['vat'] ?? 0.22)));
        $candidate = floor($value) + 0.90;
        if ($candidate + 0.000001 < $value) $candidate += 1;
        return round($candidate, 2);
    }
}
