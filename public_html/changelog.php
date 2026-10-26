<?php
declare(strict_types=1);
require dirname(__DIR__) . '/3dlens-private/bootstrap.php';
require_login();
require dirname(__DIR__) . '/3dlens-private/release.php';
session_write_close();
require dirname(__DIR__) . '/3dlens-private/changelog-view.php';
