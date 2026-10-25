<?php
declare(strict_types=1);
if (!defined('THREEDLENS_BOOTSTRAPPED')) { http_response_code(404); exit; }

function mail_settings(): array
{
    $path = private_directory(__DIR__ . '/connections') . '/mail-settings.json';
    if (!is_file($path)) return [];
    $data = json_decode((string) file_get_contents($path), true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($data)) throw new HttpError(503, 'De mailinstellingen zijn niet leesbaar.');
    return $data;
}

function mail_validate_settings(array $input, array $old): array
{
    $host = trim($input['host'] ?? '');
    $user = trim($input['username'] ?? '');
    $from = trim($input['from_email'] ?? '');
    $port = (int) ($input['port'] ?? 465);
    $encryption = $input['encryption'] ?? 'ssl';
    $password = ($input['password'] ?? '') !== '' ? $input['password'] : ($old['password'] ?? '');
    if (!preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9.-]{0,252}\z/', $host) ||
        !filter_var($from, FILTER_VALIDATE_EMAIL) || !filter_var($user, FILTER_VALIDATE_EMAIL) ||
        !in_array($port, [465, 587], true) || !in_array($encryption, ['ssl', 'tls'], true) ||
        ($port === 465 && $encryption !== 'ssl') || ($port === 587 && $encryption !== 'tls') ||
        !is_string($password) || $password === '' || strlen($password) > 4096 || preg_match('/[\x00\r\n]/', $password)) {
        throw new HttpError(422, 'Controleer SMTP-server, mailbox, afzender en wachtwoord. Gebruik SSL op 465 of STARTTLS op 587.');
    }
    if (($old['username'] ?? $user) !== $user && ($input['password'] ?? '') === '') {
        throw new HttpError(422, 'Vul bij een andere mailbox ook het bijbehorende SMTP-wachtwoord in.');
    }
    return ['host' => $host, 'port' => $port, 'encryption' => $encryption, 'username' => $user,
        'password' => $password, 'from_email' => $from, 'base_url' => valid_app_url($input['base_url'] ?? '')];
}

function mail_save_settings(array $settings): void
{
    $path = private_directory(__DIR__ . '/connections') . '/mail-settings.json';
    $temporary = $path . '.' . bin2hex(random_bytes(8)) . '.tmp';
    $json = json_encode($settings, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    $handle = fopen($temporary, 'xb');
    if (!$handle) throw new HttpError(503, 'De mailinstellingen konden niet worden opgeslagen.');
    try {
        chmod($temporary, 0600);
        if (fwrite($handle, $json) !== strlen($json) || !fflush($handle)) throw new HttpError(503, 'De mailinstellingen konden niet worden opgeslagen.');
    } finally { fclose($handle); }
    if (!rename($temporary, $path)) { @unlink($temporary); throw new HttpError(503, 'De mailinstellingen konden niet worden opgeslagen.'); }
}

function mail_is_ready(): bool
{
    $settings = mail_settings();
    return !empty($settings['host']) && !empty($settings['password']) && !empty($settings['base_url']);
}

function send_app_mail(string $recipient, string $subject, string $body): void
{
    $settings = mail_settings();
    if (!mail_is_ready()) throw new HttpError(503, 'Stel eerst een eigen mailbox in voor herstelmails.');
    require_once __DIR__ . '/vendor/phpmailer/Exception.php';
    require_once __DIR__ . '/vendor/phpmailer/SMTP.php';
    require_once __DIR__ . '/vendor/phpmailer/PHPMailer.php';
    try {
        $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = $settings['host'];
        $mailer->Port = $settings['port'];
        $mailer->SMTPSecure = $settings['encryption'];
        $mailer->SMTPAuth = true;
        $mailer->Username = $settings['username'];
        $mailer->Password = $settings['password'];
        $mailer->Timeout = 10;
        $mailer->Timelimit = 20;
        $mailer->SMTPDebug = 0;
        $mailer->CharSet = 'UTF-8';
        $mailer->setFrom($settings['from_email'], '3DLens');
        $mailer->addAddress($recipient);
        $mailer->Subject = $subject;
        $mailer->Body = $body;
        $mailer->isHTML(false);
        $mailer->send();
    } catch (Throwable $error) {
        // PHPMailer errors may contain remote details. Never display or log them.
        error_log('3DLens: mail delivery failed.');
        throw new HttpError(503, 'De mailserver kon de e-mail niet aannemen. Controleer de SMTP-instellingen en probeer opnieuw.');
    }
}

function email_reset_schema(): void
{
    $sql = file_get_contents(__DIR__ . '/migrations/002-email-reset.sql');
    foreach (explode(';', $sql) as $statement) if (trim($statement) !== '') db()->exec($statement);
}

function email_reset_tables_exist(): bool
{
    try { db()->query('SELECT user_id FROM lens_account_settings LIMIT 1'); return true; }
    catch (PDOException $error) { if (($error->errorInfo[1] ?? 0) === 1146) return false; throw $error; }
}

/** Shared database limits, not bypassed by opening another browser session. */
function reset_rate_allowed(string $scope, string $value, int $limit, int $window = 900): bool
{
    $bucket = hash('sha256', $scope . ':' . $value);
    try {
        db()->beginTransaction();
        $read = db()->prepare('SELECT attempts, window_start FROM lens_login_attempts WHERE bucket = ? FOR UPDATE');
        $read->execute([$bucket]);
        $old = $read->fetch();
        if ($old && (int) $old['window_start'] >= time() - $window) {
            $allowed = (int) $old['attempts'] < $limit;
            if ($allowed) { $update = db()->prepare('UPDATE lens_login_attempts SET attempts = attempts + 1 WHERE bucket = ?'); $update->execute([$bucket]); }
        } else {
            $allowed = true;
            if ($old) { $write = db()->prepare('UPDATE lens_login_attempts SET attempts = 1, window_start = ? WHERE bucket = ?'); $write->execute([time(), $bucket]); }
            else { $write = db()->prepare('INSERT INTO lens_login_attempts (bucket, attempts, window_start) VALUES (?, 1, ?)'); $write->execute([$bucket, time()]); }
        }
        db()->commit();
        return $allowed;
    } catch (PDOException $error) {
        if (db()->inTransaction()) db()->rollBack();
        if (($error->errorInfo[1] ?? 0) === 1062) return false;
        throw $error;
    }
}

function email_reset_prepare(string $email, string $ip): ?array
{
    $email = strtolower(trim($email));
    $ipAllowed = reset_rate_allowed('reset-ip', $ip, 5);
    $emailAllowed = reset_rate_allowed('reset-address', $email, 3);
    if (!$ipAllowed || !$emailAllowed || !filter_var($email, FILTER_VALIDATE_EMAIL) || !mail_is_ready() || !email_reset_tables_exist()) return null;
    $read = db()->prepare('SELECT u.id, u.password_hash FROM lens_users u JOIN lens_account_settings a ON a.user_id = u.id WHERE a.recovery_email = ?');
    $read->execute([$email]);
    $user = $read->fetch();
    if (!$user) return null;
    $selector = bin2hex(random_bytes(16));
    $token = bin2hex(random_bytes(32));
    try {
        db()->beginTransaction();
        $lock = db()->prepare('SELECT id FROM lens_users WHERE id = ? FOR UPDATE'); $lock->execute([$user['id']]);
        $delete = db()->prepare('DELETE FROM lens_password_resets WHERE user_id = ?'); $delete->execute([$user['id']]);
        $insert = db()->prepare('INSERT INTO lens_password_resets (selector, user_id, token_hash, password_fingerprint, expires_at) VALUES (?, ?, ?, ?, ?)');
        $insert->execute([$selector, $user['id'], hash('sha256', $token), hash('sha256', $user['password_hash']), time() + 1800]);
        db()->commit();
    } catch (Throwable $error) { if (db()->inTransaction()) db()->rollBack(); throw $error; }
    $base = valid_app_url(mail_settings()['base_url']);
    return ['recipient' => $email, 'selector' => $selector, 'url' => $base . '/reset-password.php?selector=' . $selector . '&token=' . $token];
}

function email_reset_send(array $job): void
{
    try {
        send_app_mail($job['recipient'], 'Herstel je 3DLens-wachtwoord',
            "Je hebt een nieuw wachtwoord voor je eigen 3DLens-installatie aangevraagd.\n\nOpen deze link:\n" . $job['url'] .
            "\n\nDe link werkt eenmaal en vervalt na 30 minuten. Je modellen en collecties blijven behouden.\nHeb je dit niet aangevraagd? Negeer deze e-mail; je wachtwoord is niet veranderd.\n");
    } catch (Throwable $error) {
        // Do not let delivery failures disclose whether an address is registered.
        $delete = db()->prepare('DELETE FROM lens_password_resets WHERE selector = ?'); $delete->execute([$job['selector']]);
        error_log('3DLens: password reset email could not be delivered.');
    }
}

function email_reset_record(string $selector, string $token, bool $lock = false): array
{
    if (!preg_match('/\A[a-f0-9]{32}\z/', $selector) || !preg_match('/\A[a-f0-9]{64}\z/', $token)) throw new HttpError(400, 'Deze herstellink is ongeldig of verlopen. Vraag een nieuwe aan.');
    $read = db()->prepare('SELECT r.*, u.password_hash FROM lens_password_resets r JOIN lens_users u ON u.id = r.user_id WHERE r.selector = ?' . ($lock ? ' FOR UPDATE' : ''));
    $read->execute([$selector]);
    $row = $read->fetch();
    if (!$row || (int) $row['expires_at'] <= time() || !hash_equals($row['token_hash'], hash('sha256', $token)) ||
        !hash_equals($row['password_fingerprint'], hash('sha256', $row['password_hash']))) throw new HttpError(400, 'Deze herstellink is ongeldig of verlopen. Vraag een nieuwe aan.');
    return $row;
}

function email_reset_complete(string $selector, string $token, string $password, string $confirmation): void
{
    if (strlen($password) < 14 || strlen($password) > 72 || str_contains($password, "\0")) throw new HttpError(422, 'Kies een uniek wachtwoord van minimaal 14 en maximaal 72 bytes.');
    if (!hash_equals($password, $confirmation)) throw new HttpError(422, 'De twee wachtwoorden zijn niet gelijk.');
    $hash = password_hash($password, PASSWORD_DEFAULT);
    try {
        db()->beginTransaction();
        $row = email_reset_record($selector, $token, true);
        $update = db()->prepare('UPDATE lens_users SET password_hash = ? WHERE id = ?'); $update->execute([$hash, $row['user_id']]);
        $delete = db()->prepare('DELETE FROM lens_password_resets WHERE user_id = ?'); $delete->execute([$row['user_id']]);
        db()->commit();
    } catch (Throwable $error) { if (db()->inTransaction()) db()->rollBack(); throw $error; }
}
