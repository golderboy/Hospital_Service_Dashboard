<?php

namespace App\Services;

use App\Helpers\CacheHelper;
use App\Repositories\PopulationRepository;

final class PopulationService
{
    private PopulationRepository $repository;

    public function __construct(PopulationRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getPopulationSummary(): array
    {
        return CacheHelper::remember('population_summary_latest', function (): array {
            $row = $this->repository->getLatestSnapshot();

            return [
                'population_in_area' => (int) ($row['population_in_area'] ?? 0),
                'population_in_area_thai' => (int) ($row['population_in_area_thai'] ?? 0),
                'population_total' => (int) ($row['population_total'] ?? 0),
                'last_updated' => (string) ($row['last_updated'] ?? date('Y-m-d H:i:s')),
            ];
        });
    }
}
