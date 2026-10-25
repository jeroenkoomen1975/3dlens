<?php
declare(strict_types=1);
require dirname(__DIR__) . '/3dlens-private/bootstrap.php';
require dirname(__DIR__) . '/3dlens-private/auth-view.php';
require dirname(__DIR__) . '/3dlens-private/recovery.php';
$private = dirname(__DIR__) . '/3dlens-private';
$gatePath = $private . '/recovery.local.json';
recovery_gate($gatePath); // Disabled by default. Only a hosting owner can provision the private gate.
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $lock = fopen($private . '/recovery.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX)) throw new HttpError(503, 'Herstel is tijdelijk niet beschikbaar.');
    chmod($private . '/recovery.lock', 0600);
    try {
        $gate = recovery_gate($gatePath);
        recovery_validate($gate, trim(post_string('recovery_key')), post_string('password'), post_string('password_confirmation'));
        $hash = password_hash(post_string('password'), PASSWORD_DEFAULT);
        db()->beginTransaction();
        $owner = db()->query('SELECT id FROM lens_users WHERE id = 1 FOR UPDATE')->fetch();
        if (!$owner) throw new HttpError(409, 'Er is geen eigenaarsaccount om te herstellen.');
        // Consume before changing the database: an uncertain commit cannot reuse a reset token.
        if (!unlink($gatePath)) throw new HttpError(503, 'De eenmalige herstelsleutel kon niet worden afgesloten.');
        $update = db()->prepare('UPDATE lens_users SET password_hash = ? WHERE id = 1');
        $update->execute([$hash]);
        db()->commit();
        @unlink($private . '/recovery-key.txt');
        // Existing sessions are revoked by the password fingerprint in logged_in().
        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: login.php?password_changed=1', true, 303);
        exit;
    } catch (Throwable $exception) {
        if (db()->inTransaction()) db()->rollBack();
        if (!$exception instanceof HttpError) throw $exception;
        http_response_code($exception->status);
        $error = $exception->getMessage();
    } finally { flock($lock, LOCK_UN); fclose($lock); }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    throw new HttpError(405, 'Gebruik het herstelformulier.');
}
auth_page('Herstel je toegang', 'Kies zelf een nieuw wachtwoord voor je bestaande 3DLens-account. Je modellen en collecties blijven behouden. De herstelsleutel staat alleen in je ingelogde Hostinger-bestandsbeheer.', $error,
    '<label for="recovery_key">Eenmalige herstelsleutel</label><input type="password" id="recovery_key" name="recovery_key" autocomplete="off" minlength="64" maxlength="64" required>
     <label for="password">Nieuw wachtwoord (minimaal 14 tekens)</label><input type="password" id="password" name="password" autocomplete="new-password" minlength="14" required>
     <label for="password_confirmation">Herhaal nieuw wachtwoord</label><input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>',
    'Nieuw wachtwoord opslaan');
