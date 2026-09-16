<?php
/** @var array $config */
/** @var Auth $auth */
$pageTitle = $pageTitle ?? $config['app']['name'];
$flashMessage = consumeFlash();
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e($config['app']['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="/dashboard.php">Modern Inventory</a>
    <?php if ($auth->check()): ?>
        <nav>
            <a href="/dashboard.php">在庫一覧</a>
            <a href="/items/create.php">商品登録</a>
            <a href="/stock/movement.php">入出庫</a>
            <form action="/logout.php" method="post" class="inline-form">
                <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
                <button type="submit" class="link-button">ログアウト</button>
            </form>
        </nav>
    <?php endif; ?>
</header>
<main class="container">
<?php if ($flashMessage): ?>
    <div class="flash flash-<?= e($flashMessage['type']) ?>"><?= e($flashMessage['message']) ?></div>
<?php endif; ?>

