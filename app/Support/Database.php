<?php

declare(strict_types=1);

namespace App\Support;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static array $connections = [];

    public static function connection(string $name = 'dashboard'): PDO
    {
        if (isset(self::$connections[$name])) {
            return self::$connections[$name];
        }

        $config = Config::get('database');
        $connections = $config['connections'] ?? [];
        if (!isset($connections[$name])) {
            throw new RuntimeException('ไม่พบการตั้งค่าฐานข้อมูลสำหรับ connection: ' . $name);
        }

        $db = $connections[$name];
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db['host'] ?? '127.0.0.1',
            (int) ($db['port'] ?? 3306),
            $db['dbname'] ?? '',
            $db['charset'] ?? 'utf8mb4'
        );

        try {
            self::$connections[$name] = new PDO(
                $dsn,
                (string) ($db['username'] ?? ''),
                (string) ($db['password'] ?? ''),
                $db['options'] ?? [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            throw new RuntimeException('เชื่อมต่อฐานข้อมูลไม่สำเร็จ: ' . $e->getMessage(), 0, $e);
        }

        return self::$connections[$name];
    }
}
