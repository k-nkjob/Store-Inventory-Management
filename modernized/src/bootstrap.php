<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$configFile = $root . '/config.php';
$config = require is_file($configFile) ? $configFile : $root . '/config.example.php';

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/Validator.php';
require_once __DIR__ . '/InventoryRepository.php';
require_once __DIR__ . '/StockService.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

$pdo = Database::connect($config['database']);
$auth = new Auth($pdo);
$inventory = new InventoryRepository($pdo);
$stockService = new StockService($pdo, $inventory);

