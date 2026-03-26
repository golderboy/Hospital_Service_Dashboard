<?php

declare(strict_types=1);

namespace App\Support;

final class Security
{
    public static function bootstrap(): void
    {
        $config = Config::get('security');
        self::startSession($config);
        self::enforceIpRestriction($config);
        self::enforceHttps($config);

        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: ' . ($config['x_frame_options'] ?? 'SAMEORIGIN'));
            header('Referrer-Policy: ' . ($config['referrer_policy'] ?? 'strict-origin-when-cross-origin'));
            header('Permissions-Policy: ' . ($config['permissions_policy'] ?? 'geolocation=(), microphone=(), camera=()'));

            if (($config['hsts_enabled'] ?? true) === true && self::isHttps()) {
                header('Strict-Transport-Security: max-age=' . (int) ($config['hsts_max_age'] ?? 31536000) . '; includeSubDomains');
            }

            if (($config['content_security_policy'] ?? true) === true) {
                $nonce = self::cspNonce();
                header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'self'");
            }
        }
    }

    public static function cspNonce(): string
    {
        if (!isset($_SESSION['_csp_nonce'])) {
            $_SESSION['_csp_nonce'] = bin2hex(random_bytes(16));
        }

        return (string) $_SESSION['_csp_nonce'];
    }

    public static function clientIp(): string
    {
        $config = Config::get('security');
        $trusted = $config['trusted_proxies'] ?? [];
        $remoteAddr = (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

        if (in_array($remoteAddr, $trusted, true)) {
            $forwarded = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
            if ($forwarded !== '') {
                $parts = array_values(array_filter(array_map('trim', explode(',', $forwarded))));
                if ($parts !== []) {
                    return (string) $parts[0];
                }
            }
            $realIp = (string) ($_SERVER['HTTP_X_REAL_IP'] ?? '');
            if ($realIp !== '') {
                return $realIp;
            }
        }

        return $remoteAddr;
    }

    private static function startSession(array $config): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name((string) ($config['session_name'] ?? 'HSDSESSID'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => (($config['session_secure_cookie'] ?? false) === true) || self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    private static function enforceHttps(array $config): void
    {
        if (($config['force_https'] ?? false) !== true || self::isHttps()) {
            return;
        }

        if (PHP_SAPI === 'cli') {
            return;
        }

        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: https://' . $host . $uri, true, 301);
        exit;
    }

    private static function enforceIpRestriction(array $config): void
    {
        $allowedIps = $config['allowed_ips'] ?? [];
        if ($allowedIps === []) {
            return;
        }

        $clientIp = self::clientIp();
        foreach ($allowedIps as $allowedIp) {
            if (self::ipMatches($clientIp, (string) $allowedIp)) {
                return;
            }
        }

        AuditLogger::log('access_denied_ip', 'DENIED', ['client_ip' => $clientIp], 'IP not in allow list');
        http_response_code(403);
        $isApi = strpos((string) ($_SERVER['REQUEST_URI'] ?? ''), '/api/') !== false;
        if ($isApi) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => false, 'message' => 'IP นี้ไม่ได้รับอนุญาต', 'data' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            header('Content-Type: text/plain; charset=utf-8');
            echo '403 Forbidden';
        }
        exit;
    }

    private static function ipMatches(string $ip, string $rule): bool
    {
        $rule = trim($rule);
        if ($rule === '') {
            return false;
        }
        if (strpos($rule, '/') === false) {
            return $ip === $rule;
        }

        [$subnet, $mask] = array_pad(explode('/', $rule, 2), 2, '32');
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $mask = (int) $mask;
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            $maskLong = -1 << (32 - $mask);
            $subnetLong &= $maskLong;
            return ($ipLong & $maskLong) === $subnetLong;
        }

        return false;
    }

    private static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443'
            || (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }
}
