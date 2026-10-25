<?php
declare(strict_types=1);
require dirname(__DIR__) . '/3dlens-private/bootstrap.php';
require dirname(__DIR__) . '/3dlens-private/repository.php';
require_login();
require_method('POST');
check_csrf();
$mode = post_string('mode');
if ($mode === 'cleanup' && post_string('confirm') !== 'remove-verified-local-copies') {
    throw new HttpError(422, 'Bevestig eerst het opruimen van de gecontroleerde lokale kopieën.');
}
session_write_close();
json_response(model_migration_step($mode));
