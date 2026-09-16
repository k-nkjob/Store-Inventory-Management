<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

if ($auth->check()) {
    redirect('/dashboard.php');
}

$error = null;
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid($_POST['csrf_token'] ?? null);
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($auth->attempt($email, $password)) {
        redirect('/dashboard.php');
    }
    $error = 'メールアドレスまたはパスワードが正しくありません。';
}

$pageTitle = 'ログイン';
require __DIR__ . '/partials/header.php';
?>
<section class="auth-card">
    <p class="eyebrow">LEGACY MODERNIZATION</p>
    <h1>在庫管理ログイン</h1>
    <p>Prepared Statementとハッシュ化パスワードを使用した改修版です。</p>
    <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form-stack">
        <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
        <label>メールアドレス
            <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="username">
        </label>
        <label>パスワード
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button type="submit" class="primary-button">ログイン</button>
    </form>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>

