<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';
$auth->requireLogin();

$errors = [];
$requestedItemId = filter_var($_GET['item_id'] ?? null, FILTER_VALIDATE_INT);
$input = [
    'item_id' => $requestedItemId ? (string) $requestedItemId : '',
    'type' => 'receive',
    'quantity' => '',
    'note' => '',
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid($_POST['csrf_token'] ?? null);
    $input = [
        'item_id' => (string) ($_POST['item_id'] ?? ''),
        'type' => (string) ($_POST['type'] ?? ''),
        'quantity' => (string) ($_POST['quantity'] ?? ''),
        'note' => trim((string) ($_POST['note'] ?? '')),
    ];
    $errors = Validator::movement($input);
    if (!in_array($input['type'], ['receive', 'issue'], true)) {
        $errors['type'] = '処理区分が不正です。';
    }
    if (!$errors) {
        try {
            $stockService->record((int) $input['item_id'], $input['type'], (int) $input['quantity'], $input['note']);
            flash('success', $input['type'] === 'receive' ? '入庫を記録しました。' : '出庫を記録しました。');
            redirect('/dashboard.php');
        } catch (DomainException | RuntimeException $exception) {
            $errors['quantity'] = $exception->getMessage();
        }
    }
}

$items = $inventory->allForSelect();
$pageTitle = '入出庫登録';
require dirname(__DIR__) . '/partials/header.php';
?>
<section class="form-card">
    <p class="eyebrow">STOCK MOVEMENT</p>
    <h1>入出庫登録</h1>
    <form method="post" class="form-stack">
        <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
        <label>商品
            <select name="item_id" required>
                <option value="">選択してください</option>
                <?php foreach ($items as $item): ?>
                    <option value="<?= e($item['id']) ?>" <?= $input['item_id'] === (string) $item['id'] ? 'selected' : '' ?>>
                        <?= e($item['name']) ?>（現在 <?= e($item['balance']) ?> <?= e($item['unit']) ?>）
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['item_id'])): ?><span class="error-text"><?= e($errors['item_id']) ?></span><?php endif; ?>
        </label>
        <label>処理
            <select name="type"><option value="receive" <?= $input['type'] === 'receive' ? 'selected' : '' ?>>入庫</option><option value="issue" <?= $input['type'] === 'issue' ? 'selected' : '' ?>>出庫</option></select>
        </label>
        <label>数量
            <input type="number" name="quantity" min="1" max="1000000" value="<?= e($input['quantity']) ?>" required>
            <?php if (isset($errors['quantity'])): ?><span class="error-text"><?= e($errors['quantity']) ?></span><?php endif; ?>
        </label>
        <label>備考<input name="note" maxlength="255" value="<?= e($input['note']) ?>"></label>
        <div class="button-row"><button class="primary-button" type="submit">記録する</button><a href="/dashboard.php">戻る</a></div>
    </form>
</section>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
