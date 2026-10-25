<?php
declare(strict_types=1);

// Filesystem and failure-path tests. No Microsoft requests or real credentials.
define('THREEDLENS_BOOTSTRAPPED', true);
final class HttpError extends RuntimeException {
    public function __construct(public readonly int $status, string $message) { parent::__construct($message); }
}
$fixture = sys_get_temp_dir() . '/3dlens-onedrive-test-' . bin2hex(random_bytes(8));
mkdir($fixture, 0700, true);
function private_directory(mixed $path): string {
    global $fixture;
    $target = $fixture . '/connections';
    if (!is_dir($target)) mkdir($target, 0700, true);
    return $target;
}
function storage_path(): string {
    global $fixture;
    $path = $fixture . '/storage';
    if (!is_dir($path)) mkdir($path, 0700, true);
    return $path;
}
function stored_file_path(string $key): string {
    if (!preg_match('/\A[a-f0-9]{64}\z/', $key)) throw new HttpError(503, 'Invalid key');
    return storage_path() . '/' . $key . '.bin';
}
require dirname(__DIR__) . '/3dlens-private/model-storage.php';
require dirname(__DIR__) . '/3dlens-private/recovery.php';
function expect(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
    echo 'PASS: ' . $message . PHP_EOL;
}
function rejects(callable $call, int $status, string $message): void {
    try { $call(); } catch (HttpError $error) { expect($error->status === $status, $message); return; }
    throw new RuntimeException('Expected rejection: ' . $message);
}
function cleanup_fixture(string $path): void {
    foreach (new FilesystemIterator($path) as $entry) {
        if ($entry->isDir()) cleanup_fixture($entry->getPathname());
        else unlink($entry->getPathname());
    }
    rmdir($path);
}
try {
    $remoteSecret = 'never-display-this-secret-or-token';
    $tokenError = od_token_error(['error_codes' => [7000215], 'error_description' => $remoteSecret]);
    expect(str_contains($tokenError, 'AADSTS7000215') && str_contains($tokenError, 'Value') && !str_contains($tokenError, $remoteSecret), 'Token failures explain invalid secrets without reflecting remote sensitive data');
    expect(str_contains(od_token_error(['error_description' => 'AADSTS7000222: ' . $remoteSecret]), 'verlopen'), 'Expired Microsoft credentials receive a specific explanation');
    $unsafeError = od_token_error(['error_codes' => ['<script>'], 'error' => $remoteSecret, 'error_description' => $remoteSecret]);
    expect(!str_contains($unsafeError, '<script>') && !str_contains($unsafeError, $remoteSecret), 'Unknown Microsoft errors never expose raw remote fields');
    expect(str_contains(od_token_error(['error' => 'invalid_grant']), 'Start opnieuw'), 'Expired authorization codes prompt a fresh connection');
    $gatePath = $fixture . '/recovery.local.json';
    rejects(fn() => recovery_gate($gatePath), 404, 'Account recovery is disabled without a private gate');
    $resetKey = str_repeat('a', 64);
    $resetGate = ['expires' => time() + 900, 'key_hash' => hash('sha256', $resetKey)];
    file_put_contents($gatePath, json_encode($resetGate));
    expect(recovery_gate($gatePath) === $resetGate, 'Account recovery accepts an unexpired private gate');
    rejects(fn() => recovery_validate($resetGate, str_repeat('b', 64), 'example-test-password', 'example-test-password'), 403, 'Recovery rejects a wrong one-time key');
    rejects(fn() => recovery_validate($resetGate, $resetKey, 'short', 'short'), 422, 'Recovery rejects short passwords');
    rejects(fn() => recovery_validate($resetGate, $resetKey, 'example-test-password', 'different-test-password'), 422, 'Recovery requires password confirmation');
    recovery_validate($resetGate, $resetKey, 'example-test-password', 'example-test-password');
    $resetGate['expires'] = time() - 1;
    file_put_contents($gatePath, json_encode($resetGate));
    rejects(fn() => recovery_gate($gatePath), 404, 'Expired recovery links never permit a reset');
    $_SESSION['onedrive_oauth'] = ['state' => 'expected', 'verifier' => 'test', 'expires' => time() + 900];
    $_GET = ['state' => 'wrong', 'code' => 'test'];
    rejects(fn() => od_complete_connect(), 403, 'OAuth callbacks from another session are rejected');
    expect(isset($_SESSION['onedrive_oauth']), 'An invalid callback cannot cancel the pending connection');
    $_SESSION['onedrive_oauth']['expires'] = time() - 1;
    $_GET['state'] = 'expected';
    rejects(fn() => od_complete_connect(), 403, 'Expired OAuth callbacks are rejected before Microsoft requests');
    $_SESSION['onedrive_oauth']['expires'] = time() + 900;
    $_GET['error'] = 'access_denied';
    rejects(fn() => od_complete_connect(), 400, 'Microsoft cancellation is handled without storing tokens');
    expect(!isset($_SESSION['onedrive_oauth']), 'A confirmed OAuth response consumes the one-time state');
    $_GET = [];
    expect(!od_ready() && !od_active(), 'Existing installations default to local storage');
    rejects(fn() => od_save_settings('not-a-client-id', 'fake-test-secret'), 422, 'Invalid Microsoft app IDs are rejected');
    $client = '00000000-1111-2222-3333-444444444444';
    od_save_settings($client, 'fake-test-secret');
    expect(od_ready() && !od_active(), 'Saving credentials alone never activates cloud uploads');
    od_save_settings($client, '');
    expect(od_settings()['client_secret'] === 'fake-test-secret', 'A blank secret preserves the saved value');
    expect(!file_exists(od_directory() . '/onedrive-tokens.json'), 'Credential setup does not create or invent OAuth tokens');
    $key = str_repeat('a', 64);
    $data = 'solid test-model';
    $row = ['storage_key' => $key, 'byte_size' => strlen($data), 'sha256' => hash('sha256', $data)];
    file_put_contents(stored_file_path($key), $data);
    $handle = model_open($row);
    expect(stream_get_contents($handle) === $data, 'Unmigrated models remain readable from local storage');
    fclose($handle);
    rejects(fn() => od_reference('../config'), 503, 'Filesystem traversal in cloud reference keys is rejected');
    od_write_reference($key, ['drive_id' => 'test-drive', 'item_id' => 'test-item', 'size' => strlen($data), 'sha256' => $row['sha256']]);
    expect(od_has_references(), 'Cloud references are detected without exposing tokens');
    rejects(fn() => od_save_settings('11111111-1111-2222-3333-444444444444', 'different-fake-secret'), 409, 'Changing app IDs cannot orphan an existing cloud archive');
    // No tokens exist: OneDrive must fail before any HTTP call. A matching local
    // migration mirror still keeps the archive usable during that failure.
    $handle = model_open($row);
    expect(stream_get_contents($handle) === $data, 'A verified local mirror survives an unavailable OneDrive connection');
    fclose($handle);
    file_put_contents(stored_file_path($key), 'damaged-local-data');
    rejects(fn() => model_open($row), 503, 'A damaged local mirror is never served as a successful cloud fallback');
    file_put_contents(stored_file_path($key), $data);
    $wrong = $row;
    $wrong['sha256'] = str_repeat('b', 64);
    rejects(fn() => model_open($wrong), 503, 'A cloud reference must match the database content hash');
    model_remove($key);
    expect(!is_file(stored_file_path($key)), 'Deleted records do not retain the local model');
    expect(od_reference($key)['pending_delete'] === true, 'Failed cloud recycling keeps a retryable private reference');
    rejects(fn() => model_migration_step('copy'), 409, 'Migration requires a completed Microsoft connection');
    if (PHP_OS_FAMILY !== 'Windows') {
        expect((fileperms(od_directory() . '/onedrive-settings.json') & 0777) === 0600, 'Stored app credentials are owner-readable only');
    }
    echo 'All offline OneDrive tests passed.' . PHP_EOL;
} finally { cleanup_fixture($fixture); }
