<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';
$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

Csrf::requireValid($_POST['csrf_token'] ?? null);
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(422);
    exit('Invalid item id.');
}

try {
    flash($inventory->delete((int) $id) ? 'success' : 'error', '削除処理を実行しました。');
} catch (PDOException $exception) {
    flash('error', '入出庫履歴がある商品は削除できません。');
}
redirect('/dashboard.php');

