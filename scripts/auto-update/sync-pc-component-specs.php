<?php
declare(strict_types=1);

/**
 * Estrae/aggiorna le specifiche tecniche dei componenti PC dai prodotti importati.
 *
 * Uso:
 *   php scripts/auto-update/sync-pc-component-specs.php [--dry-run]
 */

require dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Core\Database;
use App\Services\Catalog\PcComponentSpecExtractor;

$opts = getopt('', ['dry-run']);
$dryRun = isset($opts['dry-run']);

$db = Database::connection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$extractor = new PcComponentSpecExtractor($db);
$stats = $extractor->syncCatalog($dryRun);

fwrite(STDOUT, sprintf(
    "%sSpecifiche PC: sincronizzate=%d saltate=%d\n",
    $dryRun ? '[DRY-RUN] ' : '',
    $stats['synced'],
    $stats['skipped']
));
