<?php
declare(strict_types=1);

/**
 * Genera/aggiorna prodotti "PC configurabile" dal catalogo componenti.
 *
 * Uso:
 *   php scripts/auto-update/generate-pc-builds.php [--dry-run] [--no-llm]
 *
 * Cron consigliato: dopo import-products.php --mode=full e sync-pc-component-specs.php.
 */

require dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Container;
use App\Services\Ai\GeminiClient;
use App\Services\Catalog\PcBuildPlanner;
use App\Services\Catalog\PcCommercialPolicyService;
use App\Services\Catalog\PcCompatibilityService;

$opts = getopt('', ['dry-run', 'no-llm']);
$dryRun = isset($opts['dry-run']);

$db = Database::connection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$llm = isset($opts['no-llm']) ? null : GeminiClient::fromConfig(Container::get('config', []), 'editorial');
$policy = new PcCommercialPolicyService($db, $llm);
$planner = new PcBuildPlanner($db, new PcCompatibilityService($db), null, $policy);
$stats = $planner->generateDailyBuilds($dryRun);

fwrite(STDOUT, sprintf(
    "%sBuild PC: create=%d aggiornate=%d saltate=%d\n",
    $dryRun ? '[DRY-RUN] ' : '',
    $stats['created'],
    $stats['updated'],
    $stats['skipped']
));
