<?php

namespace App\Services;

use App\Helpers\CacheHelper;
use App\Repositories\DashboardRepository;
use App\Support\Config;

final class DashboardService
{
    private DashboardRepository $repository;

    public function __construct(DashboardRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getFilterOptions(): array
    {
        $rightsMap = Config::get('rights_map');
        $rights = [];
        foreach ($rightsMap as $value => $item) {
            $rights[] = [
                'value' => $value,
                'label' => $item['label'],
            ];
        }

        return [
            'clinics' => $this->repository->getClinicOptions(),
            'rights' => $rights,
        ];
    }

    public function getSummary(array $filters): array
    {
        $cacheKey = 'dashboard_summary_' . md5(json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return CacheHelper::remember($cacheKey, fn (): array => $this->repository->getSummary($filters));
    }
}
