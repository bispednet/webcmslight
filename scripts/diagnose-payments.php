<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Services\Payments\PayPalClient;

$db = Database::connection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function out(string $label, string $value): void
{
    fwrite(STDOUT, str_pad($label, 36) . $value . PHP_EOL);
}

function tableExists(PDO $db, string $table): bool
{
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = :table'
    );
    $stmt->execute(['table' => $table]);

    return (int)$stmt->fetchColumn() > 0;
}

function columnExists(PDO $db, string $table, string $column): bool
{
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column'
    );
    $stmt->execute(['table' => $table, 'column' => $column]);

    return (int)$stmt->fetchColumn() > 0;
}

$paypal = (array)config('paypal', []);
$client = new PayPalClient();

fwrite(STDOUT, "PayPal config\n-------------\n");
out('environment', (string)($paypal['environment'] ?? 'missing'));
out('client_id', trim((string)($paypal['client_id'] ?? '')) !== '' ? 'set' : 'missing');
out('client_secret', trim((string)($paypal['client_secret'] ?? '')) !== '' ? 'set' : 'missing');
out('currency', (string)($paypal['currency'] ?? 'missing'));
out('payee_email', (string)($paypal['payee_email'] ?? 'missing'));
out('configured', $client->isConfigured() ? 'yes' : 'no');

fwrite(STDOUT, "\nSchema\n------\n");
foreach (['is_payment_product', 'payment_method', 'delivery_method', 'paypal_pay_later_enabled', 'payment_notes'] as $column) {
    out('products.' . $column, columnExists($db, 'products', $column) ? 'yes' : 'missing');
}
out('product_payment_orders', tableExists($db, 'product_payment_orders') ? 'yes' : 'missing');

if (tableExists($db, 'product_payment_orders')) {
    fwrite(STDOUT, "\nOrders\n------\n");
    $statusRows = $db->query('SELECT status, COUNT(*) n FROM product_payment_orders GROUP BY status ORDER BY status')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($statusRows as $row) {
        out((string)$row['status'], (string)$row['n']);
    }

    fwrite(STDOUT, "\nLast failed orders\n------------------\n");
    $failed = $db->query(
        'SELECT public_id, product_name, paypal_order_id, raw_response_json, created_at
         FROM product_payment_orders
         WHERE status = \'failed\'
         ORDER BY created_at DESC, id DESC
         LIMIT 5'
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if ($failed === []) {
        fwrite(STDOUT, "None\n");
    }

    foreach ($failed as $row) {
        $raw = json_decode((string)($row['raw_response_json'] ?? ''), true);
        $error = is_array($raw) ? (string)($raw['error'] ?? '') : '';
        fwrite(STDOUT, '- ' . (string)$row['created_at'] . ' ' . (string)$row['public_id'] . ' ' . (string)$row['product_name'] . PHP_EOL);
        fwrite(STDOUT, '  PayPal order: ' . ((string)($row['paypal_order_id'] ?? '') ?: '-') . PHP_EOL);
        fwrite(STDOUT, '  Error: ' . ($error !== '' ? $error : '-') . PHP_EOL);
    }
}

fwrite(STDOUT, "\nPayable products\n----------------\n");
if (columnExists($db, 'products', 'payment_method')) {
    $rows = $db->query(
        "SELECT payment_method, COUNT(*) n
         FROM products
         WHERE is_payment_product = 1
         GROUP BY payment_method"
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($rows as $row) {
        out((string)$row['payment_method'], (string)$row['n']);
    }
}
