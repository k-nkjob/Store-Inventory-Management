<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'Modern Inventory',
        'debug' => false,
    ],
    'database' => [
        'driver' => getenv('DB_DRIVER') ?: 'sqlite',
        'path' => getenv('DB_PATH') ?: __DIR__ . '/database/app.sqlite',
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'name' => getenv('DB_NAME') ?: 'inventory',
        'user' => getenv('DB_USER') ?: '',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
    ],
];

