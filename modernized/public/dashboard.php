<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
$auth->requireLogin();

$query = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$result = $inventory->paginate($query, $page);
$movements = $inventory->recentMovements();

$visibleBalance = array_sum(array_map(
    static fn (array $item): int => (int) $item['balance'],
    $result['items']
));
$receiveCount = count(array_filter(
    $movements,
    static fn (array $movement): bool => $movement['movement_type'] === 'receive'
));
$issueCount = count($movements) - $receiveCount;

$pageTitle = '在庫一覧';
require __DIR__ . '/partials/header.php';
?>
<section class="dashboard-hero">
    <div class="dashboard-hero-copy">
        <p class="eyebrow">INVENTORY DATABASE</p>
        <h1>在庫一覧</h1>
        <p>商品・入出庫・在庫状況をひとつの画面で確認できます。</p>
    </div>
    <a class="primary-button hero-action" href="/items/create.php">＋ 商品登録</a>
</section>

<section class="summary-grid" aria-label="在庫サマリー">
    <article class="summary-card summary-blue">
        <span class="summary-icon">▦</span>
        <div><small>登録商品</small><strong><?= e($result['total']) ?></strong><span>items</span></div>
    </article>
    <article class="summary-card summary-cyan">
        <span class="summary-icon">▤</span>
        <div><small>表示中の在庫数</small><strong><?= e($visibleBalance) ?></strong><span>total units</span></div>
    </article>
    <article class="summary-card summary-green">
        <span class="summary-icon">↓</span>
        <div><small>最近の入庫</small><strong><?= e($receiveCount) ?></strong><span>records</span></div>
    </article>
    <article class="summary-card summary-orange">
        <span class="summary-icon">↑</span>
        <div><small>最近の出庫</small><strong><?= e($issueCount) ?></strong><span>records</span></div>
    </article>
</section>

<section class="panel inventory-panel">
    <div class="panel-heading">
        <div>
            <p class="panel-kicker">PRODUCTS</p>
            <h2>商品一覧</h2>
        </div>
        <span class="panel-count">全 <?= e($result['total']) ?> 件</span>
    </div>

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
                <td><span class="id-chip"><?= e($item['id']) ?></span></td>
                <td><strong><a class="item-link" href="/items/show.php?id=<?= e($item['id']) ?>"><?= e($item['name']) ?></a></strong></td>
                <td><span class="category-chip"><?= e($item['category']) ?></span></td>
                <td><strong class="balance-value"><?= e($item['balance']) ?></strong> <span class="unit-label"><?= e($item['unit']) ?></span></td>
                <td><?= e(substr((string) $item['created_at'], 0, 10)) ?></td>
                <td><div class="action-group">
                    <a href="/items/show.php?id=<?= e($item['id']) ?>">履歴</a>
                    <form action="/items/delete.php" method="post" onsubmit="return confirm('削除しますか？')">
                        <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
                        <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                        <button type="submit" class="danger-link">削除</button>
                    </form>
                </div></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$result['items']): ?><tr><td colspan="6" class="empty-state">該当する商品はありません。</td></tr><?php endif; ?>
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
</section>

<section class="panel movement-panel">
    <div class="panel-heading">
        <div>
            <p class="panel-kicker">RECENT ACTIVITY</p>
            <h2>最近の入出庫</h2>
        </div>
        <a class="panel-link" href="/stock/movement.php">入出庫を登録 →</a>
    </div>

    <div class="movement-grid">
        <?php foreach ($movements as $movement): ?>
            <article>
                <div class="movement-topline">
                    <span class="badge badge-<?= e($movement['movement_type']) ?>"><?= $movement['movement_type'] === 'receive' ? '入庫' : '出庫' ?></span>
                    <small><?= e($movement['created_at']) ?></small>
                </div>
                <strong><?= e($movement['item_name']) ?></strong>
                <span class="movement-quantity"><?= $movement['movement_type'] === 'receive' ? '+' : '-' ?><?= e($movement['quantity']) ?> <?= e($movement['unit']) ?></span>
            </article>
        <?php endforeach; ?>
        <?php if (!$movements): ?><p>入出庫履歴はまだありません。</p><?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
