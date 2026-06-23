<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

$db = Database::connection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$columns = [
    'image_url' => ['definition' => 'VARCHAR(500) NULL', 'after' => 'description'],
    'subcategory' => ['definition' => 'VARCHAR(80) NULL', 'after' => 'category'],
    'subcategory_label' => ['definition' => 'VARCHAR(120) NULL', 'after' => 'subcategory'],
    'stock_qty' => ['definition' => 'INT UNSIGNED DEFAULT 0', 'after' => 'stock_status'],
];

foreach ($columns as $column => $definition) {
    $exists = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = \'products\' AND column_name = :column'
    );
    $exists->execute(['column' => $column]);
    if ((int)$exists->fetchColumn() > 0) {
        continue;
    }

    // MySQL 5.7 / older MariaDB do not support ADD COLUMN IF NOT EXISTS.
    $db->exec(sprintf(
        'ALTER TABLE products ADD COLUMN `%s` %s AFTER `%s`',
        $column,
        $definition['definition'],
        $definition['after']
    ));
    fwrite(STDOUT, "Added products.{$column}\n");
}

$statements = [
    "CREATE TABLE IF NOT EXISTS pc_component_specs (
        product_id INT UNSIGNED PRIMARY KEY,
        component_type VARCHAR(40) NOT NULL,
        platform_brand VARCHAR(40) NULL,
        socket VARCHAR(40) NULL,
        chipset VARCHAR(40) NULL,
        memory_type VARCHAR(20) NULL,
        form_factor VARCHAR(40) NULL,
        wattage INT UNSIGNED NULL,
        capacity_gb INT UNSIGNED NULL,
        interface_type VARCHAR(60) NULL,
        metadata_json JSON NULL,
        confidence TINYINT UNSIGNED NOT NULL DEFAULT 40,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        INDEX idx_pc_specs_type (component_type),
        INDEX idx_pc_specs_socket (socket),
        INDEX idx_pc_specs_memory (memory_type),
        INDEX idx_pc_specs_platform (platform_brand)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS pc_builds (
        product_id INT UNSIGNED PRIMARY KEY,
        profile_key VARCHAR(80) NOT NULL,
        budget_ceiling DECIMAL(10,2) NULL,
        target_use VARCHAR(120) NOT NULL DEFAULT 'gaming',
        base_platform VARCHAR(40) NULL,
        generation_hint VARCHAR(120) NULL,
        metadata_json JSON NULL,
        last_generated_at DATETIME NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        INDEX idx_pc_builds_profile (profile_key),
        INDEX idx_pc_builds_budget (budget_ceiling)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS pc_build_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        build_product_id INT UNSIGNED NOT NULL,
        component_product_id INT UNSIGNED NOT NULL,
        component_type VARCHAR(40) NOT NULL,
        qty INT UNSIGNED NOT NULL DEFAULT 1,
        is_required TINYINT(1) NOT NULL DEFAULT 1,
        is_user_configurable TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT UNSIGNED NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (build_product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (component_product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE KEY uniq_pc_build_item (build_product_id, component_type, component_product_id),
        INDEX idx_pc_build_items_build (build_product_id, sort_order),
        INDEX idx_pc_build_items_component (component_product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS pc_commercial_policies (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        source VARCHAR(40) NOT NULL DEFAULT 'fallback',
        policy_json JSON NOT NULL,
        notes TEXT NULL,
        generated_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_pc_policies_generated (generated_at),
        INDEX idx_pc_policies_source (source)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($statements as $statement) {
    $db->exec($statement);
}

fwrite(STDOUT, "PC configurator schema ready.\n");
