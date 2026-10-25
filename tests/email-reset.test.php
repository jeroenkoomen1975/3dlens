<?php
declare(strict_types=1);
// Isolated SQLite logic test; FOR UPDATE is omitted by this test adapter only.
define('THREEDLENS_BOOTSTRAPPED', true);
final class HttpError extends RuntimeException {
    public function __construct(public readonly int $status, string $message) { parent::__construct($message); }
}
class ResetTestDatabase extends PDO {
    public function prepare(string $query, array $options = []): PDOStatement|false {
        return parent::prepare(str_replace(' FOR UPDATE', '', $query), $options);
    }
}
$fixture = sys_get_temp_dir() . '/3dlens-email-test-' . bin2hex(random_bytes(8));
mkdir($fixture, 0700);
$database = new ResetTestDatabase('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
function db(): PDO { global $database; return $database; }
function private_directory(mixed $path): string { global $fixture; return $fixture; }
function valid_app_url(mixed $url): string {
    if (!is_string($url) || !str_starts_with($url, 'https://')) throw new HttpError(422, 'Bad URL');
    return rtrim($url, '/');
}
function expect(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
    echo 'PASS: ' . $message . PHP_EOL;
}
function rejects(callable $action, int $status, string $message): void {
    try { $action(); } catch (HttpError $error) { expect($error->status === $status, $message); return; }
    throw new RuntimeException('Expected rejection: ' . $message);
}
require dirname(__DIR__) . '/3dlens-private/mail.php';
try {
    db()->exec('CREATE TABLE lens_users (id INTEGER PRIMARY KEY, username TEXT, password_hash TEXT);
        CREATE TABLE lens_account_settings (user_id INTEGER PRIMARY KEY, recovery_email TEXT);
        CREATE TABLE lens_password_resets (selector TEXT PRIMARY KEY, user_id INTEGER, token_hash TEXT, password_fingerprint TEXT, expires_at INTEGER);
        CREATE TABLE lens_login_attempts (bucket TEXT PRIMARY KEY, attempts INTEGER, window_start INTEGER);
        CREATE TABLE lens_files (id TEXT PRIMARY KEY, content TEXT);');
    $oldPassword = 'old-private-test-password';
    $oldHash = password_hash($oldPassword, PASSWORD_DEFAULT);
    $insert = db()->prepare('INSERT INTO lens_users VALUES (1, ?, ?)'); $insert->execute(['owner', $oldHash]);
    db()->exec("INSERT INTO lens_account_settings VALUES (1, 'owner@example.com'); INSERT INTO lens_files VALUES ('model-1', 'unchanged-model');");
    expect(!mail_is_ready(), 'Recovery mail stays disabled until a mailbox is configured');
    $settings = mail_validate_settings(['host' => 'smtp.example.com', 'port' => 465, 'encryption' => 'ssl', 'username' => 'sender@example.com', 'password' => 'fake-test-mail-password', 'from_email' => 'sender@example.com', 'base_url' => 'https://archive.example.com'], []);
    mail_save_settings($settings);
    expect(mail_is_ready(), 'Valid private SMTP settings enable recovery mail');
    $blank = $settings; $blank['password'] = '';
    expect(mail_validate_settings($blank, $settings)['password'] === $settings['password'], 'A blank SMTP password preserves the existing credential');
    $blank['username'] = 'other@example.com';
    rejects(fn() => mail_validate_settings($blank, $settings), 422, 'A different SMTP mailbox cannot inherit the old password');
    $bad = $settings; $bad['port'] = 25;
    rejects(fn() => mail_validate_settings($bad, []), 422, 'Unencrypted SMTP is rejected');
    expect(email_reset_prepare('unknown@example.com', 'test-ip-a') === null, 'Unknown accounts do not generate mail jobs');
    expect((int) db()->query('SELECT COUNT(*) FROM lens_password_resets')->fetchColumn() === 0, 'Unknown addresses do not create reset tokens');
    $job = email_reset_prepare('OWNER@example.com', 'test-ip-a');
    expect($job['recipient'] === 'owner@example.com' && str_starts_with($job['url'], 'https://archive.example.com/reset-password.php?'), 'Recovery uses the saved account address and configured canonical URL');
    parse_str(parse_url($job['url'], PHP_URL_QUERY), $link);
    $record = db()->query('SELECT * FROM lens_password_resets')->fetch();
    expect($record['token_hash'] === hash('sha256', $link['token']) && !str_contains(json_encode($record), $link['token']), 'The database stores only a hash of the email token');
    email_reset_record($link['selector'], $link['token']);
    expect((int) db()->query('SELECT COUNT(*) FROM lens_password_resets')->fetchColumn() === 1, 'Opening a link does not consume it, including email link scanners');
    rejects(fn() => email_reset_complete($link['selector'], str_repeat('0', 64), 'new-private-test-password', 'new-private-test-password'), 400, 'Wrong tokens cannot change the password');
    rejects(fn() => email_reset_complete($link['selector'], $link['token'], 'short', 'short'), 422, 'Weak reset passwords are rejected without consuming the token');
    rejects(fn() => email_reset_complete($link['selector'], $link['token'], 'new-private-test-password', 'different-private-test-password'), 422, 'Password confirmation is required');
    email_reset_complete($link['selector'], $link['token'], 'new-private-test-password', 'new-private-test-password');
    $newHash = db()->query('SELECT password_hash FROM lens_users')->fetchColumn();
    expect(password_verify('new-private-test-password', $newHash) && !password_verify($oldPassword, $newHash), 'A successful reset changes the existing account password');
    expect(hash('sha256', $newHash) !== hash('sha256', $oldHash), 'Old authenticated session fingerprints no longer match');
    expect(db()->query('SELECT content FROM lens_files')->fetchColumn() === 'unchanged-model', 'Password reset preserves the archive');
    rejects(fn() => email_reset_record($link['selector'], $link['token']), 400, 'Used links cannot be reused');
    $job = email_reset_prepare('owner@example.com', 'test-ip-b');
    parse_str(parse_url($job['url'], PHP_URL_QUERY), $link);
    db()->exec('UPDATE lens_password_resets SET expires_at = 1');
    rejects(fn() => email_reset_record($link['selector'], $link['token']), 400, 'Expired email links are rejected');
    expect(reset_rate_allowed('fixture', 'same-ip', 1), 'The first rate-limited request is allowed');
    expect(!reset_rate_allowed('fixture', 'same-ip', 1), 'The next request is limited using shared database state');
    expect(!reset_rate_allowed('fixture', 'same-ip', 1), 'A rejected request does not reopen the limit');
    $third = email_reset_prepare('owner@example.com', 'test-ip-c');
    expect($third !== null && email_reset_prepare('owner@example.com', 'test-ip-d') === null, 'Address limits apply across different IPs and browser sessions');
} finally {
    foreach (new FilesystemIterator($fixture) as $entry) unlink($entry->getPathname());
    rmdir($fixture);
}
