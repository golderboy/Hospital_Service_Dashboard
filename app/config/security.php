<?php

declare(strict_types=1);

$allowedIps = array_values(array_filter(array_map('trim', explode(',', (string) (getenv('APP_ALLOWED_IPS') ?: '')))));
$trustedProxies = array_values(array_filter(array_map('trim', explode(',', (string) (getenv('APP_TRUSTED_PROXIES') ?: '127.0.0.1,::1')))));

return [
    'force_https' => getenv('APP_FORCE_HTTPS') === '1',
    'hsts_enabled' => getenv('APP_HSTS_ENABLED') !== '0',
    'hsts_max_age' => (int) (getenv('APP_HSTS_MAX_AGE') ?: 31536000),
    'session_name' => getenv('APP_SESSION_NAME') ?: 'HSDSESSID',
    'session_secure_cookie' => getenv('APP_SESSION_SECURE_COOKIE') === '1',
    'allowed_ips' => $allowedIps,
    'trusted_proxies' => $trustedProxies,
    'content_security_policy' => getenv('APP_CSP_ENABLED') !== '0',
    'x_frame_options' => getenv('APP_X_FRAME_OPTIONS') ?: 'SAMEORIGIN',
    'referrer_policy' => getenv('APP_REFERRER_POLICY') ?: 'strict-origin-when-cross-origin',
    'permissions_policy' => getenv('APP_PERMISSIONS_POLICY') ?: 'geolocation=(), microphone=(), camera=()',
    'cache_control_api' => getenv('APP_CACHE_CONTROL_API') ?: 'no-store, no-cache, must-revalidate, max-age=0',
];
