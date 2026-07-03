<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

$db = Database::connection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$columns = [
    'is_payment_product' => ['definition' => 'TINYINT(1) NOT NULL DEFAULT 0', 'after' => 'featured_order'],
    'payment_method' => ['definition' => "ENUM('none','paypal','crypto') NOT NULL DEFAULT 'none'", 'after' => 'is_payment_product'],
    'delivery_method' => ['definition' => "ENUM('shipping','pickup','both') NOT NULL DEFAULT 'pickup'", 'after' => 'payment_method'],
    'paypal_pay_later_enabled' => ['definition' => 'TINYINT(1) NOT NULL DEFAULT 0', 'after' => 'delivery_method'],
    'payment_notes' => ['definition' => 'VARCHAR(255) NULL', 'after' => 'paypal_pay_later_enabled'],
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

    $db->exec(sprintf(
        'ALTER TABLE products ADD COLUMN `%s` %s AFTER `%s`',
        $column,
        $definition['definition'],
        $definition['after']
    ));
    fwrite(STDOUT, "Added products.{$column}\n");
}

$db->exec(
    "CREATE TABLE IF NOT EXISTS product_payment_orders (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(32) NOT NULL UNIQUE,
        product_id INT UNSIGNED NOT NULL,
        product_name VARCHAR(190) NOT NULL,
        product_slug VARCHAR(150) NOT NULL,
        payment_method ENUM('paypal','crypto') NOT NULL,
        delivery_method ENUM('shipping','pickup') NOT NULL DEFAULT 'pickup',
        status ENUM('created','approved','completed','cancelled','failed','pending_crypto') NOT NULL DEFAULT 'created',
        currency CHAR(3) NOT NULL DEFAULT 'EUR',
        amount DECIMAL(10,2) NOT NULL,
        paypal_order_id VARCHAR(64) NULL UNIQUE,
        paypal_capture_id VARCHAR(120) NULL,
        payer_email VARCHAR(190) NULL,
        payer_name VARCHAR(190) NULL,
        raw_response_json JSON NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        INDEX idx_product_payment_orders_product (product_id),
        INDEX idx_product_payment_orders_status (status),
        INDEX idx_product_payment_orders_paypal_order (paypal_order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

fwrite(STDOUT, "Payments schema ready.\n");
