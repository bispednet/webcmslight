<?php
declare(strict_types=1);

namespace App\Services\Catalog;

use PDO;

final class CatalogHealth
{
    public static function report(PDO $db, array $configuration): array
    {
        try {
            $config = CatalogPricing::configuration($db, $configuration);
            $visibility = CatalogSaleGuard::visibilitySql($db, $config);
            $last = $db->query('SELECT mode,status,started_at,completed_at,error_message FROM catalog_sync_runs ORDER BY COALESCE(completed_at,started_at) DESC,id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC) ?: null;
            $success = $db->query("SELECT completed_at FROM catalog_sync_runs WHERE status='success' ORDER BY id DESC LIMIT 1")->fetchColumn();
            $expired = (int)$db->query("SELECT COUNT(*) FROM products WHERE supplier_key IS NOT NULL AND supplier_status='active' AND NOT {$visibility}")->fetchColumn();
            $retired = (int)$db->query("SELECT COUNT(*) FROM products WHERE supplier_status='retired'")->fetchColumn();
            $lastTime = $success ? strtotime($success . ' UTC') : false;
            $warning = !$lastTime || $lastTime < time() - (int)($config['max_age_seconds'] ?? 7200) || $expired > 0 || ($last['status'] ?? '') === 'failed';
            return ['warning' => $warning, 'last' => $last, 'last_success_utc' => $success ?: null, 'expired' => $expired, 'retired' => $retired, 'schema_ready' => true];
        } catch (\Throwable) {
            return ['warning' => true, 'schema_ready' => false, 'expired' => 0, 'retired' => 0, 'last_success_utc' => null, 'last' => null];
        }
    }
}
