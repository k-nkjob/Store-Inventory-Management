<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
redirect($auth->check() ? '/dashboard.php' : '/login.php');

