<?php

namespace App\Helpers;

use App\Support\Config;

final class CacheHelper
{
    public static function remember(string $key, callable $callback): array
    {
        $config = Config::get('app')['cache'];
        if (empty($config['enabled'])) {
            return $callback();
        }

        $cachePath = rtrim($config['path'], '/');
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0775, true);
        }

        $file = $cachePath . '/' . md5($key) . '.json';
        $ttl = (int) ($config['ttl'] ?? 1800);

        if (is_file($file) && (time() - filemtime($file) < $ttl)) {
            $content = file_get_contents($file);
            if ($content !== false) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        $data = $callback();
        file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $data;
    }
}
