<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/src/functions.php';
require_once $root . '/src/Database.php';
require_once $root . '/src/Auth.php';
require_once $root . '/src/Csrf.php';
require_once $root . '/src/Validator.php';
require_once $root . '/src/InventoryRepository.php';
require_once $root . '/src/StockService.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
}

$databasePath = tempnam(sys_get_temp_dir(), 'inventory-test-');
if ($databasePath === false) {
    throw new RuntimeException('Could not create temporary database.');
}

try {
    $pdo = Database::connect(['driver' => 'sqlite', 'path' => $databasePath]);
    $pdo->exec((string) file_get_contents($root . '/database/schema.sqlite.sql'));

    $insertUser = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)'
    );
    $insertUser->execute([
        'name' => 'Test Admin',
        'email' => 'admin@example.com',
        'password_hash' => password_hash('correct-password', PASSWORD_DEFAULT),
    ]);

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $auth = new Auth($pdo);
    expect(!$auth->attempt('admin@example.com', 'wrong-password'), 'Wrong password must fail.');
    expect($auth->attempt('ADMIN@example.com', 'correct-password'), 'Hashed login must succeed.');
    expect($auth->check(), 'Session must contain authenticated user.');

    $repository = new InventoryRepository($pdo);
    $service = new StockService($pdo, $repository);
    $itemId = $repository->create(['name' => 'Test Cable', 'category' => 'Assets', 'unit' => 'pcs']);
    $service->record($itemId, 'receive', 10, 'Initial stock');
    $service->record($itemId, 'issue', 4, 'Department use');
    expect((int) $repository->find($itemId)['balance'] === 6, 'Balance must be updated transactionally.');

    $history = $repository->movementHistory($itemId, 1, 20);
    expect($history['total'] === 2, 'Item history must contain receive and issue records.');
    expect(count($history['movements']) === 2, 'Item history page must return both records.');
    expect($history['movements'][0]['note'] === 'Department use', 'Newest movement note must be returned first.');

    try {
        $service->record($itemId, 'issue', 7, 'Over issue');
        throw new RuntimeException('FAIL: Over-issue must throw.');
    } catch (DomainException) {
        expect((int) $repository->find($itemId)['balance'] === 6, 'Failed transaction must roll back.');
    }

    $search = $repository->paginate('Test', 1, 20);
    expect($search['total'] === 1, 'Prefix search must find matching item.');
    expect(count($search['items']) === 1, 'Page must contain one row.');

    $errors = Validator::movement(['item_id' => $itemId, 'quantity' => 0]);
    expect(isset($errors['quantity']), 'Zero quantity must be rejected.');

    $_SESSION['csrf_token'] = str_repeat('a', 64);
    expect(Csrf::verify(str_repeat('a', 64)), 'Matching CSRF token must pass.');
    expect(!Csrf::verify(str_repeat('b', 64)), 'Mismatched CSRF token must fail.');

    fwrite(STDOUT, "SMOKE_TEST_PASS\n");
} finally {
    @unlink($databasePath);
}
