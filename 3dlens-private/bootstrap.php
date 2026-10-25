<?php
declare(strict_types=1);

define('THREEDLENS_BOOTSTRAPPED', true);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

final class HttpError extends RuntimeException
{
    public function __construct(public readonly int $status, string $message)
    {
        parent::__construct($message);
    }
}

function is_api(): bool
{
    return in_array(basename($_SERVER['SCRIPT_FILENAME'] ?? ''), ['api.php', 'onedrive-migrate.php'], true);
}

set_exception_handler(function (Throwable $error): void {
    $status = $error instanceof HttpError ? $error->status : 503;
    $message = $error instanceof HttpError ? $error->getMessage()
        : '3DLens is tijdelijk niet beschikbaar. Controleer de serverconfiguratie of probeer het later opnieuw.';
    // Never expose credentials, SQL, stack traces or filesystem paths to visitors.
    error_log('3DLens request failed: ' . get_class($error) . ' (' . $status . ')');
    http_response_code($status);
    if (is_api()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="nl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>3DLens</title><body><h1>3DLens</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></body></html>';
    }
    exit;
});

header('Cache-Control: no-store, private, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
// Browsers can apply form-action to redirects too. Only the OneDrive settings
// route needs to send the owner from its local POST to Microsoft's OAuth page.
$formAction = basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'onedrive.php'
    ? "'self' https://login.microsoftonline.com" : "'self'";
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: blob:; connect-src 'self' https://cdn.jsdelivr.net; worker-src 'self' blob:; object-src 'none'; base-uri 'none'; frame-ancestors 'none'; form-action " . $formAction);

$configFile = __DIR__ . '/config.local.php';
if (!is_file($configFile)) {
    throw new HttpError(503, '3DLens is nog niet ingesteld. Volg de installatiehandleiding voor Hostinger.');
}
$config = require $configFile;
if (!is_array($config)) {
    throw new HttpError(503, 'De serverconfiguratie ontbreekt of is ongeldig.');
}

function config(string $name): mixed
{
    global $config;
    if (!array_key_exists($name, $config)) {
        throw new HttpError(503, 'De serverconfiguratie is niet compleet.');
    }
    return $config[$name];
}

$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (config('require_https') && !$https) {
    // Do not trust client-controlled forwarded headers or redirect to a Host header.
    throw new HttpError(403, 'Open 3DLens via https://. Een beveiligde verbinding is verplicht.');
}
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
ini_set('session.gc_maxlifetime', (string) config('idle_timeout_seconds'));
session_name('lens_' . substr(hash('sha256', __DIR__), 0, 12));
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $https,
    'httponly' => true,
    // Lax allows the top-level Microsoft OAuth callback. All mutations still
    // require CSRF; the callback additionally validates state and PKCE.
    'samesite' => 'Lax',
]);
session_start();

function db(): PDO
{
    static $connection = null;
    if ($connection === null) {
        $connection = new PDO(
            'mysql:host=' . config('db_host') . ';port=' . (int) config('db_port')
                . ';dbname=' . config('db_name') . ';charset=utf8mb4',
            config('db_user'), config('db_password'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES => false]
        );
    }
    return $connection;
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function check_csrf(): void
{
    $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf'] ?? '';
    if (!is_string($provided) || !hash_equals(csrf_token(), $provided)) {
        throw new HttpError(403, 'Deze aanvraag is verlopen. Vernieuw de pagina en probeer opnieuw.');
    }
}

function require_method(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        header('Allow: ' . $method);
        throw new HttpError(405, 'Deze aanvraagmethode is niet toegestaan.');
    }
}

function logged_in(): bool
{
    $now = time();
    if (empty($_SESSION['user_id']) ||
        $now - ($_SESSION['last_seen'] ?? 0) > (int) config('idle_timeout_seconds') ||
        $now - ($_SESSION['started'] ?? 0) > (int) config('session_lifetime_seconds')) {
        unset($_SESSION['user_id'], $_SESSION['started'], $_SESSION['last_seen']);
        return false;
    }
    $user = db()->prepare('SELECT id, password_hash FROM lens_users WHERE id = ?');
    $user->execute([$_SESSION['user_id']]);
    $account = $user->fetch();
    if (!$account || !hash_equals(hash('sha256', $account['password_hash']), $_SESSION['auth_hash'] ?? '')) {
        unset($_SESSION['user_id']);
        return false;
    }
    $_SESSION['last_seen'] = $now;
    return true;
}

function require_login(): void
{
    if (!logged_in()) {
        if (is_api()) {
            throw new HttpError(401, 'Je sessie is verlopen. Log opnieuw in.');
        }
        header('Location: login.php', true, 303);
        exit;
    }
}

function json_response(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function h(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function post_string(string $key): string
{
    return is_string($_POST[$key] ?? null) ? $_POST[$key] : '';
}

function input_id(mixed $value): string
{
    if (!is_string($value) || !preg_match('/\A[a-zA-Z0-9_-]{1,80}\z/', $value)) {
        throw new HttpError(422, 'Ongeldige bestands- of collectiecode.');
    }
    return $value;
}

function storage_path(): string
{
    return private_directory(config('storage_path'));
}

function private_directory(mixed $path): string
{
    if (!is_string($path) || $path === '') {
        throw new HttpError(503, 'De opslagmap is niet ingesteld.');
    }
    if (!is_dir($path) && !mkdir($path, 0700, true) && !is_dir($path)) {
        throw new HttpError(503, 'De privéopslag is niet beschikbaar.');
    }
    $resolved = realpath($path);
    $publicRoots = [realpath(dirname(__DIR__) . '/public_html'), realpath($_SERVER['DOCUMENT_ROOT'] ?? '')];
    if ($resolved === false) {
        throw new HttpError(503, 'De opslag moet buiten public_html staan.');
    }
    foreach ($publicRoots as $public) {
        if ($public !== false && ($resolved === $public || str_starts_with($resolved, $public . DIRECTORY_SEPARATOR))) {
            throw new HttpError(503, 'De opslag moet buiten public_html staan.');
        }
    }
    return $resolved;
}

function stored_file_path(string $key): string
{
    if (!preg_match('/\A[a-f0-9]{64}\z/', $key)) {
        throw new HttpError(503, 'Het opgeslagen bestand is ongeldig.');
    }
    return storage_path() . DIRECTORY_SEPARATOR . $key . '.bin';
}
