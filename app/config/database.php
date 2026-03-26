<?php

declare(strict_types=1);

return [
    'default_connection' => 'dashboard',
    'connections' => [
        'dashboard' => [
            'host' => getenv('DB_DASHBOARD_HOST') ?: '127.0.0.1',
            'port' => (int) (getenv('DB_DASHBOARD_PORT') ?: 3306),
            'dbname' => getenv('DB_DASHBOARD_NAME') ?: 'hos_dashboard',
            'charset' => getenv('DB_DASHBOARD_CHARSET') ?: 'utf8mb4',
            'username' => getenv('DB_DASHBOARD_USER') ?: '',
            'password' => getenv('DB_DASHBOARD_PASS') ?: '',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => (int) (getenv('DB_DASHBOARD_TIMEOUT') ?: 5),
            ],
        ],
        'source' => [
            'host' => getenv('DB_SOURCE_HOST') ?: '127.0.0.1',
            'port' => (int) (getenv('DB_SOURCE_PORT') ?: 3306),
            'dbname' => getenv('DB_SOURCE_NAME') ?: 'hosxpv4',
            'charset' => getenv('DB_SOURCE_CHARSET') ?: 'utf8mb4',
            'username' => getenv('DB_SOURCE_USER') ?: '',
            'password' => getenv('DB_SOURCE_PASS') ?: '',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => (int) (getenv('DB_SOURCE_TIMEOUT') ?: 5),
            ],
        ],
    ],
];