<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
$auth->requireLogin();

$query = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$result = $inventory->paginate($query, $page);
$movements = $inventory->recentMovements();
$pageTitle = '在庫一覧';
require __DIR__ . '/partials/header.php';
?>
<section class="page-heading">
    <div><p class="eyebrow">INVENTORY DATABASE</p><h1>在庫一覧</h1></div>
    <a class="primary-button" href="/items/create.php">＋ 商品登録</a>
</section>

<form method="get" class="search-form">
    <label class="sr-only" for="q">商品名またはカテゴリ</label>
    <input id="q" type="search" name="q" value="<?= e($query) ?>" placeholder="商品名・カテゴリを先頭から検索">
    <button type="submit">検索</button>
</form>

<div class="table-wrap">
<table>
    <thead><tr><th>ID</th><th>商品名</th><th>カテゴリ</th><th>在庫</th><th>登録日</th><th>操作</th></tr></thead>
    <tbody>
    <?php foreach ($result['items'] as $item): ?>
        <tr>
            <td><?= e($item['id']) ?></td>
            <td><strong><?= e($item['name']) ?></strong></td>
            <td><?= e($item['category']) ?></td>
            <td><?= e($item['balance']) ?> <?= e($item['unit']) ?></td>
            <td><?= e(substr((string) $item['created_at'], 0, 10)) ?></td>
            <td>
                <form action="/items/delete.php" method="post" onsubmit="return confirm('削除しますか？')">
                    <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
                    <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                    <button type="submit" class="danger-link">削除</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$result['items']): ?><tr><td colspan="6">該当する商品はありません。</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<?php if ($result['pages'] > 1): ?>
<nav class="pagination" aria-label="ページネーション">
    <?php for ($i = max(1, $page - 2); $i <= min($result['pages'], $page + 2); $i++): ?>
        <a class="<?= $i === $page ? 'active' : '' ?>" href="?q=<?= urlencode($query) ?>&page=<?= $i ?>"><?= $i ?></a>
    <?php endfor; ?>
</nav>
<?php endif; ?>

<section class="subsection">
    <h2>最近の入出庫</h2>
    <div class="movement-grid">
        <?php foreach ($movements as $movement): ?>
            <article>
                <span class="badge badge-<?= e($movement['movement_type']) ?>"><?= $movement['movement_type'] === 'receive' ? '入庫' : '出庫' ?></span>
                <strong><?= e($movement['item_name']) ?></strong>
                <span><?= e($movement['quantity']) ?> <?= e($movement['unit']) ?></span>
                <small><?= e($movement['created_at']) ?></small>
            </article>
        <?php endforeach; ?>
        <?php if (!$movements): ?><p>入出庫履歴はまだありません。</p><?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>

