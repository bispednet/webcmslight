<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
$db = \App\Core\Database::connection();
$columns = [
    'supplier_key' => 'VARCHAR(32) NULL',
    'supplier_cost' => 'DECIMAL(12,4) NULL',
    'supplier_status' => "VARCHAR(32) NOT NULL DEFAULT 'unverified'",
    'supplier_seen_at' => 'DATETIME NULL',
    'supplier_stock_checked_at' => 'DATETIME NULL',
    'supplier_price_checked_at' => 'DATETIME NULL',
    'min_sell_price' => 'DECIMAL(12,2) NULL',
    'catalog_block_reason' => 'VARCHAR(80) NULL',
];
foreach ($columns as $name => $definition) {
    $check = $db->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=\'products\' AND column_name=:name');
    $check->execute(['name' => $name]);
    if (!(int)$check->fetchColumn()) $db->exec("ALTER TABLE products ADD COLUMN `{$name}` {$definition}");
}
$db->exec("UPDATE products SET supplier_key='runner', supplier_status='unverified', catalog_block_reason='initial_sync_required'
    WHERE supplier_key IS NULL AND sku IS NOT NULL AND sku<>'' AND COALESCE(category,'')<>'pc-custom'
      AND (featured_order >= 100 OR image_url LIKE '/media/products/runner/%')");
$db->exec("CREATE TABLE IF NOT EXISTS catalog_sync_runs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, supplier_key VARCHAR(32) NOT NULL,
    mode VARCHAR(32) NOT NULL, status VARCHAR(20) NOT NULL,
    started_at DATETIME NOT NULL, completed_at DATETIME NULL,
    input_count INT UNSIGNED NOT NULL DEFAULT 0, stats_json JSON NULL,
    error_message VARCHAR(500) NULL, INDEX idx_catalog_sync_supplier (supplier_key, started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$indexes = $db->query('SHOW INDEX FROM products')->fetchAll(PDO::FETCH_ASSOC);
if (!in_array('idx_products_supplier', array_column($indexes, 'Key_name'), true)) $db->exec('ALTER TABLE products ADD INDEX idx_products_supplier (supplier_key, supplier_status)');
echo "Catalog safety schema ready; a successful supplier sync is required before imported products can be sold.\n";
