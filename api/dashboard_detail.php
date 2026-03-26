<?php

declare(strict_types=1);

require __DIR__ . '/_common.php';

try {
    $repo = dashboardRepository();
    $filters = requestFilters();
    $data = $repo->getPhase2Data($filters);
    auditAction('detail_view', 'SUCCESS', ['filters' => $filters]);
    respond(true, $data);
} catch (Throwable $e) {
    auditAction('detail_view', 'ERROR', [], $e->getMessage());
    respond(false, [], $e->getMessage(), 500);
}
