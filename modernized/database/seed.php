<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$config = require is_file($root . '/config.php')
    ? $root . '/config.php'
    : $root . '/config.example.php';
require_once $root . '/src/Database.php';

$pdo = Database::connect($config['database']);
$driver = $config['database']['driver'];
$schema = __DIR__ . '/schema.' . $driver . '.sql';
if (!is_file($schema)) {
    throw new RuntimeException('Schema not found: ' . $schema);
}
$pdo->exec((string) file_get_contents($schema));

$email = getenv('APP_ADMIN_EMAIL') ?: 'admin@example.com';
$password = getenv('APP_ADMIN_PASSWORD') ?: 'change-me-now';
if ($driver !== 'sqlite' && !getenv('APP_ADMIN_PASSWORD')) {
    throw new RuntimeException('APP_ADMIN_PASSWORD is required outside the SQLite development environment.');
}

$userStatement = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
$userStatement->execute(['email' => $email]);
if ((int) $userStatement->fetchColumn() === 0) {
    $insertUser = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)'
    );
    $insertUser->execute([
        'name' => 'Portfolio Admin',
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
}

if ((int) $pdo->query('SELECT COUNT(*) FROM items')->fetchColumn() === 0) {
    $insertItem = $pdo->prepare(
        'INSERT INTO items (name, category, unit, balance) VALUES (:name, :category, :unit, :balance)'
    );
    foreach ([
        ['Notebook A5', 'Stationery', 'pcs', 120],
        ['USB-C Cable', 'Assets', 'pcs', 35],
        ['Copy Paper A4', 'Stationery', 'box', 18],
    ] as [$name, $category, $unit, $balance]) {
        $insertItem->execute(compact('name', 'category', 'unit', 'balance'));
    }
}

fwrite(STDOUT, "Database initialized.\n");
if (!getenv('APP_ADMIN_PASSWORD')) {
    fwrite(STDOUT, "Development login: {$email} / {$password}\n");
}
