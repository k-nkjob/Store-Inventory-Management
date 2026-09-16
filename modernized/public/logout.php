<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

Csrf::requireValid($_POST['csrf_token'] ?? null);
$auth->logout();
redirect('/login.php');

