<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';
$auth->requireLogin();

$input = ['name' => '', 'category' => '', 'unit' => 'pcs'];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid($_POST['csrf_token'] ?? null);
    $input = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'category' => trim((string) ($_POST['category'] ?? '')),
        'unit' => trim((string) ($_POST['unit'] ?? '')),
    ];
    $errors = Validator::item($input);
    if (!$errors) {
        try {
            $inventory->create($input);
            flash('success', '商品を登録しました。');
            redirect('/dashboard.php');
        } catch (PDOException $exception) {
            $errors['name'] = '同じ商品名が登録されている可能性があります。';
        }
    }
}

$pageTitle = '商品登録';
require dirname(__DIR__) . '/partials/header.php';
?>
<section class="form-card">
    <p class="eyebrow">CREATE ITEM</p>
    <h1>商品登録</h1>
    <form method="post" class="form-stack">
        <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
        <label>商品名
            <input name="name" value="<?= e($input['name']) ?>" maxlength="150" required>
            <?php if (isset($errors['name'])): ?><span class="error-text"><?= e($errors['name']) ?></span><?php endif; ?>
        </label>
        <label>カテゴリ
            <input name="category" value="<?= e($input['category']) ?>" maxlength="100" required>
            <?php if (isset($errors['category'])): ?><span class="error-text"><?= e($errors['category']) ?></span><?php endif; ?>
        </label>
        <label>単位
            <select name="unit" required>
                <?php foreach (['pcs', 'box', 'kg', 'litre', 'meter'] as $unit): ?>
                    <option value="<?= e($unit) ?>" <?= $input['unit'] === $unit ? 'selected' : '' ?>><?= e($unit) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="button-row"><button class="primary-button" type="submit">登録する</button><a href="/dashboard.php">戻る</a></div>
    </form>
</section>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>

