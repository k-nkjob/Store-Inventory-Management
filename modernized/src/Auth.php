<?php

declare(strict_types=1);

final class Auth
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function attempt(string $email, string $password): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, email, password_hash FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => mb_strtolower(trim($email))]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
        ];
        return true;
    }

    public function check(): bool
    {
        return isset($_SESSION['user']['id']);
    }

    public function requireLogin(): void
    {
        if (!$this->check()) {
            redirect('/login.php');
        }
    }

    public function user(): ?array
    {
        return $this->check() ? $_SESSION['user'] : null;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], '', $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}

