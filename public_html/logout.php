<?php
declare(strict_types=1);
require dirname(__DIR__) . '/3dlens-private/bootstrap.php';
require_method('POST');
check_csrf();
$_SESSION = [];
$cookie = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires' => time() - 3600, 'path' => $cookie['path'],
    'secure' => $cookie['secure'], 'httponly' => true, 'samesite' => 'Lax',
]);
session_destroy();
header('Location: login.php', true, 303);
