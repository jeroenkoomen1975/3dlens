<?php
declare(strict_types=1);
require dirname(__DIR__) . '/3dlens-private/bootstrap.php';
require dirname(__DIR__) . '/3dlens-private/auth-view.php';
require dirname(__DIR__) . '/3dlens-private/mail.php';
if (isset($_GET['selector']) || isset($_GET['token'])) {
    require_method('GET');
    $selector = is_string($_GET['selector'] ?? null) ? $_GET['selector'] : '';
    $token = is_string($_GET['token'] ?? null) ? $_GET['token'] : '';
    email_reset_record($selector, $token);
    $_SESSION['email_password_reset'] = ['selector' => $selector, 'token' => $token];
    header('Location: reset-password.php', true, 303);
    exit;
}
$pending = $_SESSION['email_password_reset'] ?? [];
$selector = $pending['selector'] ?? '';
$token = $pending['token'] ?? '';
email_reset_record($selector, $token);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (!reset_rate_allowed('reset-confirm', $_SERVER['REMOTE_ADDR'] ?? '', 10)) throw new HttpError(429, 'Te veel herstelpogingen. Wacht 15 minuten.');
    try {
        email_reset_complete($selector, $token, post_string('password'), post_string('password_confirmation'));
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['password_reset_done'] = true;
        header('Location: login.php', true, 303);
        exit;
    } catch (HttpError $exception) { http_response_code($exception->status); $error = $exception->getMessage(); }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') { throw new HttpError(405, 'Gebruik het herstelformulier.'); }
auth_page('Kies een nieuw wachtwoord', 'Je modellen en collecties blijven behouden. Log na het opslaan opnieuw in met je bestaande gebruikersnaam.', $error,
    '<label for="password">Nieuw wachtwoord (minimaal 14 tekens)</label><input type="password" id="password" name="password" autocomplete="new-password" minlength="14" required>
     <label for="password_confirmation">Herhaal nieuw wachtwoord</label><input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>',
    'Nieuw wachtwoord opslaan');
