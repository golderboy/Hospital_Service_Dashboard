<?php

declare(strict_types=1);

use App\Repositories\DashboardRepository;
use App\Support\AuditLogger;
use App\Support\Config;
use App\Support\Database;

require dirname(__DIR__) . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: ' . (Config::get('security')['cache_control_api'] ?? 'no-store, no-cache, must-revalidate, max-age=0'));

function respond(bool $status, array $data = [], string $message = '', int $httpStatus = 200): void
{
    http_response_code($httpStatus);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function parseDate(?string $value): ?DateTimeImmutable
{
    if ($value === null || $value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    return $date instanceof DateTimeImmutable ? $date : null;
}

function requestFilters(): array
{
    $today = new DateTimeImmutable('today');
    $startDate = parseDate($_GET['start_date'] ?? '') ?? $today;
    $endDate = parseDate($_GET['end_date'] ?? '') ?? $today;
    $maxDays = (int) (Config::get('app')['max_date_range_days'] ?? 370);

    if ($startDate > $endDate) {
        respond(false, [], 'วันที่เริ่มต้นต้องไม่มากกว่าวันที่สิ้นสุด', 422);
    }

    $dateDiff = (int) $startDate->diff($endDate)->days;
    if ($dateDiff > $maxDays) {
        respond(false, [], 'ช่วงวันที่ต้องไม่เกิน ' . $maxDays . ' วัน', 422);
    }

    return [
        'start_date' => $startDate->format('Y-m-d'),
        'end_date' => $endDate->format('Y-m-d'),
        'clinic' => trim((string) ($_GET['clinic'] ?? '')),
        'rights' => trim((string) ($_GET['rights'] ?? '')),
        'patient_type' => trim((string) ($_GET['patient_type'] ?? '')),
    ];
}

function dashboardRepository(): DashboardRepository
{
    return new DashboardRepository(Database::connection('dashboard'));
}

function auditAction(string $action, string $status = 'SUCCESS', array $context = [], string $message = ''): void
{
    AuditLogger::log($action, $status, $context, $message);
}
