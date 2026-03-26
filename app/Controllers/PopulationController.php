<?php

namespace App\Controllers;

use App\Repositories\PopulationRepository;
use App\Services\PopulationService;
use App\Support\Database;

final class PopulationController
{
    public function summary(): array
    {
        $service = new PopulationService(new PopulationRepository(Database::connection()));
        return $service->getPopulationSummary();
    }
}
