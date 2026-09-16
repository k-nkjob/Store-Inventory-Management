<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';
$auth->requireLogin();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    exit('Item not found.');
}

$item = $inventory->find((int) $id);
if (!$item) {
    http_response_code(404);
    exit('Item not found.');
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$history = $inventory->movementHistory((int) $id, $page);
$pageTitle = $item['name'] . 'の入出庫履歴';
require dirname(__DIR__) . '/partials/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">ITEM LEDGER</p>
        <h1><?= e($item['name']) ?></h1>
        <p><?= e($item['category']) ?> ／ 現在庫 <strong><?= e($item['balance']) ?> <?= e($item['unit']) ?></strong></p>
    </div>
    <a class="primary-button" href="/stock/movement.php?item_id=<?= e($item['id']) ?>">＋ 入出庫登録</a>
</section>

<section class="subsection history-section">
    <div class="section-heading">
        <h2>入出庫履歴</h2>
        <span>全 <?= e($history['total']) ?> 件</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>日時</th><th>区分</th><th>数量</th><th>備考</th></tr></thead>
            <tbody>
            <?php foreach ($history['movements'] as $movement): ?>
                <tr>
                    <td><?= e($movement['created_at']) ?></td>
                    <td><span class="badge badge-<?= e($movement['movement_type']) ?>"><?= $movement['movement_type'] === 'receive' ? '入庫' : '出庫' ?></span></td>
                    <td><?= $movement['movement_type'] === 'receive' ? '+' : '-' ?><?= e($movement['quantity']) ?> <?= e($item['unit']) ?></td>
                    <td><?= $movement['note'] !== '' ? e($movement['note']) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$history['movements']): ?><tr><td colspan="4">この商品の入出庫履歴はありません。</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($history['pages'] > 1): ?>
        <nav class="pagination" aria-label="履歴ページネーション">
            <?php for ($i = max(1, $page - 2); $i <= min($history['pages'], $page + 2); $i++): ?>
                <a class="<?= $i === $page ? 'active' : '' ?>" href="?id=<?= e($item['id']) ?>&page=<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>

    <div class="button-row"><a href="/dashboard.php">← 在庫一覧へ戻る</a></div>
</section>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
