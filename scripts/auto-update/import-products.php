<?php
declare(strict_types=1);

/**
 * Import / aggiornamento catalogo da fornitore B2B (Runner) nel CMS Bisped.
 *
 * Modalità:
 *   --mode=full          import completo: crea nuovi, aggiorna, esaurisce stock 0,
 *                        marca "ritirato" i prodotti non più nel listino. (default, cron 24h)
 *   --mode=availability  aggiorna solo lo stock_status (leggero, cron 6h).
 *
 * Uso:
 *   php scripts/auto-update/import-products.php --supplier=runner --mode=full [--dry-run] [--verbose] [--limit=N]
 *   php scripts/auto-update/import-products.php --supplier=runner --mode=availability
 *
 * Cron HOST.it (DirectAdmin):
 *   # disponibilità ogni 6h
 *   0 *\/6 * * *  /usr/local/bin/php .../import-products.php --supplier=runner --mode=availability >> storage/logs/products-cron.log 2>&1
 *   # catalogo completo ogni giorno alle 4
 *   0 4 * * *     /usr/local/bin/php .../import-products.php --supplier=runner --mode=full >> storage/logs/products-cron.log 2>&1
 */

require dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Core\Container;
use App\Core\Database;
use App\Services\Catalog\ProductImporter;
use App\Services\Catalog\Suppliers\NexthsAdapter;
use App\Services\Catalog\Suppliers\RunnerAdapter;

$opts     = getopt('', ['supplier:', 'mode:', 'dry-run', 'verbose', 'limit:']);
$supplier = (string)($opts['supplier'] ?? 'runner');
$mode     = (string)($opts['mode'] ?? 'full');
$dryRun   = isset($opts['dry-run']);
$verbose  = isset($opts['verbose']);
$limit    = isset($opts['limit']) ? max(0, (int)$opts['limit']) : 0;

$config  = Container::get('config', []);
$catalog = $config['catalog'] ?? [];

if (empty($catalog['enabled'])) {
    fwrite(STDERR, "Import prodotti disabilitato. Imposta catalog.enabled=true in .env.php\n");
    exit(1);
}

$db = Database::connection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$adapter = match ($supplier) {
    'runner' => new RunnerAdapter((array)($catalog['runner'] ?? [])),
    'nexths' => new NexthsAdapter((array)($catalog['nexths'] ?? [])),
    default  => null,
};
if ($adapter === null) {
    fwrite(STDERR, "Fornitore non supportato: {$supplier}\n");
    exit(1);
}

$importer = new ProductImporter($db, $catalog);
$prefix   = $dryRun ? '[DRY-RUN] ' : '';
$ts       = date('Y-m-d H:i');

try {
    $started = microtime(true);

    if ($mode === 'availability') {
        if (!$adapter instanceof RunnerAdapter) {
            fwrite(STDERR, "mode=availability supportato solo per Runner.\n");
            exit(1);
        }
        $availability = $adapter->fetchAvailability();
        $res = $importer->syncAvailability($availability, $dryRun);
        $el  = round(microtime(true) - $started, 1);
        fwrite(STDOUT, sprintf(
            "%s[%s] Disponibilità %s: tornati disponibili=%d, esauriti=%d (su %d SKU listino) (%.1fs)\n",
            $prefix, $ts, $supplier, $res['available'], $res['depleted'], count($availability), $el
        ));
        exit(0);
    }

    // mode=full
    $stats = $importer->import($adapter, $dryRun, $limit);
    $el = round(microtime(true) - $started, 1);
    fwrite(STDOUT, sprintf(
        "%s[%s] Import %s: creati=%d aggiornati=%d esauriti=%d ritirati=%d saltati=%d errori=%d (%.1fs)\n",
        $prefix, $ts, $supplier,
        $stats['created'], $stats['updated'], $stats['depleted'], $stats['pruned'], $stats['skipped'], $stats['errors'], $el
    ));
} catch (\Throwable $e) {
    fwrite(STDERR, "ERRORE {$mode} {$supplier}: " . $e->getMessage() . "\n");
    exit(1);
}
