<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$c = require $root . '/.env.php';
$production = require $root . '/.local/production.env.php';
if (($c['database']['host'] ?? '') !== '127.0.0.1' || (int)$c['database']['port'] !== 3307 || $c['database']['database'] !== 'bisped_local') {
    throw new RuntimeException('Configurazione database locale non isolata');
}
$pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=bisped_local;charset=utf8mb4', $c['database']['username'], $c['database']['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$models = [];
$collect = function (array $items, string $prefix = '') use (&$collect, &$models): void {
    foreach ($items as $key => $value) {
        $path = $prefix === '' ? (string)$key : $prefix . '.' . $key;
        if (is_array($value)) { $collect($value, $path); }
        elseif (preg_match('/(?:^|\.)(?:provider|model|models|temperature|thinking_level|thinking_budget)$/', $path)) { $models[$path] = $value; }
    }
};
$collect($production);
$retained = true;
foreach ($models as $path => $value) {
    $localValue = $c;
    foreach (explode('.', $path) as $key) { $localValue = is_array($localValue) ? ($localValue[$key] ?? null) : null; }
    $retained = $retained && $localValue === $value;
}
$key = $pdo->query("SELECT setting_key FROM settings ORDER BY id LIMIT 1")->fetchColumn();
$writeVerified = false;
if ($key !== false) {
    $pdo->beginTransaction();
    try {
        $update = $pdo->prepare('UPDATE settings SET setting_value = :value WHERE setting_key = :key');
        $probe = 'bisped-local-write-check-' . bin2hex(random_bytes(8));
        $update->execute(['value' => $probe, 'key' => $key]);
        $select = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = :key');
        $select->execute(['key' => $key]);
        $writeVerified = $select->fetchColumn() === $probe;
    } finally { $pdo->rollBack(); }
}
echo json_encode([
    'database' => $pdo->query('SELECT DATABASE()')->fetchColumn(),
    'server_version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
    'tables' => (int)$pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()')->fetchColumn(),
    'products' => (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'blog_posts' => (int)$pdo->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn(),
    'model_settings_preserved' => $retained,
    'database_write_rollback_verified' => $writeVerified,
    'model_settings_count' => count($models),
    'ai_enabled' => (bool)($c['ai_concierge']['enabled'] ?? false),
    'mail_captured_locally' => ($c['mail']['host'] ?? '') === '127.0.0.1',
    'calendar_disabled' => empty($c['calendar']['enabled']),
    'catalog_import_enabled' => !empty($c['catalog']['enabled']),
    'paypal_sandbox' => ($c['paypal']['environment'] ?? '') === 'sandbox' && empty($c['paypal']['client_secret']),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
if (!$retained || !$writeVerified) exit(1);
