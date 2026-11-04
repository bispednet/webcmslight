<?php
declare(strict_types=1);
// commercial (alias availability) aggiorna costo, prezzo, stock e prodotti rimossi.
require dirname(__DIR__, 2) . '/app/bootstrap.php';
use App\Core\Container;
use App\Core\Database;
use App\Services\Catalog\CatalogPricing;
use App\Services\Catalog\ProductImporter;
use App\Services\Catalog\Suppliers\NexthsAdapter;
use App\Services\Catalog\Suppliers\RunnerAdapter;
$opts=getopt('', ['supplier:','mode:','dry-run','verbose','limit:']);
$supplier=(string)($opts['supplier']??'runner'); $mode=(string)($opts['mode']??'full');
$dryRun=isset($opts['dry-run']); $limit=max(0,(int)($opts['limit']??0));
$config=Container::get('config',[]); $db=Database::connection();
$catalog=CatalogPricing::configuration($db,(array)($config['catalog']??[])); $runId=null;
try {
 if(empty($catalog['enabled'])&&!$dryRun)throw new RuntimeException('Import disabilitato: catalog.enabled=false');
 if(!in_array($mode,['full','availability','commercial'],true))throw new InvalidArgumentException('Modalita non valida');
 $adapter=match($supplier){'runner'=>new RunnerAdapter((array)($catalog['runner']??[])),'nexths'=>new NexthsAdapter((array)($catalog['nexths']??[])),default=>throw new InvalidArgumentException('Fornitore non supportato')};
 if(!$dryRun){$db->prepare("INSERT INTO catalog_sync_runs (supplier_key,mode,status,started_at) VALUES (:supplier,:mode,'running',UTC_TIMESTAMP())")->execute(['supplier'=>$supplier,'mode'=>$mode]);$runId=(int)$db->lastInsertId();}
 $started=microtime(true);$importer=new ProductImporter($db,$catalog);
 if($mode!=='full'){if(!$adapter instanceof RunnerAdapter)throw new InvalidArgumentException('Sync commerciale supportato solo per Runner');$stats=$importer->syncCommercial($adapter,$dryRun);}else $stats=$importer->import($adapter,$dryRun,$limit);
 if(!$dryRun)$stats['pc_builds']=(new \App\Services\Catalog\PcCompatibilityService($db))->refreshBuildProducts();
 $status=$mode==='full'&&$limit>0?'partial':'success';
 if($runId!==null)$db->prepare("UPDATE catalog_sync_runs SET status=:status,completed_at=UTC_TIMESTAMP(),input_count=:count,stats_json=:stats WHERE id=:id")->execute(['status'=>$status,'count'=>($stats['input_count']??0),'stats'=>json_encode($stats,JSON_THROW_ON_ERROR),'id'=>$runId]);
 fwrite(STDOUT,sprintf("%s[%s] Sync %s %s: %s (%.1fs)\n",$dryRun?'[DRY-RUN] ':'',date('Y-m-d H:i'),$supplier,$mode,json_encode($stats),microtime(true)-$started));
 if(!empty($stats['errors'])||!empty($stats['image_errors']))exit(2);
}catch(Throwable $error){
 if($db->inTransaction())$db->rollBack();
 if($runId!==null){try{$db->prepare("UPDATE catalog_sync_runs SET status='failed',completed_at=UTC_TIMESTAMP(),error_message=:error WHERE id=:id")->execute(['error'=>mb_substr($error->getMessage(),0,500),'id'=>$runId]);}catch(Throwable){}}
 fwrite(STDERR,"ERRORE {$mode} {$supplier}: ".$error->getMessage()."\n");exit(1);
}
