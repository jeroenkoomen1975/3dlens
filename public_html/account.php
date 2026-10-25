<?php
declare(strict_types=1);
require dirname(__DIR__) . '/3dlens-private/bootstrap.php';
require dirname(__DIR__) . '/3dlens-private/auth-view.php';
require dirname(__DIR__) . '/3dlens-private/mail.php';
require_login();
email_reset_schema(); // Additive upgrade, only available to the authenticated owner.
$userId = (int) $_SESSION['user_id'];
$read = db()->prepare('SELECT recovery_email FROM lens_account_settings WHERE user_id = ?');
$read->execute([$userId]);
$email = (string) ($read->fetchColumn() ?: '');
$settings = mail_settings();
$error = '';
$intro = 'Stel je eigen hersteladres in en gebruik een bestaande mailbox voor het versturen. Het mailboxwachtwoord blijft privé op deze server.';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (!reset_rate_allowed('account-confirm', $_SERVER['REMOTE_ADDR'] ?? '', 5)) throw new HttpError(429, 'Te veel pogingen. Wacht 15 minuten.');
    $password = post_string('current_password');
    $owner = db()->prepare('SELECT password_hash FROM lens_users WHERE id = ?'); $owner->execute([$userId]);
    if (strlen($password) > 72 || !password_verify($password, (string) $owner->fetchColumn())) {
        $error = 'Je huidige 3DLens-wachtwoord klopt niet. Er is niets gewijzigd.';
    } else {
        try {
            if (post_string('action') === 'test') {
                if ($email === '') throw new HttpError(422, 'Sla eerst je hersteladres en mailinstellingen op.');
                send_app_mail($email, '3DLens: test van je herstelmail', "Dit is de aangevraagde testmail van je eigen 3DLens-installatie.\nOntvang je dit bericht, dan kan de ingestelde mailserver e-mail versturen.\nJe wachtwoord is niet gewijzigd.\n");
                $intro = 'De mailserver heeft je testmail aangenomen. Controleer je herstelmailbox en de spammap.';
            } else {
                $newEmail = strtolower(trim(post_string('recovery_email')));
                if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL) || strlen($newEmail) > 254) throw new HttpError(422, 'Vul een geldig hersteladres in.');
                $newSettings = null;
                if (trim(post_string('smtp_host')) !== '') {
                    $port = (int) post_string('smtp_port');
                    $newSettings = mail_validate_settings(['host' => post_string('smtp_host'), 'port' => $port,
                        'encryption' => $port === 465 ? 'ssl' : 'tls', 'username' => post_string('smtp_username'),
                        'password' => post_string('smtp_password'), 'from_email' => post_string('from_email'), 'base_url' => post_string('base_url')], $settings);
                }
                db()->beginTransaction();
                $lock = db()->prepare('SELECT id FROM lens_users WHERE id = ? FOR UPDATE'); $lock->execute([$userId]);
                $save = db()->prepare('INSERT INTO lens_account_settings (user_id, recovery_email) VALUES (?, ?) ON DUPLICATE KEY UPDATE recovery_email = VALUES(recovery_email)');
                $save->execute([$userId, $newEmail]);
                $delete = db()->prepare('DELETE FROM lens_password_resets WHERE user_id = ?'); $delete->execute([$userId]);
                if ($newSettings !== null) mail_save_settings($newSettings);
                db()->commit();
                $email = $newEmail;
                $settings = mail_settings();
                $intro = mail_is_ready() ? 'Je instellingen zijn opgeslagen. Verstuur nu een testmail en controleer of deze aankomt.' : 'Je hersteladres is opgeslagen. Vul ook de bestaande verzendmailbox in om herstel via e-mail te activeren.';
            }
        } catch (Throwable $exception) {
            if (db()->inTransaction()) db()->rollBack();
            if (!$exception instanceof HttpError) throw $exception;
            http_response_code($exception->status);
            $error = $exception->getMessage();
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') { throw new HttpError(405, 'Gebruik het accountformulier.'); }
$base = $settings['base_url'] ?? app_base_url();
$fields = '<h2 style="font-size:18px">Jouw account</h2>
<label for="recovery_email">Hersteladres</label><input type="email" id="recovery_email" name="recovery_email" value="' . h($email) . '" maxlength="254" required>
<h2 style="font-size:18px;margin-top:30px">Bestaande verzendmailbox</h2>
<p>Gebruik je eigen mailbox. Bij Hostinger Email is de server smtp.hostinger.com, met SSL op poort 465. Andere providers kunnen andere instellingen gebruiken.</p>
<label for="base_url">Vaste HTTPS-URL van deze installatie</label><input type="url" id="base_url" name="base_url" value="' . h($base) . '">
<label for="smtp_host">SMTP-server</label><input id="smtp_host" name="smtp_host" value="' . h($settings['host'] ?? '') . '" placeholder="smtp.hostinger.com">
<label for="smtp_port">Poort en beveiliging</label><select id="smtp_port" name="smtp_port" style="width:100%;padding:12px;background:#0d0e0c;color:#e8e6df"><option value="465"' . (($settings['port'] ?? 465) === 465 ? ' selected' : '') . '>465 — SSL</option><option value="587"' . (($settings['port'] ?? 465) === 587 ? ' selected' : '') . '>587 — STARTTLS</option></select>
<label for="smtp_username">Volledig e-mailadres van de verzendmailbox</label><input type="email" id="smtp_username" name="smtp_username" value="' . h($settings['username'] ?? '') . '" autocomplete="off">
<label for="smtp_password">Wachtwoord van die mailbox</label><input type="password" id="smtp_password" name="smtp_password" autocomplete="new-password"><small>Leeg laten behoudt het eerder opgeslagen mailboxwachtwoord.</small>
<label for="from_email">Afzenderadres</label><input type="email" id="from_email" name="from_email" value="' . h($settings['from_email'] ?? '') . '"><small>Gebruik het adres dat jouw mailprovider toestaat; meestal hetzelfde als de verzendmailbox.</small>
<label for="current_password">Bevestig met je huidige 3DLens-wachtwoord</label><input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
<button type="submit" name="action" value="test" formnovalidate style="background:transparent;color:#d4ff3a;border:1px solid #d4ff3a">Testmail naar opgeslagen hersteladres</button>';
auth_page('Account en herstelmail', $intro, $error, $fields, 'Instellingen opslaan', '<p style="margin-top:20px"><a style="color:#d4ff3a" href="index.php">Terug naar mijn archief</a></p>');
