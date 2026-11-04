<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
$db = \App\Core\Database::connection();
echo json_encode(\App\Services\Catalog\CatalogHealth::report($db, (array)config('catalog', [])), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
