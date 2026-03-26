<?php

declare(strict_types=1);

require __DIR__ . '/_common.php';

try {
    $repo = dashboardRepository();
    $data = [
        'clinics' => $repo->getClinicOptions(),
        'rights' => $repo->getRightsOptions(),
    ];
    auditAction('filters_view', 'SUCCESS');
    respond(true, $data);
} catch (Throwable $e) {
    auditAction('filters_view', 'ERROR', [], $e->getMessage());
    respond(false, [], $e->getMessage(), 500);
}
