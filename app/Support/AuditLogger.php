<?php

declare(strict_types=1);

namespace App\Support;

use Throwable;

final class AuditLogger
{
    private static ?bool $hasAuditTable = null;

    public static function log(string $action, string $status = 'SUCCESS', array $context = [], string $message = ''): void
    {
        $config = Config::get('app');
        $security = Config::get('security');
        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $row = [
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'context' => $context,
            'request_method' => (string) ($_SERVER['REQUEST_METHOD'] ?? 'CLI'),
            'request_uri' => (string) ($_SERVER['REQUEST_URI'] ?? 'cli'),
            'user_ip' => Security::clientIp(),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if (($config['audit_log_db'] ?? true) === true) {
            self::writeDatabase($row, (string) $contextJson);
        }

        if (($config['audit_log_file'] ?? true) === true) {
            self::writeFile($row);
        }
    }

    private static function writeDatabase(array $row, string $contextJson): void
    {
        try {
            $db = Database::connection('dashboard');
            if (self::$hasAuditTable === null) {
                $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name');
                $stmt->execute(['table_name' => 'web_audit_log']);
                self::$hasAuditTable = (int) ($stmt->fetchColumn() ?: 0) > 0;
            }
            if (self::$hasAuditTable !== true) {
                return;
            }

            $stmt = $db->prepare(
                'INSERT INTO web_audit_log (action, status, request_method, request_uri, user_ip, message, context_json, created_at) VALUES (:action, :status, :request_method, :request_uri, :user_ip, :message, :context_json, :created_at)'
            );
            $stmt->execute([
                'action' => $row['action'],
                'status' => $row['status'],
                'request_method' => $row['request_method'],
                'request_uri' => $row['request_uri'],
                'user_ip' => $row['user_ip'],
                'message' => $row['message'],
                'context_json' => $contextJson,
                'created_at' => $row['created_at'],
            ]);
        } catch (Throwable $e) {
        }
    }

    private static function writeFile(array $row): void
    {
        try {
            $dir = BASE_PATH . '/storage/logs';
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $line = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
            file_put_contents($dir . '/web_audit.log', $line, FILE_APPEND | LOCK_EX);
        } catch (Throwable $e) {
        }
    }
    
}
