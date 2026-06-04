<?php
declare(strict_types=1);

/**
 * Import prodotti da fornitore B2B (Nexths) nel catalogo Bisped.
 *
 * Uso:
 *   php scripts/auto-update/import-products.php --supplier=nexths [--dry-run] [--verbose] [--limit=N]
 *
 * Cron (HOST.it DirectAdmin), una volta al giorno:
 *   /usr/local/bin/php /path/public_html/scripts/auto-update/import-products.php --supplier=nexths >> storage/logs/products-cron.log 2>&1
 */

require dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Core\Container;
use App\Core\Database;
use App\Services\Catalog\ProductImporter;
use App\Services\Catalog\Suppliers\NexthsAdapter;

$opts     = getopt('', ['supplier:', 'dry-run', 'verbose', 'limit:']);
$supplier = (string)($opts['supplier'] ?? 'nexths');
$dryRun   = isset($opts['dry-run']);
$verbose  = isset($opts['verbose']);
$limit    = isset($opts['limit']) ? max(0, (int)$opts['limit']) : 0;

$config   = Container::get('config', []);
$catalog  = $config['catalog'] ?? [];

if (empty($catalog['enabled'])) {
    fwrite(STDERR, "Import prodotti disabilitato. Imposta catalog.enabled=true in .env.php\n");
    exit(1);
}

$db = Database::connection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$adapter = match ($supplier) {
    'nexths' => new NexthsAdapter((array)($catalog['nexths'] ?? [])),
    default  => null,
};

if ($adapter === null) {
    fwrite(STDERR, "Fornitore non supportato: {$supplier}\n");
    exit(1);
}

$importer = new ProductImporter($db, $catalog);

try {
    $started = microtime(true);
    $stats = $importer->import($adapter, $dryRun, $limit);
    $elapsed = round(microtime(true) - $started, 1);

    $prefix = $dryRun ? '[DRY-RUN] ' : '';
    fwrite(STDOUT, sprintf(
        "%s[%s] Import %s: creati=%d aggiornati=%d saltati=%d errori=%d (%.1fs)\n",
        $prefix,
        date('Y-m-d H:i'),
        $supplier,
        $stats['created'],
        $stats['updated'],
        $stats['skipped'],
        $stats['errors'],
        $elapsed
    ));
} catch (\Throwable $e) {
    fwrite(STDERR, "ERRORE import {$supplier}: " . $e->getMessage() . "\n");
    exit(1);
}
