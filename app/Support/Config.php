<?php

declare(strict_types=1);

namespace App\Support;

final class Config
{
    private static array $cache = [];

    public static function get(string $name): array
    {
        if (!isset(self::$cache[$name])) {
            $path = BASE_PATH . '/app/config/' . $name . '.php';
            self::$cache[$name] = is_file($path) ? (require $path) : [];
        }

        return self::$cache[$name];
    }
}
