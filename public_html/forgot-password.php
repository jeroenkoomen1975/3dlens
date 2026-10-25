<?php
declare(strict_types=1);
require dirname(__DIR__) . '/3dlens-private/bootstrap.php';
require dirname(__DIR__) . '/3dlens-private/auth-view.php';
require dirname(__DIR__) . '/3dlens-private/mail.php';
$intro = 'Vul het hersteladres van je 3DLens-account in. Je ontvangt een link waarmee je zelf een nieuw wachtwoord kiest.';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $job = email_reset_prepare(substr(post_string('email'), 0, 254), $_SERVER['REMOTE_ADDR'] ?? '');
    $intro = 'Als dit adres bij een account hoort en herstelmail is ingesteld, ontvang je een link. Controleer ook je spammap. De link is 30 minuten geldig.';
    if ($job !== null) {
        // Finish the generic response before SMTP where the hosting API supports
        // it. Never show mail errors, account existence or reset URLs publicly.
        register_shutdown_function(function () use ($job): void {
            session_write_close();
            if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
            elseif (function_exists('litespeed_finish_request')) litespeed_finish_request();
            email_reset_send($job);
        });
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') { throw new HttpError(405, 'Gebruik het herstelformulier.'); }
auth_page('Wachtwoord vergeten?', $intro, '',
    '<label for="email">Je hersteladres</label><input type="email" id="email" name="email" autocomplete="email" maxlength="254" required>',
    'Herstellink aanvragen', '<p style="margin-top:20px"><a style="color:#d4ff3a" href="login.php">Terug naar inloggen</a></p>');
