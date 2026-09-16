<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/src/Database.php';
require_once $root . '/src/InventoryRepository.php';

$databasePath = tempnam(sys_get_temp_dir(), 'inventory-perf-');
if ($databasePath === false) {
    throw new RuntimeException('Could not create temporary database.');
}

try {
    $pdo = Database::connect(['driver' => 'sqlite', 'path' => $databasePath]);
    $pdo->exec((string) file_get_contents($root . '/database/schema.sqlite.sql'));
    $pdo->beginTransaction();
    $insert = $pdo->prepare(
        'INSERT INTO items (name, category, unit, balance) VALUES (:name, :category, :unit, :balance)'
    );
    for ($i = 1; $i <= 10000; $i++) {
        $insert->execute([
            'name' => sprintf('Item %05d', $i),
            'category' => 'Category ' . ($i % 20),
            'unit' => 'pcs',
            'balance' => $i % 100,
        ]);
    }
    $pdo->commit();

    $legacyStart = hrtime(true);
    $allRows = $pdo->query('SELECT * FROM items')->fetchAll();
    $legacyMatches = array_values(array_filter(
        $allRows,
        static fn(array $row): bool => str_starts_with($row['name'], 'Item 099')
    ));
    $legacyMs = (hrtime(true) - $legacyStart) / 1_000_000;

    $repository = new InventoryRepository($pdo);
    $modernStart = hrtime(true);
    $modernResult = $repository->paginate('Item 099', 1, 20);
    $modernMs = (hrtime(true) - $modernStart) / 1_000_000;

    if (count($allRows) !== 10000 || count($modernResult['items']) > 20) {
        throw new RuntimeException('Performance fixture or pagination result is invalid.');
    }
    if (count($legacyMatches) !== $modernResult['total']) {
        throw new RuntimeException('Legacy and modernized search results differ.');
    }

    $planStatement = $pdo->prepare('EXPLAIN QUERY PLAN SELECT id FROM items WHERE name LIKE :prefix LIMIT 20');
    $planStatement->execute(['prefix' => 'Item 099%']);
    $plan = implode(' | ', array_column($planStatement->fetchAll(), 'detail'));

    fwrite(STDOUT, json_encode([
        'dataset_rows' => 10000,
        'matched_rows' => $modernResult['total'],
        'legacy_rows_loaded' => count($allRows),
        'modern_rows_loaded' => count($modernResult['items']),
        'legacy_elapsed_ms' => round($legacyMs, 3),
        'modern_elapsed_ms' => round($modernMs, 3),
        'query_plan' => $plan,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    fwrite(STDOUT, "PERFORMANCE_COMPARISON_PASS\n");
} finally {
    @unlink($databasePath);
}

