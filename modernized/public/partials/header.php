<?php
/** @var array $config */
/** @var Auth $auth */
$pageTitle = $pageTitle ?? $config['app']['name'];
$flashMessage = consumeFlash();
$isLoggedIn = $auth->check();
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e($config['app']['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/showcase-v4.css?v=1">
</head>
<body class="<?= $isLoggedIn ? 'app-authenticated' : 'app-guest' ?>">
<?php if ($isLoggedIn): ?>
<div class="app-layout">
    <aside class="app-sidebar">
        <a class="sidebar-brand" href="/dashboard.php">
            <span class="sidebar-brand-mark">MI</span>
            <span class="sidebar-brand-copy">
                <strong>Modern Inventory</strong>
                <small>在庫管理システム</small>
            </span>
        </a>

        <nav class="sidebar-nav" aria-label="管理メニュー">
            <p class="sidebar-section-label">MAIN</p>
            <a href="/dashboard.php">
                <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 13h6V4H4v9Zm0 7h6v-5H4v5Zm10 0h6V11h-6v9Zm0-16v5h6V4h-6Z"/></svg></span>
                <span>ダッシュボード</span>
            </a>
            <a href="/dashboard.php">
                <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 5h16v3H4V5Zm0 5h16v4H4v-4Zm0 6h16v3H4v-3Z"/></svg></span>
                <span>商品一覧</span>
            </a>
            <p class="sidebar-section-label management-label">MANAGEMENT</p>
            <a href="/items/create.php">
                <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M11 5h2v6h6v2h-6v6h-2v-6H5v-2h6V5Z"/></svg></span>
                <span>商品登録</span>
            </a>
            <a href="/stock/movement.php">
                <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m7 7 3-3 3 3h-2v5H9V7H7Zm10 10-3 3-3-3h2v-5h2v5h2Z"/></svg></span>
                <span>入出庫登録</span>
            </a>
        </nav>

        <div class="sidebar-note">
            <span class="sidebar-note-icon">✓</span>
            <div><strong>SECURE</strong><small>CSRF / Auth / PDO</small></div>
        </div>

        <form action="/logout.php" method="post" class="sidebar-logout">
            <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
            <button type="submit">ログアウト</button>
        </form>
    </aside>

    <div class="app-main">
        <header class="app-topbar">
            <div>
                <span class="topbar-kicker">INVENTORY MANAGEMENT</span>
                <strong><?= e($pageTitle) ?></strong>
            </div>
            <div class="topbar-user"><span class="user-dot"></span>管理者</div>
        </header>
        <main class="container">
<?php else: ?>
<header class="guest-header">
    <a class="brand" href="/dashboard.php">Modern Inventory</a>
</header>
<main class="container guest-container">
<?php endif; ?>

<?php if ($flashMessage): ?>
    <div class="flash flash-<?= e($flashMessage['type']) ?>"><?= e($flashMessage['message']) ?></div>
<?php endif; ?>
