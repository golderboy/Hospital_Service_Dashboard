<?php

declare(strict_types=1);

require __DIR__ . '/_common.php';

try {
    $metric = trim((string) ($_GET['metric'] ?? ''));
    if ($metric === '') {
        respond(false, [], 'ไม่พบ metric ที่ต้องการ drill-down', 422);
    }

    $repo = dashboardRepository();
    $filters = requestFilters();
    $data = $repo->getDrilldownData($metric, $filters);
    auditAction('drilldown_view', 'SUCCESS', ['metric' => $metric, 'filters' => $filters]);
    respond(true, $data);
} catch (Throwable $e) {
    auditAction('drilldown_view', 'ERROR', ['metric' => $_GET['metric'] ?? ''], $e->getMessage());
    respond(false, [], $e->getMessage(), 500);
}
