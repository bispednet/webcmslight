<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

$db = Database::connection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$statements = [
    "CREATE TABLE IF NOT EXISTS product_metrics (
        product_id INT UNSIGNED PRIMARY KEY,
        views_total BIGINT UNSIGNED NOT NULL DEFAULT 0,
        views_30d BIGINT UNSIGNED NOT NULL DEFAULT 0,
        last_viewed_at TIMESTAMP NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS product_metric_daily (
        product_id INT UNSIGNED NOT NULL,
        metric_date DATE NOT NULL,
        views INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (product_id, metric_date),
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($statements as $sql) {
    $db->exec($sql);
}

fwrite(STDOUT, "Analytics/SEO schema ok\n");
