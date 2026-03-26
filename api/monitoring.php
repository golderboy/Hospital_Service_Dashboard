<?php

declare(strict_types=1);

require __DIR__ . '/_common.php';

try {
    $repo = dashboardRepository();
    $data = $repo->getMonitoringData();
    auditAction('monitoring_view', 'SUCCESS');
    respond(true, $data);
} catch (Throwable $e) {
    auditAction('monitoring_view', 'ERROR', [], $e->getMessage());
    respond(false, [], $e->getMessage(), 500);
}
