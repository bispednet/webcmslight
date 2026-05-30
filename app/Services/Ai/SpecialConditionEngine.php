<?php
declare(strict_types=1);

namespace App\Services\Ai;

use PDO;

final class SpecialConditionEngine
{
    public function __construct(private PDO $db)
    {
    }

    public function find(string $sector, int $score): ?string
    {
        $stmt = $this->db->prepare(
            'SELECT description FROM ai_special_conditions
             WHERE is_active=1 AND min_lead_score <= :score AND (sector=:sector OR sector IS NULL)
             ORDER BY (sector=:sector_order) DESC, sort_order ASC, id ASC LIMIT 1'
        );
        $stmt->execute(['sector' => $sector, 'sector_order' => $sector, 'score' => $score]);
        $value = $stmt->fetchColumn();

        return is_string($value) && $value !== '' ? $value : null;
    }
}
