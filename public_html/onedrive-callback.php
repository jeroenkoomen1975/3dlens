<?php
declare(strict_types=1);
require dirname(__DIR__) . '/3dlens-private/bootstrap.php';
require dirname(__DIR__) . '/3dlens-private/repository.php';
require_login();
require_method('GET');
try {
    write_lock();
    od_complete_connect();
    db()->commit();
} catch (Throwable $error) {
    if (db()->inTransaction()) db()->rollBack();
    throw $error;
}
// Strip the short-lived OAuth code from the address bar immediately.
header('Location: onedrive.php?connected=1', true, 303);
