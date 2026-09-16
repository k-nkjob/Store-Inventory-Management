<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$error = null;
$completed = false;

try {
    $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($userCount > 0) {
        http_response_code(410);
        exit('Initial setup is already complete.');
    }
} catch (Throwable) {
    http_response_code(500);
    exit('Database connection or schema setup is incomplete.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid($_POST['csrf_token'] ?? null);

    $configuredKey = (string) ($config['install']['key'] ?? '');
    $submittedKey = (string) ($_POST['install_key'] ?? '');
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if ($configuredKey === '' || str_starts_with($configuredKey, 'CHANGE_ME')) {
        $error = 'config.phpのインストールキーを変更してください。';
    } elseif (!hash_equals($configuredKey, $submittedKey)) {
        $error = 'インストールキーが正しくありません。';
    } elseif ($name === '' || mb_strlen($name) > 100) {
        $error = '管理者名を1〜100文字で入力してください。';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '有効なメールアドレスを入力してください。';
    } elseif (mb_strlen($password) < 12) {
        $error = 'パスワードは12文字以上で入力してください。';
    } else {
        $statement = $pdo->prepare(
            'INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)'
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        if (isset($_POST['sample_data'])) {
            foreach ([
                ['Notebook A5', 'Stationery', 'pcs', 120],
                ['USB-C Cable', 'Assets', 'pcs', 35],
                ['Copy Paper A4', 'Stationery', 'box', 18],
            ] as [$itemName, $category, $unit, $balance]) {
                $itemStatement = $pdo->prepare(
                    'INSERT INTO items (name, category, unit, balance) VALUES (:name, :category, :unit, :balance)'
                );
                $itemStatement->execute([
                    'name' => $itemName,
                    'category' => $category,
                    'unit' => $unit,
                    'balance' => $balance,
                ]);
            }
        }

        $completed = true;
    }
}

$pageTitle = '初期セットアップ';
require __DIR__ . '/partials/header.php';
?>
<section class="auth-card">
    <p class="eyebrow">ONE-TIME SETUP</p>
    <h1>管理者アカウント作成</h1>
    <?php if ($completed): ?>
        <div class="flash flash-success">管理者アカウントを作成しました。この画面は自動的に無効になりました。</div>
        <a class="primary-button" href="/login.php">ログイン画面へ</a>
    <?php else: ?>
        <p>公開環境で最初の管理者を作成します。登録後は再実行できません。</p>
        <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="form-stack">
            <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
            <label>インストールキー<input type="password" name="install_key" required autocomplete="off"></label>
            <label>管理者名<input type="text" name="name" maxlength="100" required></label>
            <label>ログイン用メールアドレス<input type="email" name="email" required autocomplete="username"></label>
            <label>ログイン用パスワード<input type="password" name="password" minlength="12" required autocomplete="new-password"></label>
            <label><span><input type="checkbox" name="sample_data" value="1" checked> サンプル商品を登録する</span></label>
            <button class="primary-button" type="submit">管理者を作成する</button>
        </form>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>

