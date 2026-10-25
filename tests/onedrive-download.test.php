<?php
declare(strict_types=1);
// Run with php -n: simulate both HTTPS endpoints without network or real tokens.
if (extension_loaded('curl')) { fwrite(STDERR, "Run this transport test with php -n.\n"); exit(1); }
define('THREEDLENS_BOOTSTRAPPED', true);
foreach (['CURLOPT_CUSTOMREQUEST', 'CURLOPT_HTTPHEADER', 'CURLOPT_CONNECTTIMEOUT', 'CURLOPT_TIMEOUT',
    'CURLOPT_FOLLOWLOCATION', 'CURLOPT_PROTOCOLS', 'CURLOPT_SSL_VERIFYPEER', 'CURLOPT_SSL_VERIFYHOST',
    'CURLOPT_HEADERFUNCTION', 'CURLOPT_WRITEFUNCTION', 'CURLOPT_UPLOAD', 'CURLOPT_INFILE',
    'CURLOPT_INFILESIZE', 'CURLOPT_POSTFIELDS', 'CURLINFO_RESPONSE_CODE', 'CURLOPT_MAXREDIRS',
    'CURLOPT_REDIR_PROTOCOLS', 'CURLPROTO_HTTPS'] as $number => $constant) define($constant, $number + 1000);
final class HttpError extends RuntimeException {
    public function __construct(public readonly int $status, string $message) { parent::__construct($message); }
}
$fixture = sys_get_temp_dir() . '/3dlens-download-test-' . bin2hex(random_bytes(8));
mkdir($fixture, 0700);
function private_directory(mixed $path): string { global $fixture; return $fixture; }
require dirname(__DIR__) . '/3dlens-private/onedrive.php';
function expect(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
    echo 'PASS: ' . $message . PHP_EOL;
}
$requests = [];
$payload = 'verified-model-bytes';
$mode = 'success';
$signed = 'https://example.files.1drv.com/model?auth=fake-test-signature';
function curl_init(string $url): object { return (object) ['url' => $url, 'options' => [], 'status' => 0]; }
function curl_setopt_array(object $curl, array $options): bool { $curl->options = $options + $curl->options; return true; }
function curl_setopt(object $curl, int $option, mixed $value): bool { $curl->options[$option] = $value; return true; }
function curl_getinfo(object $curl, int $option): int { return $curl->status; }
function curl_close(object $curl): void {}
function curl_exec(object $curl): bool {
    global $requests, $signed, $payload, $mode;
    $requests[] = $curl;
    if (str_starts_with($curl->url, OD_GRAPH . '/')) {
        expect($curl->url === OD_GRAPH . '/drives/test-drive/items/test-item/content', 'The download requests the Graph content endpoint instead of optional metadata');
        expect(in_array('Authorization: Bearer fake-token', $curl->options[CURLOPT_HTTPHEADER], true), 'The Graph request includes owner authorization');
        expect($curl->options[CURLOPT_FOLLOWLOCATION] === false, 'The authenticated request never follows a redirect');
        $header = $curl->options[CURLOPT_HEADERFUNCTION];
        $header($curl, "HTTP/1.1 100 Continue\r\n");
        $header($curl, "Location: https://wrong.example/old-header\r\n");
        $header($curl, "HTTP/2 302 Found\r\n");
        if ($mode !== 'missing-location') $header($curl, 'Location: ' . $signed . "\r\n");
        $curl->status = 302;
        return true;
    }
    expect($curl->url === $signed, 'The exact signed Location URL is used in a separate request');
    expect(empty($curl->options[CURLOPT_HTTPHEADER]), 'The Microsoft bearer token is never sent to the download host');
    $curl->status = 200;
    $body = $mode === 'corrupt' ? 'wrong-model-content' : $payload;
    $write = $curl->options[CURLOPT_WRITEFUNCTION];
    return $write($curl, $body) === strlen($body);
}
try {
    od_write_json('onedrive-tokens', ['access_token' => 'fake-token', 'expires_at' => time() + 3600]);
    $ref = ['drive_id' => 'test-drive', 'item_id' => 'test-item', 'size' => strlen($payload), 'sha256' => hash('sha256', $payload)];
    $handle = od_download($ref);
    expect(stream_get_contents($handle) === $payload, 'A verified download works when Graph provides only a Location header');
    fclose($handle);
    foreach (['corrupt', 'missing-location'] as $mode) {
        $rejected = false;
        try { $handle = od_download($ref); fclose($handle); }
        catch (HttpError $error) { $rejected = $error->status === 503; }
        expect($rejected, 'The download fails safely for ' . $mode);
    }
} finally {
    foreach (new FilesystemIterator($fixture) as $entry) unlink($entry->getPathname());
    rmdir($fixture);
}
