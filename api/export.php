<?php

declare(strict_types=1);

use App\Support\Config;

require __DIR__ . '/_common.php';

function exportFilename(string $title, string $format): string
{
    $safe = preg_replace('/[^A-Za-z0-9_-]+/', '_', strtolower($title)) ?: 'dashboard_export';
    return $safe . '_' . date('Ymd_His') . '.' . ($format === 'excel' ? 'xls' : 'csv');
}

function exportRowsToCsv(array $columns, array $rows): string
{
    $stream = fopen('php://temp', 'r+');
    fputcsv($stream, array_map(static fn (array $col): string => (string) ($col['label'] ?? $col['key'] ?? ''), $columns));
    foreach ($rows as $row) {
        $line = [];
        foreach ($columns as $column) {
            $line[] = (string) ($row[$column['key']] ?? '');
        }
        fputcsv($stream, $line);
    }
    rewind($stream);
    return (string) stream_get_contents($stream);
}

function exportRowsToExcelHtml(string $title, array $columns, array $rows): string
{
    $thead = '';
    foreach ($columns as $column) {
        $thead .= '<th>' . htmlspecialchars((string) ($column['label'] ?? $column['key'] ?? ''), ENT_QUOTES, 'UTF-8') . '</th>';
    }

    $tbody = '';
    foreach ($rows as $row) {
        $tbody .= '<tr>';
        foreach ($columns as $column) {
            $tbody .= '<td>' . htmlspecialchars((string) ($row[$column['key']] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
        }
        $tbody .= '</tr>';
    }

    return '<html><head><meta charset="UTF-8"><title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title></head><body>'
        . '<table border="1"><thead><tr>' . $thead . '</tr></thead><tbody>' . $tbody . '</tbody></table>'
        . '</body></html>';
}

try {
    $format = strtolower(trim((string) ($_GET['format'] ?? 'csv')));
    $scope = strtolower(trim((string) ($_GET['scope'] ?? 'summary')));
    $key = trim((string) ($_GET['metric'] ?? $_GET['table'] ?? 'summary'));

    if (!in_array($format, ['csv', 'excel'], true)) {
        throw new RuntimeException('รองรับเฉพาะ csv และ excel');
    }
    if (!in_array($scope, ['summary', 'drilldown', 'table'], true)) {
        throw new RuntimeException('scope ไม่ถูกต้อง');
    }

    $repo = dashboardRepository();
    $filters = requestFilters();
    $dataset = $repo->getExportDataset($scope, $key, $filters);
    $columns = $dataset['columns'] ?? [];
    $rows = $dataset['rows'] ?? [];

    $limit = (int) (Config::get('app')['export_row_limit'] ?? 50000);
    if (count($rows) > $limit) {
        throw new RuntimeException('จำนวนแถวเกิน limit ที่กำหนด (' . $limit . ')');
    }

    $title = (string) ($dataset['title'] ?? $key ?: 'dashboard_export');
    $filename = exportFilename($title, $format);

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        echo exportRowsToCsv($columns, $rows);
    } else {
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo exportRowsToExcelHtml($title, $columns, $rows);
    }

    auditAction('export_' . $format, 'SUCCESS', ['scope' => $scope, 'key' => $key, 'filters' => $filters]);
    exit;
} catch (Throwable $e) {
    auditAction('export_error', 'ERROR', ['scope' => $_GET['scope'] ?? '', 'key' => $_GET['metric'] ?? ($_GET['table'] ?? '')], $e->getMessage());
    respond(false, [], $e->getMessage(), 500);
}
