<?php

declare(strict_types=1);

use App\Support\Database;
use App\Support\Env;

require dirname(__DIR__) . '/app/bootstrap.php';

function sendWebhook(string $url, array $payload): void
{
    if ($url === '') {
        return;
    }

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return;
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        curl_exec($ch);
        curl_close($ch);
        return;
    }

    @file_get_contents($url, false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $json,
            'timeout' => 10,
        ],
    ]));
}

$db = Database::connection('dashboard');
$latest = $db->query("SELECT job_name, status, started_at, finished_at, message FROM etl_job_log ORDER BY etl_job_log_id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
$failed24h = (int) ($db->query("SELECT COUNT(*) FROM etl_job_log WHERE status <> 'SUCCESS' AND started_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)")->fetchColumn() ?: 0);

$payload = [
    'checked_at' => date('Y-m-d H:i:s'),
    'latest' => $latest,
    'failed_count_24h' => $failed24h,
];

$logDir = dirname(__DIR__) . '/storage/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0775, true);
}
file_put_contents($logDir . '/etl_monitor.log', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);

if (($latest['status'] ?? '') !== 'SUCCESS' || $failed24h > 0) {
    sendWebhook((string) (getenv('ETL_FAIL_WEBHOOK_URL') ?: ''), $payload);
    fwrite(STDERR, "ETL ALERT\n");
    exit(2);
}

echo "ETL OK\n";
exit(0);
