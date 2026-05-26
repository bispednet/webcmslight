<?php
declare(strict_types=1);

define('BISPED_SKIP_DB_BOOTSTRAP', true);
require dirname(__DIR__) . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $pdo = \App\Core\Database::connection();
    $pdo->query('SELECT 1');

    echo json_encode([
        'ok' => true,
        'service' => 'database',
        'time' => gmdate('c'),
    ], JSON_THROW_ON_ERROR);
} catch (\Throwable $e) {
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'service' => 'database',
        'error' => 'Database unavailable',
        'time' => gmdate('c'),
    ], JSON_THROW_ON_ERROR);
}
