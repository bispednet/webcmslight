<?php
declare(strict_types=1);

/**
 * Pipeline giornaliera PC configurabili:
 * 1. aggiorna specifiche componenti
 * 2. genera/aggiorna build PC brandizzate
 *
 * Uso:
 *   php scripts/auto-update/generate-pc-catalog.php [--dry-run] [--no-llm]
 */

require dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Container;
use App\Services\Ai\GeminiClient;
use App\Services\Catalog\PcBuildPlanner;
use App\Services\Catalog\PcCommercialPolicyService;
use App\Services\Catalog\PcCompatibilityService;
use App\Services\Catalog\PcComponentSpecExtractor;

$opts = getopt('', ['dry-run', 'no-llm']);
$dryRun = isset($opts['dry-run']);

$db = Database::connection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$specStats = (new PcComponentSpecExtractor($db))->syncCatalog($dryRun);

$llm = isset($opts['no-llm']) ? null : GeminiClient::fromConfig(Container::get('config', []), 'editorial');
$policy = new PcCommercialPolicyService($db, $llm);
$buildStats = (new PcBuildPlanner($db, new PcCompatibilityService($db), null, $policy))->generateDailyBuilds($dryRun);

fwrite(STDOUT, sprintf(
    "%sPC catalog: specifiche=%d saltate=%d, build create=%d aggiornate=%d saltate=%d\n",
    $dryRun ? '[DRY-RUN] ' : '',
    $specStats['synced'],
    $specStats['skipped'],
    $buildStats['created'],
    $buildStats['updated'],
    $buildStats['skipped']
));
