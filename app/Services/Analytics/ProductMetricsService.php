<?php
declare(strict_types=1);

namespace App\Services\Analytics;

use PDO;

final class ProductMetricsService
{
    private ?bool $available = null;

    public function __construct(private PDO $db)
    {
    }

    public function recordView(int $productId): void
    {
        if ($productId <= 0 || !$this->isAvailable()) {
            return;
        }

        try {
            $this->db->beginTransaction();

            $daily = $this->db->prepare(
                'INSERT INTO product_metric_daily (product_id, metric_date, views)
                 VALUES (:id, CURRENT_DATE, 1)
                 ON DUPLICATE KEY UPDATE views = views + 1'
            );
            $daily->execute(['id' => $productId]);

            $summary = $this->db->prepare(
                'INSERT INTO product_metrics (product_id, views_total, views_30d, last_viewed_at)
                 VALUES (:id, 1, 1, NOW())
                 ON DUPLICATE KEY UPDATE views_total = views_total + 1, last_viewed_at = NOW()'
            );
            $summary->execute(['id' => $productId]);

            $refresh = $this->db->prepare(
                'UPDATE product_metrics
                 SET views_30d = (
                     SELECT COALESCE(SUM(views), 0)
                     FROM product_metric_daily
                     WHERE product_id = :daily_id AND metric_date >= CURRENT_DATE - INTERVAL 30 DAY
                 )
                 WHERE product_id = :summary_id'
            );
            $refresh->execute(['daily_id' => $productId, 'summary_id' => $productId]);

            $this->db->commit();
        } catch (\Throwable) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
        }
    }

    private function isAvailable(): bool
    {
        if ($this->available !== null) {
            return $this->available;
        }

        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'product_metrics'");
            $this->available = (bool)$stmt->fetchColumn();
        } catch (\Throwable) {
            $this->available = false;
        }

        return $this->available;
    }
}
