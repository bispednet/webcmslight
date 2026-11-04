<?php
declare(strict_types=1);

namespace App\Services\Catalog;

use PDO;

final class CatalogSaleGuard
{
    private static array $schema = [];

    public static function managed(array $product): bool
    {
        return !empty($product['supplier_key']) || (
            (string)($product['sku'] ?? '') !== '' && ($product['category'] ?? '') !== 'pc-custom'
            && ((int)($product['featured_order'] ?? 0) >= 100 || str_starts_with((string)($product['image_url'] ?? ''), '/media/products/runner/'))
        );
    }

    public static function reason(array $product, array $config, ?int $now = null, ?float $amount = null): ?string
    {
        if (self::managed($product) && ($product['supplier_status'] ?? '') !== 'active') return (string)($product['catalog_block_reason'] ?? '') ?: 'supplier_unverified';
        $available = in_array(strtolower(trim((string)($product['stock_status'] ?? ''))), ['disponibile', 'instock', 'in-stock', '1', 'true'], true);
        if (!$available || (int)($product['stock_qty'] ?? 0) <= 0) return 'out_of_stock';
        $price = $amount ?? (float)(($product['sale_price'] ?? null) ?: ($product['price'] ?? 0));
        if (!is_finite($price) || $price <= 0) return 'invalid_price';
        if (!self::managed($product)) return null;
        $now ??= time();
        $age = max(60, (int)($config['max_age_seconds'] ?? 7200));
        foreach (['supplier_stock_checked_at', 'supplier_price_checked_at'] as $field) {
            $time = empty($product[$field]) ? false : strtotime($product[$field] . ' UTC');
            if ($time === false || $time > $now + 60 || $time < $now - $age) return 'supplier_data_expired';
        }
        try { $minimum = max((float)($product['min_sell_price'] ?? 0), CatalogPricing::minimumPrice((float)($product['supplier_cost'] ?? 0), $config)); }
        catch (\InvalidArgumentException) { return 'supplier_price_unverified'; }
        return $price + 0.00001 < $minimum ? 'below_minimum_price' : null;
    }

    public static function visibilitySql(PDO $db, array $config, string $alias = ''): string
    {
        if ($alias !== '' && !preg_match('/^[a-z_][a-z0-9_]*$/i', $alias)) throw new \InvalidArgumentException('Alias non valido');
        $p = $alias === '' ? '' : $alias . '.';
        $legacy = "({$p}sku IS NOT NULL AND {$p}sku <> '' AND COALESCE({$p}category,'') <> 'pc-custom' AND ({$p}featured_order >= 100 OR {$p}image_url LIKE '/media/products/runner/%'))";
        $id = spl_object_id($db);
        if (!array_key_exists($id, self::$schema)) self::$schema[$id] = (bool)$db->query("SHOW COLUMNS FROM products LIKE 'supplier_key'")->fetch();
        if (!self::$schema[$id]) return '(NOT ' . $legacy . ')';
        $age = max(60, (int)($config['max_age_seconds'] ?? 7200));
        $floor = 'GREATEST(COALESCE(' . $p . 'min_sell_price,0), ' . CatalogPricing::minimumPriceSql($p . 'supplier_cost', $config) . ')';
        return "(({$p}supplier_key IS NULL AND NOT {$legacy}) OR (
            {$p}supplier_status = 'active' AND {$p}supplier_cost > 0 AND {$p}stock_qty > 0
            AND {$p}stock_status IN ('disponibile','instock','in-stock','1','true')
            AND {$p}supplier_stock_checked_at >= UTC_TIMESTAMP() - INTERVAL {$age} SECOND
            AND {$p}supplier_price_checked_at >= UTC_TIMESTAMP() - INTERVAL {$age} SECOND
            AND {$p}supplier_stock_checked_at <= UTC_TIMESTAMP() + INTERVAL 60 SECOND
            AND {$p}supplier_price_checked_at <= UTC_TIMESTAMP() + INTERVAL 60 SECOND
            AND COALESCE(NULLIF({$p}sale_price,0),{$p}price,0) >= {$floor}
        ))";
    }

    public static function publicProduct(array $product, array $config): array
    {
        $reason = self::reason($product, $config);
        $product['catalog_available'] = $reason === null;
        $product['catalog_sale_block_reason'] = $reason;
        if ($reason !== null && self::managed($product)) {
            $product['stock_status'] = 'non disponibile'; $product['stock_qty'] = 0;
            $product['price'] = null; $product['sale_price'] = null;
        }
        return $product;
    }
}
