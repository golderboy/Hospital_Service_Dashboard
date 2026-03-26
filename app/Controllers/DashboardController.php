<?php

namespace App\Controllers;

use App\Helpers\FilterHelper;
use App\Repositories\DashboardRepository;
use App\Services\DashboardService;
use App\Support\Database;

final class DashboardController
{
    public function filters(): array
    {
        $service = new DashboardService(new DashboardRepository(Database::connection()));
        return $service->getFilterOptions();
    }

    public function summary(array $input): array
    {
        $service = new DashboardService(new DashboardRepository(Database::connection()));
        $filters = FilterHelper::sanitize($input);

        return $service->getSummary($filters);
    }
}
