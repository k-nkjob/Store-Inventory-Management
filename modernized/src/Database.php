<?php

declare(strict_types=1);

final class Database
{
    public static function connect(array $config): PDO
    {
        $driver = $config['driver'] ?? 'sqlite';

        if ($driver === 'sqlite') {
            $pdo = new PDO('sqlite:' . $config['path']);
            $pdo->exec('PRAGMA foreign_keys = ON');
        } elseif ($driver === 'mysql') {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['name'],
                $config['charset'] ?? 'utf8mb4'
            );
            $pdo = new PDO($dsn, $config['user'], $config['password']);
        } else {
            throw new InvalidArgumentException('Unsupported database driver.');
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        return $pdo;
    }
}

