<?php
declare(strict_types=1);

/**
 * Diagnostica non distruttiva della pipeline PC Custom.
 *
 * Uso:
 *   php scripts/diagnose-pc-catalog.php
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Container;
use App\Core\Database;
use App\Services\Ai\GeminiClient;

$db = Database::connection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$config = Container::get('config', []);

/** @param mixed $value */
function diagnosticLine(string $state, string $label, mixed $value = null): void
{
    $suffix = $value === null || $value === '' ? '' : ': ' . (string)$value;
    fwrite(STDOUT, sprintf("[%s] %s%s\n", $state, $label, $suffix));
}

function tableExists(PDO $db, string $table): bool
{
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table'
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

function scalar(PDO $db, string $query): int
{
    return (int)$db->query($query)->fetchColumn();
}

fwrite(STDOUT, "PC Custom diagnostic\n====================\n");

$database = (string)($config['database']['database'] ?? '');
$appUrl = (string)($config['app']['url'] ?? '');
diagnosticLine($database !== '' ? 'OK' : 'FAIL', 'Database configurato', $database ?: 'mancante');
diagnosticLine($appUrl !== '' ? 'OK' : 'WARN', 'App URL', $appUrl ?: 'mancante');

$geminiReady = GeminiClient::fromConfig($config, 'editorial') instanceof GeminiClient;
diagnosticLine(
    $geminiReady ? 'OK' : 'WARN',
    'Gemini editoriale',
    $geminiReady ? 'configurato' : 'assente: verra usata solo la policy fallback'
);

fwrite(STDOUT, "\nSchema\n------\n");
$requiredTables = ['products', 'pc_component_specs', 'pc_builds', 'pc_build_items', 'pc_commercial_policies'];
$schemaReady = true;
foreach ($requiredTables as $table) {
    $exists = tableExists($db, $table);
    $schemaReady = $schemaReady && $exists;
    diagnosticLine($exists ? 'OK' : 'FAIL', 'Tabella ' . $table, $exists ? 'presente' : 'mancante');
}

$requiredColumns = ['image_url', 'subcategory', 'subcategory_label', 'stock_qty'];
foreach ($requiredColumns as $column) {
    $exists = columnExists($db, 'products', $column);
    $schemaReady = $schemaReady && $exists;
    diagnosticLine($exists ? 'OK' : 'FAIL', 'products.' . $column, $exists ? 'presente' : 'mancante');
}

if (!$schemaReady) {
    fwrite(STDOUT, "\nAZIONE: esegui prima scripts/migrate-pc-configurator.php.\n");
    exit(2);
}

fwrite(STDOUT, "\nCatalogo sorgente\n-----------------\n");
$totalProducts = scalar($db, 'SELECT COUNT(*) FROM products');
$eligibleProducts = scalar(
    $db,
    "SELECT COUNT(*) FROM products
     WHERE category IN ('componenti', 'gaming', 'monitor', 'accessori', 'server')
        OR subcategory IN ('cpu', 'mainboard', 'memorie-ram', 'ssd-interni', 'hard-disk-interni', 'schede-video')"
);
diagnosticLine($totalProducts > 0 ? 'OK' : 'FAIL', 'Prodotti totali', $totalProducts);
diagnosticLine($eligibleProducts > 0 ? 'OK' : 'FAIL', 'Prodotti candidati alle specifiche PC', $eligibleProducts);

$types = $db->query(
    "SELECT component_type, COUNT(*) AS total
     FROM pc_component_specs
     GROUP BY component_type
     ORDER BY component_type"
)->fetchAll(PDO::FETCH_ASSOC);
$typeCounts = [];
foreach ($types as $type) {
    $typeCounts[(string)$type['component_type']] = (int)$type['total'];
}
$specCount = array_sum($typeCounts);
foreach (['cpu', 'motherboard', 'ram', 'storage', 'gpu', 'psu', 'case', 'cpu_cooler'] as $type) {
    $count = $typeCounts[$type] ?? 0;
    diagnosticLine($count > 0 ? 'OK' : 'FAIL', 'Specifiche ' . $type, $count);
}

$availableSpecs = scalar(
    $db,
    "SELECT COUNT(*)
     FROM pc_component_specs s
     INNER JOIN products p ON p.id = s.product_id
     WHERE COALESCE(p.sale_price, p.price, 0) > 0
       AND COALESCE(p.stock_status, '') NOT IN ('esaurito', 'ritirato', 'outofstock', 'non disponibile')"
);
diagnosticLine($availableSpecs > 0 ? 'OK' : 'FAIL', 'Componenti con prezzo e disponibilita', $availableSpecs);

fwrite(STDOUT, "\nOutput PC Custom\n----------------\n");
$buildCount = scalar($db, "SELECT COUNT(*) FROM products WHERE sku LIKE 'BISBUILD-%'");
$buildItems = scalar($db, 'SELECT COUNT(*) FROM pc_build_items');
$lastBuild = $db->query(
    "SELECT MAX(COALESCE(last_generated_at, updated_at))
     FROM pc_builds"
)->fetchColumn();
$lastPolicy = $db->query('SELECT MAX(generated_at) FROM pc_commercial_policies')->fetchColumn();
diagnosticLine($buildCount > 0 ? 'OK' : 'WARN', 'Build pubblicate', $buildCount);
diagnosticLine($buildItems > 0 ? 'OK' : 'WARN', 'Componenti associati alle build', $buildItems);
diagnosticLine($lastBuild ? 'OK' : 'WARN', 'Ultima build', $lastBuild ?: 'mai');
diagnosticLine($lastPolicy ? 'OK' : 'WARN', 'Ultima policy commerciale', $lastPolicy ?: 'mai');

$logPath = BASE_PATH . '/storage/logs/pc-configurator-cron.log';
fwrite(STDOUT, "\nCron\n----\n");
if (!is_file($logPath)) {
    diagnosticLine('WARN', 'Log pc-configurator-cron.log', 'non trovato');
} elseif (!is_readable($logPath)) {
    diagnosticLine('WARN', 'Log pc-configurator-cron.log', 'non leggibile');
} else {
    $size = filesize($logPath);
    diagnosticLine($size > 0 ? 'OK' : 'WARN', 'Log pc-configurator-cron.log', $size . ' byte');
    if ($size > 0) {
        $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $tail = array_slice(is_array($lines) ? $lines : [], -8);
        fwrite(STDOUT, "Ultime righe log:\n");
        foreach ($tail as $line) {
            fwrite(STDOUT, '  ' . $line . "\n");
        }
    }
}

fwrite(STDOUT, "\nEsito\n-----\n");
if ($buildCount > 0) {
    fwrite(STDOUT, "Pipeline attiva: le build sono gia nel database. Se non compaiono sul sito, controllare filtri/categoria o cache.\n");
    exit(0);
}

if ($eligibleProducts === 0) {
    fwrite(STDOUT, "Blocco catalogo: prima deve completarsi l'import Runner con componenti disponibili e prezzati.\n");
    exit(3);
}

if ($specCount === 0) {
    fwrite(STDOUT, "Specifiche non ancora estratte: eseguire generate-pc-catalog.php una volta dopo la migrazione.\n");
    exit(1);
}

if ($availableSpecs === 0) {
    fwrite(STDOUT, "Blocco disponibilita: le specifiche ci sono, ma nessun componente ha prezzo e disponibilita utilizzabili.\n");
    exit(3);
}

fwrite(STDOUT, "Nessuna build pubblicata: eseguire generate-pc-catalog.php manualmente e leggere l'output qui sopra.\n");
exit(1);
