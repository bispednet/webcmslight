<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$pdo = \App\Core\Database::connection();
$schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
if ($schema === false) {
    fwrite(STDERR, "Unable to read schema\n");
    exit(1);
}

$tables = [
    'users',
    'user_identities',
    'user_wallets',
    'user_roles',
    'auth_nonces',
    'auth_audit_log',
];

foreach ($tables as $table) {
    if (!preg_match('/CREATE TABLE IF NOT EXISTS ' . preg_quote($table, '/') . ' \\(.+?\\) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;/s', $schema, $match)) {
        fwrite(STDERR, "Table definition not found: {$table}\n");
        exit(1);
    }
    $pdo->exec($match[0]);
    echo "ok {$table}\n";
}
