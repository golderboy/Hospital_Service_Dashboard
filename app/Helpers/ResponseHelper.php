<?php

namespace App\Helpers;

use App\Support\Config;

final class ResponseHelper
{
    public static function json(bool $status, array $data = [], string $message = '', int $statusCode = 200): void
    {
        $securityConfig = Config::get('security');

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: ' . ($securityConfig['cache_control_api'] ?? 'no-store, no-cache, must-revalidate, max-age=0'));
        header('Pragma: no-cache');

        echo json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function requireMethod(string $method): void
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== strtoupper($method)) {
            self::json(false, [], 'Method Not Allowed', 405);
        }
    }
}
