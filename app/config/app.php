<?php

declare(strict_types=1);

return [
    'name' => getenv('APP_NAME') ?: 'Sobmoei Hospital Dashboard',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => getenv('APP_DEBUG') === '1',
    'timezone' => getenv('APP_TIMEZONE') ?: 'Asia/Bangkok',
    'base_url' => getenv('APP_BASE_URL') ?: '',
    'max_date_range_days' => (int) (getenv('APP_MAX_DATE_RANGE_DAYS') ?: 370),
    'demo_mode' => getenv('APP_DEMO_MODE') === '1',
    'audit_log_db' => getenv('AUDIT_LOG_DB') !== '0',
    'audit_log_file' => getenv('AUDIT_LOG_FILE') !== '0',
    'export_row_limit' => (int) (getenv('APP_EXPORT_ROW_LIMIT') ?: 50000),
    'allowed_patient_types' => ['OPD', 'IPD', 'ER'],
    'cache' => [
        'enabled' => true,
        'path' => BASE_PATH . '/storage/cache',
        'ttl' => 1800,
    ],
];
