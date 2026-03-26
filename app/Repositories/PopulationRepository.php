<?php

namespace App\Repositories;

use PDO;

final class PopulationRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getLatestSnapshot(): array
    {
        $sql = "
            SELECT
                fps.population_in_area,
                fps.population_in_area_thai,
                fps.population_total,
                DATE_FORMAT(fps.snapshot_at, '%Y-%m-%d %H:%i:%s') AS last_updated
            FROM fact_population_snapshot fps
            ORDER BY fps.snapshot_at DESC, fps.snapshot_id DESC
            LIMIT 1
        ";

        return $this->db->query($sql)->fetch() ?: [];
    }
}
