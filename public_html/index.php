<?php
declare(strict_types=1);
require dirname(__DIR__) . '/3dlens-private/bootstrap.php';
require_login();
$csrf = csrf_token();
// Do not keep a session lock while the browser loads or parses large models.
session_write_close();
require dirname(__DIR__) . '/3dlens-private/archive.php';
