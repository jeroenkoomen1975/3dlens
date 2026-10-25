<?php
declare(strict_types=1);
require dirname(__DIR__) . '/3dlens-private/bootstrap.php';
require dirname(__DIR__) . '/3dlens-private/repository.php';
require_login();
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST', 'DELETE'], true)) {
    header('Allow: GET, POST, DELETE');
    throw new HttpError(405, 'Deze aanvraagmethode is niet toegestaan.');
}
if ($method !== 'GET') {
    check_csrf();
}
// Release session locking for concurrent thumbnails, downloads and large uploads.
session_write_close();
$store = $_GET['store'] ?? '';
if (!in_array($store, ['collections', 'files'], true)) {
    throw new HttpError(422, 'Ongeldig opslagtype.');
}

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        if ($store !== 'files') {
            throw new HttpError(422, 'Dit bestand is niet beschikbaar.');
        }
        $id = input_id($_GET['id']);
        $query = db()->prepare('SELECT storage_key, name, byte_size FROM lens_files WHERE id = ?');
        $query->execute([$id]);
        $file = $query->fetch();
        if (!$file) {
            throw new HttpError(404, 'Dit bestand bestaat niet meer.');
        }
        $path = stored_file_path($file['storage_key']);
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            throw new HttpError(503, 'Het opgeslagen bestand is tijdelijk niet beschikbaar.');
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="model.' . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION))
            . '"; filename*=UTF-8\'\'' . rawurlencode($file['name']));
        header('Content-Length: ' . $file['byte_size']);
        // A model must never be rendered as active HTML, even if renamed on upload.
        header("Content-Security-Policy: sandbox; default-src 'none'");
        fpassthru($handle);
        fclose($handle);
        exit;
    }
    if ($store === 'collections') {
        json_response(db()->query('SELECT id, name FROM lens_collections ORDER BY name, id')->fetchAll());
    }
    json_response(array_map('file_metadata', db()->query('SELECT * FROM lens_files ORDER BY added, id')->fetchAll()));
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? input_id($_GET['id']) : null;
    if ($id === null && ($_GET['confirm'] ?? '') !== 'clear') {
        throw new HttpError(422, 'Bevestig het leegmaken van het archief.');
    }
    delete_records($store, $id);
    json_response(['ok' => true]);
}

$raw = post_string('metadata');
if (strlen($raw) > 3 * 1024 * 1024) {
    throw new HttpError(413, 'De bestandsgegevens zijn te groot.');
}
try {
    $metadata = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    throw new HttpError(422, 'Ongeldige bestandsgegevens. Mogelijk is de uploadlimiet van de hosting overschreden.');
}
if (!is_array($metadata)) {
    throw new HttpError(422, 'Ongeldige bestandsgegevens.');
}
if ($store === 'files') {
    json_response(save_model($metadata, $_FILES['file'] ?? null));
}
$id = input_id($metadata['id'] ?? null);
$name = trim(bounded_text($metadata['name'] ?? null, 200, 'collectienaam'));
if ($name === '') {
    throw new HttpError(422, 'Geef de collectie een naam.');
}
try {
    write_lock();
    $save = db()->prepare('INSERT INTO lens_collections (id, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)');
    $save->execute([$id, $name]);
    db()->commit();
} catch (Throwable $error) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    throw $error;
}
json_response(['id' => $id, 'name' => $name]);
