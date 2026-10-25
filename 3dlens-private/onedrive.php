<?php
declare(strict_types=1);
if (!defined('THREEDLENS_BOOTSTRAPPED')) { http_response_code(404); exit; }

const OD_REDIRECT_URI = 'https://koomen.3dlens.cloud/onedrive-callback.php';
const OD_SCOPE = 'offline_access https://graph.microsoft.com/Files.ReadWrite.AppFolder';
const OD_GRAPH = 'https://graph.microsoft.com/v1.0';
const OD_LOGIN = 'https://login.microsoftonline.com/common/oauth2/v2.0/';

function od_directory(): string { return private_directory(__DIR__ . '/connections'); }

function od_read_json(string $name): array
{
    $path = od_directory() . '/' . $name . '.json';
    if (!is_file($path)) return [];
    $data = json_decode((string) file_get_contents($path), true, 32, JSON_THROW_ON_ERROR);
    if (!is_array($data)) throw new HttpError(503, 'De OneDrive-configuratie is niet leesbaar.');
    return $data;
}

function od_write_json(string $name, array $data): void
{
    $path = od_directory() . '/' . $name . '.json';
    $temporary = $path . '.' . bin2hex(random_bytes(8)) . '.tmp';
    $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    $handle = fopen($temporary, 'xb');
    if (!$handle) throw new HttpError(503, 'OneDrive-instellingen konden niet worden opgeslagen.');
    try {
        chmod($temporary, 0600);
        if (fwrite($handle, $json) !== strlen($json) || !fflush($handle)) {
            throw new HttpError(503, 'OneDrive-instellingen konden niet worden opgeslagen.');
        }
    } finally { fclose($handle); }
    if (!rename($temporary, $path)) {
        @unlink($temporary);
        throw new HttpError(503, 'OneDrive-instellingen konden niet worden opgeslagen.');
    }
}

function od_locked(callable $action): mixed
{
    $lock = fopen(od_directory() . '/onedrive.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX)) throw new HttpError(503, 'OneDrive is bezig. Probeer opnieuw.');
    try { chmod(od_directory() . '/onedrive.lock', 0600); return $action(); }
    finally { flock($lock, LOCK_UN); fclose($lock); }
}

function od_settings(): array { return od_read_json('onedrive-settings'); }

function od_ready(): bool
{
    $settings = od_settings();
    return !empty($settings['client_id']) && !empty($settings['client_secret']);
}

function od_active(): bool
{
    return !empty(od_settings()['active']);
}

function od_reference_path(string $key): string
{
    // Reuse the strict 64-character server key validation. Never use user paths.
    return stored_file_path($key) . '.onedrive.json';
}

function od_reference(string $key): ?array
{
    $path = od_reference_path($key);
    if (!is_file($path)) return null;
    $reference = json_decode((string) file_get_contents($path), true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($reference) || empty($reference['drive_id']) || empty($reference['item_id']) ||
        !preg_match('/\A[a-f0-9]{64}\z/', $reference['sha256'] ?? '')) {
        throw new HttpError(503, 'De OneDrive-bestandsverwijzing is ongeldig.');
    }
    return $reference;
}

function od_write_reference(string $key, array $reference): void
{
    $path = od_reference_path($key);
    $temporary = $path . '.' . bin2hex(random_bytes(8)) . '.tmp';
    $handle = fopen($temporary, 'xb');
    if (!$handle) throw new HttpError(503, 'De bestandsverwijzing kon niet worden opgeslagen.');
    try {
        chmod($temporary, 0600);
        $json = json_encode($reference, JSON_THROW_ON_ERROR);
        if (fwrite($handle, $json) !== strlen($json) || !fflush($handle)) {
            throw new HttpError(503, 'De bestandsverwijzing kon niet worden opgeslagen.');
        }
    } finally { fclose($handle); }
    if (!rename($temporary, $path)) {
        @unlink($temporary);
        throw new HttpError(503, 'De bestandsverwijzing kon niet worden opgeslagen.');
    }
}

function od_has_references(): bool
{
    $iterator = new DirectoryIterator(storage_path());
    foreach ($iterator as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.bin.onedrive.json')) return true;
    }
    return false;
}

function od_save_settings(string $clientId, string $clientSecret): void
{
    $clientId = strtolower(trim($clientId));
    if (!preg_match('/\A[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}\z/', $clientId)) {
        throw new HttpError(422, 'Vul de Application (client) ID uit Microsoft Entra in.');
    }
    od_locked(function () use ($clientId, $clientSecret): void {
        $old = od_settings();
        $changed = isset($old['client_id']) && $old['client_id'] !== $clientId;
        if ($changed && od_has_references()) {
            throw new HttpError(409, 'Er staan al modellen in deze OneDrive-appmap. Gebruik dezelfde appregistratie.');
        }
        if ($clientSecret === '' && !$changed) $clientSecret = $old['client_secret'] ?? '';
        if ($clientSecret === '' || strlen($clientSecret) > 4096 || preg_match('/[\x00-\x1f]/', $clientSecret)) {
            throw new HttpError(422, 'Vul de waarde van je client secret in.');
        }
        $settings = $changed ? [] : $old;
        $settings['client_id'] = $clientId;
        $settings['client_secret'] = $clientSecret;
        $settings['redirect_uri'] = OD_REDIRECT_URI;
        if ($changed) od_write_json('onedrive-tokens', []);
        od_write_json('onedrive-settings', $settings);
    });
}

/** Fixed Microsoft API requests; tokens and API error bodies never reach HTML or logs. */
function od_http(string $method, string $url, array $headers = [], mixed $body = null): array
{
    if (!function_exists('curl_init')) throw new HttpError(503, 'Schakel de PHP-extensie cURL in voor OneDrive.');
    if (!str_starts_with($url, OD_GRAPH . '/') && !str_starts_with($url, OD_LOGIN)) {
        throw new HttpError(503, 'Ongeldig OneDrive-adres.');
    }
    $curl = curl_init($url);
    $response = '';
    $location = '';
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_TIMEOUT => 240,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HEADERFUNCTION => function ($handle, string $line) use (&$location): int {
            if (preg_match('/\AHTTP\/\S+\s+[0-9]{3}\b/i', $line)) $location = '';
            elseif (stripos($line, 'Location:') === 0) {
                $value = trim(substr($line, 9));
                if (strlen($value) > 8192) return 0;
                $location = $value;
            }
            return strlen($line);
        },
        CURLOPT_WRITEFUNCTION => function ($handle, string $chunk) use (&$response): int {
            if (strlen($response) + strlen($chunk) > 2 * 1024 * 1024) return 0;
            $response .= $chunk;
            return strlen($chunk);
        },
    ]);
    if (is_resource($body)) {
        rewind($body);
        $stat = fstat($body);
        curl_setopt_array($curl, [CURLOPT_UPLOAD => true, CURLOPT_INFILE => $body, CURLOPT_INFILESIZE => $stat['size']]);
    } elseif (is_string($body)) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
    }
    $ok = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    if ($ok === false) throw new HttpError(503, 'De verbinding met OneDrive is onderbroken. Probeer opnieuw.');
    $data = $response === '' ? [] : json_decode($response, true);
    return ['status' => $status, 'data' => is_array($data) ? $data : [], 'location' => $location];
}

/** Show only bounded Microsoft codes and our own text, never remote error descriptions. */
function od_token_error(array $data): string
{
    $code = '';
    $candidate = is_array($data['error_codes'] ?? null) ? ($data['error_codes'][0] ?? null) : null;
    if ((is_int($candidate) || is_string($candidate)) && preg_match('/\A[0-9]{1,10}\z/', (string) $candidate)) {
        $code = (string) $candidate;
    } elseif (is_string($data['error_description'] ?? null) &&
        preg_match('/\bAADSTS([0-9]{1,10})\b/', $data['error_description'], $match)) {
        $code = $match[1];
    }
    $message = match ($code) {
        '7000215', '70002' => 'De client secret past niet bij deze app. Vul de Value in, niet de Secret ID, van dezelfde app als de Application (client) ID.',
        '7000222' => 'De client secret is verlopen. Maak bij dezelfde Microsoft-app een nieuwe secret en sla de nieuwe Value op in 3DLens.',
        '700016', '700011' => 'Microsoft kan deze appregistratie niet vinden. Controleer de Application (client) ID en ondersteuning voor persoonlijke Microsoft-accounts.',
        '7000218' => 'Microsoft ontvangt geen geldige appreferentie. Controleer de client secret en registreer het omleidingsadres als Web.',
        '700025', '9002326' => 'De app is als publieke app of SPA ingesteld. Registreer het omleidingsadres als Web voor deze serverkoppeling.',
        '50011' => 'Het omleidingsadres wijkt af. Stel bij Web exact ' . OD_REDIRECT_URI . ' in.',
        '65001' => 'Microsoft heeft de benodigde toestemming nog niet bevestigd. Start OneDrive koppelen opnieuw en controleer de gevraagde machtigingen.',
        '70000', '54005' => 'De Microsoft-bevestiging is verlopen, ongeldig of al gebruikt. Start opnieuw via OneDrive koppelen; vernieuw de terugkeerpagina niet.',
        default => match ($data['error'] ?? '') {
            'invalid_client' => 'Microsoft accepteert de appgegevens niet. Controleer de Application (client) ID en de client secret Value van dezelfde app.',
            'invalid_grant' => 'De Microsoft-bevestiging is ongeldig of verlopen. Start opnieuw via OneDrive koppelen.',
            'invalid_scope' => 'Microsoft accepteert de machtigingen niet. Controleer Files.ReadWrite.AppFolder en offline_access.',
            'unauthorized_client' => 'Deze app mag dit accounttype niet koppelen. Controleer ondersteuning voor persoonlijke Microsoft-accounts en het Web-omleidingsadres.',
            default => 'Microsoft kon de koppeling niet bevestigen. Controleer je appinstellingen en koppel opnieuw.',
        },
    };
    return ($code !== '' ? 'Microsoft (AADSTS' . $code . '): ' : '') . $message;
}

function od_token_exchange(array $fields): array
{
    $settings = od_settings();
    if (!od_ready()) throw new HttpError(409, 'Stel eerst de Microsoft-appregistratie in.');
    $response = od_http('POST', OD_LOGIN . 'token', ['Content-Type: application/x-www-form-urlencoded'],
        http_build_query($fields + ['client_id' => $settings['client_id'], 'client_secret' => $settings['client_secret']]));
    if ($response['status'] !== 200 || empty($response['data']['access_token'])) {
        throw new HttpError(503, od_token_error($response['data']));
    }
    return $response['data'];
}

function od_access_token(bool $refresh = false): string
{
    return od_locked(function () use ($refresh): string {
        $tokens = od_read_json('onedrive-tokens');
        if (!$refresh && !empty($tokens['access_token']) && ($tokens['expires_at'] ?? 0) > time() + 90) {
            return $tokens['access_token'];
        }
        if (empty($tokens['refresh_token'])) throw new HttpError(503, 'Koppel OneDrive opnieuw via de opslaginstellingen.');
        $new = od_token_exchange(['grant_type' => 'refresh_token', 'refresh_token' => $tokens['refresh_token'], 'scope' => OD_SCOPE]);
        $tokens['access_token'] = $new['access_token'];
        $tokens['refresh_token'] = $new['refresh_token'] ?? $tokens['refresh_token'];
        $tokens['expires_at'] = time() + (int) ($new['expires_in'] ?? 3600);
        od_write_json('onedrive-tokens', $tokens);
        return $tokens['access_token'];
    });
}

function od_graph(string $method, string $path, mixed $body = null, string $type = 'application/json', bool $downloadRedirect = false): array
{
    if (!str_starts_with($path, '/')) throw new HttpError(503, 'Ongeldig OneDrive-pad.');
    for ($attempt = 0; $attempt < 2; $attempt++) {
        $token = od_access_token($attempt > 0);
        $response = od_http($method, OD_GRAPH . $path, ['Authorization: Bearer ' . $token, 'Content-Type: ' . $type], $body);
        if ($response['status'] !== 401) break;
    }
    if ($downloadRedirect && $method === 'GET' && str_ends_with($path, '/content') && $response['status'] === 302) {
        return ['@microsoft.graph.downloadUrl' => od_validate_download_url($response['location'] ?? '')];
    }
    if ($response['status'] === 404) throw new HttpError(404, 'Het model is niet meer beschikbaar in OneDrive.');
    if ($response['status'] === 429) throw new HttpError(503, 'OneDrive is tijdelijk druk. Wacht even en probeer opnieuw.');
    if ($response['status'] < 200 || $response['status'] >= 300) {
        throw new HttpError(503, 'OneDrive kon de bewerking niet uitvoeren. Controleer de koppeling en beschikbare opslagruimte.');
    }
    return $response['data'];
}

function od_begin_connect(): never
{
    if (!od_ready()) throw new HttpError(409, 'Sla eerst de appinstellingen op.');
    // Emit the Lax cookie even for an owner whose previous login used Strict.
    session_regenerate_id(true);
    $state = bin2hex(random_bytes(32));
    $verifier = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
    $_SESSION['onedrive_oauth'] = ['state' => $state, 'verifier' => $verifier, 'expires' => time() + 900];
    $query = http_build_query([
        'client_id' => od_settings()['client_id'], 'response_type' => 'code',
        'redirect_uri' => OD_REDIRECT_URI, 'response_mode' => 'query', 'scope' => OD_SCOPE,
        'state' => $state, 'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
        'code_challenge_method' => 'S256', 'prompt' => 'select_account',
    ]);
    session_write_close();
    header('Location: ' . OD_LOGIN . 'authorize?' . $query, true, 303);
    exit;
}

function od_complete_connect(): void
{
    $pending = $_SESSION['onedrive_oauth'] ?? [];
    $state = $_GET['state'] ?? '';
    if (!is_string($state) || empty($pending['state']) || !hash_equals($pending['state'], $state) || ($pending['expires'] ?? 0) < time()) {
        throw new HttpError(403, 'De OneDrive-aanvraag is verlopen of hoort niet bij deze sessie. Start de koppeling opnieuw.');
    }
    unset($_SESSION['onedrive_oauth']);
    if (isset($_GET['error'])) throw new HttpError(400, 'De Microsoft-koppeling is geannuleerd of niet toegestaan.');
    $code = $_GET['code'] ?? '';
    if (!is_string($code) || $code === '' || strlen($code) > 16384) throw new HttpError(400, 'Microsoft heeft geen geldige bevestiging gestuurd.');
    session_write_close();
    $tokens = od_token_exchange(['grant_type' => 'authorization_code', 'code' => $code,
        'redirect_uri' => OD_REDIRECT_URI, 'code_verifier' => $pending['verifier'], 'scope' => OD_SCOPE]);
    if (empty($tokens['refresh_token'])) throw new HttpError(503, 'Microsoft heeft geen blijvende toegang bevestigd. Koppel opnieuw met offline_access.');
    $root = od_http('GET', OD_GRAPH . '/me/drive/special/approot', ['Authorization: Bearer ' . $tokens['access_token']]);
    $folder = $root['data'];
    $driveId = $folder['parentReference']['driveId'] ?? '';
    if ($root['status'] !== 200 || empty($folder['id']) || !is_string($driveId) || $driveId === '') {
        throw new HttpError(503, 'De privé-appmap in OneDrive kon niet worden geopend. Controleer de machtiging Files.ReadWrite.AppFolder.');
    }
    od_locked(function () use ($tokens, $folder, $driveId): void {
        $settings = od_settings();
        if (!empty($settings['drive_id']) && $settings['drive_id'] !== $driveId && od_has_references()) {
            throw new HttpError(409, 'Er zijn modellen gekoppeld aan je vorige OneDrive-account. Koppel hetzelfde account opnieuw.');
        }
        od_write_json('onedrive-tokens', [
            'access_token' => $tokens['access_token'], 'refresh_token' => $tokens['refresh_token'] ?? '',
            'expires_at' => time() + (int) ($tokens['expires_in'] ?? 3600),
        ]);
        $settings['drive_id'] = $driveId;
        $settings['folder_id'] = $folder['id'];
        $settings['active'] = true;
        od_write_json('onedrive-settings', $settings);
    });
}

function od_item_path(array $reference): string
{
    return '/drives/' . rawurlencode($reference['drive_id']) . '/items/' . rawurlencode($reference['item_id']);
}

function od_validate_download_url(mixed $url): string
{
    $parsed = is_string($url) ? parse_url($url) : false;
    if (!$parsed || ($parsed['scheme'] ?? '') !== 'https' || empty($parsed['host']) ||
        isset($parsed['user']) || isset($parsed['pass']) || preg_match('/[\x00-\x20\x7f]/', $url)) {
        throw new HttpError(503, 'OneDrive heeft geen geldig downloadadres gegeven.');
    }
    return $url;
}

/** Download into an automatically removed temporary file, never a permanent model copy. */
function od_download(array $reference): mixed
{
    // /content provides the documented Location redirect even when the metadata
    // response omits its temporary downloadUrl annotation. Do not follow this
    // authenticated Graph request: the signed download uses a separate client.
    $item = od_graph('GET', od_item_path($reference) . '/content', null, 'application/json', true);
    $url = od_validate_download_url($item['@microsoft.graph.downloadUrl'] ?? '');
    $file = tmpfile();
    if (!$file) throw new HttpError(503, 'Er is geen tijdelijke ruimte beschikbaar voor de modeldownload.');
    $hash = hash_init('sha256');
    $bytes = 0;
    $expected = (int) $reference['size'];
    $curl = curl_init($url);
    // This URL comes only from Microsoft Graph, never from a client or sidecar.
    // Do not forward the Graph bearer token to the signed download URL.
    curl_setopt_array($curl, [
        CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_TIMEOUT => 240,
        CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 3,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_WRITEFUNCTION => function ($handle, string $chunk) use ($file, $hash, &$bytes, $expected): int {
            if ($bytes + strlen($chunk) > $expected) return 0;
            $written = fwrite($file, $chunk);
            if ($written !== strlen($chunk)) return 0;
            $bytes += $written;
            hash_update($hash, $chunk);
            return $written;
        },
    ]);
    $ok = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    $digest = hash_final($hash);
    if ($ok === false || $status !== 200 || $bytes !== $expected || !hash_equals($reference['sha256'], $digest)) {
        fclose($file);
        throw new HttpError(503, 'De OneDrive-download kon niet volledig worden gecontroleerd. Het model is niet gewijzigd.');
    }
    rewind($file);
    return $file;
}

function od_upload(string $key, string $name, string $source, int $size, string $hash): array
{
    $settings = od_settings();
    if (!od_active() || empty($settings['drive_id']) || empty($settings['folder_id'])) {
        throw new HttpError(503, 'Koppel OneDrive voordat je bestanden overzet.');
    }
    if ($size > 250000000) throw new HttpError(413, 'Voor OneDrive geldt in deze versie maximaal 250 MB per bestand.');
    $file = fopen($source, 'rb');
    if (!$file) throw new HttpError(503, 'Het lokale model kon niet worden geopend.');
    try {
        $filename = $key . '.' . strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $item = od_graph('PUT', '/drives/' . rawurlencode($settings['drive_id']) . '/items/' . rawurlencode($settings['folder_id'])
            . ':/' . rawurlencode($filename) . ':/content', $file, 'application/octet-stream');
    } finally { fclose($file); }
    if (empty($item['id']) || (int) ($item['size'] ?? -1) !== $size) {
        throw new HttpError(503, 'OneDrive heeft de volledige upload niet bevestigd. Het lokale model blijft behouden.');
    }
    $reference = ['drive_id' => $settings['drive_id'], 'item_id' => $item['id'], 'size' => $size, 'sha256' => $hash];
    // Verify the bytes by downloading and hashing, including accounts whose
    // Graph metadata does not expose a SHA-256. Only then publish the reference.
    $verified = od_download($reference);
    fclose($verified);
    od_write_reference($key, $reference);
    return $reference;
}
