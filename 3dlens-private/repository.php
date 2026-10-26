<?php
declare(strict_types=1);
if (!defined('THREEDLENS_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/model-storage.php';

const MODEL_EXTENSIONS = ['stl', '3mf', 'ctb', 'cbddlp', 'phz', 'photon', 'pws', 'pw0', 'pwmx', 'pwms', 'pwmo', 'pwx', 'sl1', 'sl1s'];

function bounded_text(mixed $value, int $limit, string $field): string
{
    if (!is_string($value) || strlen($value) > $limit || str_contains($value, "\0")) {
        throw new HttpError(422, 'Ongeldige of te lange waarde voor ' . $field . '.');
    }
    return $value;
}

function validated_metadata(array $input): array
{
    $id = input_id($input['id'] ?? null);
    $name = bounded_text($input['name'] ?? null, 255, 'bestandsnaam');
    if ($name === '' || preg_match('/[\x00-\x1f\x7f\/\\\\]/', $name)) {
        throw new HttpError(422, 'Ongeldige bestandsnaam.');
    }
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($extension, MODEL_EXTENSIONS, true)) {
        throw new HttpError(422, 'Dit bestandstype wordt niet ondersteund.');
    }
    $collectionId = $input['collectionId'] ?? null;
    if ($collectionId !== null) {
        $collectionId = input_id($collectionId);
    }
    $tags = $input['tags'] ?? [];
    if (!is_array($tags) || !array_is_list($tags) || count($tags) > 50) {
        throw new HttpError(422, 'Gebruik maximaal 50 tags per bestand.');
    }
    foreach ($tags as $tag) {
        bounded_text($tag, 160, 'tag');
    }
    $thumbnail = $input['thumbnail'] ?? null;
    if ($thumbnail !== null) {
        if (!is_string($thumbnail) || strlen($thumbnail) > 2 * 1024 * 1024 ||
            !preg_match('#\Adata:image/(png|jpeg|webp);base64,([A-Za-z0-9+/=]+)\z#', $thumbnail, $matches)) {
            throw new HttpError(422, 'De miniatuur is geen geldige afbeelding of is te groot.');
        }
        $image = base64_decode($matches[2], true);
        $details = $image !== false ? @getimagesizefromstring($image) : false;
        if (!$details || !in_array($details['mime'], ['image/png', 'image/jpeg', 'image/webp'], true) ||
            $details[0] > 4096 || $details[1] > 4096) {
            throw new HttpError(422, 'Ongeldige miniatuurafbeelding.');
        }
    }
    $stats = $input['stats'] ?? null;
    if ($stats !== null) {
        if (!is_array($stats)) {
            throw new HttpError(422, 'Ongeldige modelstatistieken.');
        }
        foreach ($stats as $key => $value) {
            if ($key === 'sla' && is_array($value)) {
                foreach ($value as $slaKey => $slaValue) {
                    if ($slaKey === 'format') {
                        if (!is_string($slaValue) || !preg_match('/\A[a-zA-Z0-9_-]{1,30}\z/', $slaValue)) {
                            throw new HttpError(422, 'Ongeldig SLA-formaat.');
                        }
                    } elseif ($slaKey === 'printerModel') {
                        bounded_text($slaValue, 255, 'printermodel');
                    } elseif ($slaValue !== null && !is_int($slaValue) && !is_float($slaValue)) {
                        throw new HttpError(422, 'Ongeldige SLA-statistieken.');
                    }
                }
            } elseif ($value !== null && !is_int($value) && !is_float($value)) {
                throw new HttpError(422, 'Ongeldige modelstatistieken.');
            }
        }
    }
    $ai = $input['ai'] ?? null;
    if ($ai !== null) {
        if (!is_array($ai)) {
            throw new HttpError(422, 'Ongeldige Smart Scan.');
        }
        foreach (['displayName', 'modelType', 'engine'] as $key) {
            if (isset($ai[$key])) {
                bounded_text($ai[$key], 1000, 'Smart Scan');
            }
        }
        if (isset($ai['confidence']) && !is_int($ai['confidence']) && !is_float($ai['confidence'])) {
            throw new HttpError(422, 'Ongeldige Smart Scan-betrouwbaarheid.');
        }
        if (isset($ai['tags'])) {
            if (!is_array($ai['tags']) || count($ai['tags']) > 50) {
                throw new HttpError(422, 'Ongeldige Smart Scan-tags.');
            }
            foreach ($ai['tags'] as $tag) {
                bounded_text($tag, 160, 'Smart Scan-tag');
            }
        }
        if (isset($ai['printAdvice'])) {
            if (!is_array($ai['printAdvice'])) {
                throw new HttpError(422, 'Ongeldig printadvies.');
            }
            foreach (['orientation', 'difficulty', 'reason'] as $key) {
                if (isset($ai['printAdvice'][$key])) {
                    bounded_text($ai['printAdvice'][$key], 2000, 'printadvies');
                }
            }
        }
    }
    $status = $input['previewStatus'] ?? 'pending';
    if (!in_array($status, ['pending', 'ready', 'failed'], true)) {
        throw new HttpError(422, 'Ongeldige previewstatus.');
    }
    $added = $input['added'] ?? (int) (microtime(true) * 1000);
    if ((!is_int($added) && !is_float($added)) || $added < 0 || $added > 9999999999999) {
        throw new HttpError(422, 'Ongeldige datum.');
    }
    // Ignore caller-supplied type, size, storage location and content hash.
    return [
        'id' => $id, 'name' => $name, 'collectionId' => $collectionId,
        'type' => $extension === 'stl' || $extension === '3mf' ? $extension : 'sla',
        'subFormat' => in_array($extension, ['stl', '3mf'], true) ? null : $extension,
        'description' => bounded_text($input['description'] ?? '', 8000, 'omschrijving'),
        'tags' => array_values($tags), 'stats' => $stats, 'thumbnail' => $thumbnail,
        'previewStatus' => $status, 'ai' => $ai, 'added' => (int) $added,
        'modelCheck' => validated_model_check($input['modelCheck'] ?? null),
    ];
}

function validated_model_check(mixed $input): ?array
{
    if ($input === null) return null;
    if (!is_array($input)) throw new HttpError(422, 'Ongeldige modelcontrole.');
    $result = [];
    foreach (['checked', 'triangles', 'boundaryEdges', 'nonManifoldEdges', 'windingEdges',
        'degenerateTriangles', 'duplicateTriangles', 'invalidTriangles', 'topologySkipped'] as $key) {
        $value = $input[$key] ?? 0;
        if (!is_int($value) || $value < 0 || $value > 1000000000) throw new HttpError(422, 'Ongeldige modelcontrole.');
        $result[$key] = $value;
    }
    return $result;
}

function file_metadata(array $row): array
{
    $data = json_decode($row['metadata'], true, 32, JSON_THROW_ON_ERROR);
    $data['id'] = $row['id'];
    $data['name'] = $row['name'];
    $data['collectionId'] = $row['collection_id'];
    $data['size'] = (int) $row['byte_size'];
    $data['hash'] = $row['sha256'];
    $data['added'] = (int) $row['added'];
    return $data;
}

function write_lock(): void
{
    db()->beginTransaction();
    // Serialize mutations so collection deletion, file writes and clearing cannot race.
    db()->query('SELECT id FROM lens_users WHERE id = 1 FOR UPDATE')->fetch();
}

function save_model(array $metadata, ?array $upload): array
{
    $meta = validated_metadata($metadata);
    $newPath = null;
    $commitAttempted = false;
    try {
        write_lock();
        if ($meta['collectionId'] !== null) {
            $collection = db()->prepare('SELECT id FROM lens_collections WHERE id = ?');
            $collection->execute([$meta['collectionId']]);
            if (!$collection->fetch()) {
                throw new HttpError(422, 'Deze collectie bestaat niet meer. Vernieuw de pagina.');
            }
        }
        $existing = db()->prepare('SELECT * FROM lens_files WHERE id = ?');
        $existing->execute([$meta['id']]);
        $row = $existing->fetch();
        if ($row) {
            if ($upload !== null) {
                throw new HttpError(409, 'Dit bestand bestaat al. Vernieuw de pagina.');
            }
            $meta['added'] = (int) $row['added'];
            $update = db()->prepare('UPDATE lens_files SET collection_id = ?, name = ?, metadata = ? WHERE id = ?');
            $update->execute([$meta['collectionId'], $meta['name'], json_encode($meta, JSON_THROW_ON_ERROR), $meta['id']]);
        } else {
            if (!$upload || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])) {
                throw new HttpError(422, 'Het bestand is niet ontvangen. Controleer ook de uploadlimiet van je hosting.');
            }
            $size = filesize($upload['tmp_name']);
            if ($size === false || $size < 1 || $size > (int) config('max_upload_bytes')) {
                throw new HttpError(413, 'Het bestand is leeg of overschrijdt de ingestelde uploadlimiet.');
            }
            $hash = hash_file('sha256', $upload['tmp_name']);
            $duplicate = db()->prepare('SELECT id FROM lens_files WHERE sha256 = ?');
            $duplicate->execute([$hash]);
            if ($duplicate->fetch()) {
                throw new HttpError(409, 'Dit bestand staat al in het archief. Vernieuw de pagina.');
            }
            $key = bin2hex(random_bytes(32));
            $newPath = stored_file_path($key);
            if (od_active()) {
                od_upload($key, $meta['name'], $upload['tmp_name'], $size, $hash);
            } else {
                if (!move_uploaded_file($upload['tmp_name'], $newPath)) {
                    throw new HttpError(503, 'Het bestand kon niet worden opgeslagen.');
                }
                chmod($newPath, 0600);
            }
            $insert = db()->prepare('INSERT INTO lens_files (id, collection_id, name, byte_size, sha256, storage_key, metadata, added) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $insert->execute([$meta['id'], $meta['collectionId'], $meta['name'], $size, $hash, $key,
                json_encode($meta, JSON_THROW_ON_ERROR), $meta['added']]);
        }
        $result = db()->prepare('SELECT * FROM lens_files WHERE id = ?');
        $result->execute([$meta['id']]);
        $saved = file_metadata($result->fetch());
        $commitAttempted = true;
        db()->commit();
        return $saved;
    } catch (Throwable $error) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        // An interrupted commit can have succeeded on the database server.
        // Retain the upload rather than risk deleting a committed model.
        if ($newPath !== null && !$commitAttempted) {
            try { model_remove($key); }
            catch (Throwable) { error_log('3DLens: failed-upload cleanup requires attention.'); }
        }
        throw $error;
    }
}

function delete_records(string $store, ?string $id): void
{
    $keys = [];
    try {
        write_lock();
        if ($store === 'files') {
            // Project references are removed atomically with model deletion.
            foreach (db()->query('SELECT id, metadata FROM lens_projects')->fetchAll() as $projectRow) {
                $project = json_decode($projectRow['metadata'], true, 32, JSON_THROW_ON_ERROR);
                $parts = $id === null ? [] : array_values(array_filter($project['parts'], fn($part) => $part['fileId'] !== $id));
                if (count($parts) === count($project['parts'])) continue;
                $project['parts'] = $parts;
                $updateProject = db()->prepare('UPDATE lens_projects SET metadata = ? WHERE id = ?');
                $updateProject->execute([json_encode($project, JSON_THROW_ON_ERROR), $projectRow['id']]);
            }
            $read = db()->prepare('SELECT storage_key FROM lens_files' . ($id === null ? '' : ' WHERE id = ?'));
            $read->execute($id === null ? [] : [$id]);
            foreach ($read->fetchAll() as $row) {
                $keys[] = $row['storage_key'];
            }
        }
        $table = match ($store) { 'files' => 'lens_files', 'collections' => 'lens_collections', 'projects' => 'lens_projects',
            default => throw new HttpError(422, 'Ongeldig opslagtype.') };
        $delete = db()->prepare('DELETE FROM ' . $table . ($id === null ? '' : ' WHERE id = ?'));
        $delete->execute($id === null ? [] : [$id]);
        db()->commit();
    } catch (Throwable $error) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $error;
    }
    // Only remove bytes after a successful database commit. Interrupted cleanup can
    // leave an inaccessible orphan; it cannot make remaining records unreadable.
    foreach ($keys as $key) {
        model_remove($key);
    }
}
