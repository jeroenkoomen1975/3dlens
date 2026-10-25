<?php
declare(strict_types=1);
if (!defined('THREEDLENS_BOOTSTRAPPED')) { http_response_code(404); exit; }
require_once __DIR__ . '/onedrive.php';

function model_open(array $row): mixed
{
    $path = stored_file_path($row['storage_key']);
    $reference = od_reference($row['storage_key']);
    if ($reference !== null) {
        if (($reference['sha256'] ?? '') !== $row['sha256'] || (int) ($reference['size'] ?? -1) !== (int) $row['byte_size']) {
            throw new HttpError(503, 'De OneDrive-verwijzing hoort niet bij dit model.');
        }
        try { return od_download($reference); }
        catch (HttpError $error) {
            // During migration a verified local mirror can bridge a cloud outage.
            if (!is_file($path) || filesize($path) !== (int) $row['byte_size'] ||
                !hash_equals($row['sha256'], hash_file('sha256', $path))) throw $error;
        }
    }
    $handle = @fopen($path, 'rb');
    if (!$handle) throw new HttpError(503, 'Het opgeslagen model is tijdelijk niet beschikbaar.');
    return $handle;
}

/** Called only after the matching database deletion commits, or for a failed new insert. */
function model_remove(string $key): void
{
    $reference = od_reference($key);
    if ($reference !== null) {
        try {
            od_graph('DELETE', od_item_path($reference));
            unlink(od_reference_path($key));
        } catch (HttpError $error) {
            if ($error->status === 404) {
                unlink(od_reference_path($key));
            } else {
                // Retain a retryable private record if OneDrive is unavailable.
                $reference['pending_delete'] = true;
                od_write_reference($key, $reference);
                error_log('3DLens: OneDrive recycle-bin operation is pending.');
            }
        }
    }
    $path = stored_file_path($key);
    if (is_file($path) && !unlink($path)) error_log('3DLens: local model cleanup is pending.');
}

function model_storage_counts(): array
{
    $counts = ['local' => 0, 'onedrive' => 0, 'local_copies' => 0, 'local_copy_bytes' => 0, 'pending_delete' => 0];
    foreach (db()->query('SELECT storage_key, byte_size FROM lens_files')->fetchAll() as $row) {
        $remote = is_file(od_reference_path($row['storage_key']));
        $local = is_file(stored_file_path($row['storage_key']));
        $counts[$remote ? 'onedrive' : 'local']++;
        if ($remote && $local) { $counts['local_copies']++; $counts['local_copy_bytes'] += (int) $row['byte_size']; }
    }
    foreach (new DirectoryIterator(storage_path()) as $file) {
        if (!$file->isFile() || !preg_match('/\A([a-f0-9]{64})\.bin\.onedrive\.json\z/', $file->getFilename(), $match)) continue;
        if (!empty(od_reference($match[1])['pending_delete'])) $counts['pending_delete']++;
    }
    return $counts;
}

/** One file per request so large archives can resume safely after an interruption. */
function model_migration_step(string $mode): array
{
    if (!od_active()) throw new HttpError(409, 'Koppel eerst OneDrive.');
    if (!in_array($mode, ['copy', 'cleanup'], true)) throw new HttpError(422, 'Ongeldige overzetactie.');
    try {
        write_lock();
        $rows = db()->query('SELECT * FROM lens_files ORDER BY added, id')->fetchAll();
        foreach ($rows as $row) {
            $key = $row['storage_key'];
            $reference = od_reference($key);
            $path = stored_file_path($key);
            if ($mode === 'copy' && $reference === null) {
                if (!is_file($path) || filesize($path) !== (int) $row['byte_size'] ||
                    !hash_equals($row['sha256'], hash_file('sha256', $path))) {
                    throw new HttpError(503, 'Een lokaal model ontbreekt of is gewijzigd. Overzetten is gestopt; er is niets opgeruimd.');
                }
                od_upload($key, $row['name'], $path, (int) $row['byte_size'], $row['sha256']);
                db()->commit();
                return ['done' => false, 'name' => $row['name'], 'counts' => model_storage_counts()];
            }
            if ($mode === 'cleanup' && $reference !== null && is_file($path)) {
                if (!hash_equals($row['sha256'], $reference['sha256']) || (int) $reference['size'] !== (int) $row['byte_size']) {
                    throw new HttpError(503, 'De bestandsverwijzing klopt niet. De lokale kopie is behouden.');
                }
                $verified = od_download($reference);
                fclose($verified);
                if (!unlink($path)) throw new HttpError(503, 'De gecontroleerde lokale kopie kon niet worden opgeruimd.');
                db()->commit();
                return ['done' => false, 'name' => $row['name'], 'counts' => model_storage_counts()];
            }
        }
        db()->commit();
        return ['done' => true, 'counts' => model_storage_counts()];
    } catch (Throwable $error) {
        if (db()->inTransaction()) db()->rollBack();
        throw $error;
    }
}

function model_retry_deletions(): void
{
    try {
        write_lock();
        foreach (new DirectoryIterator(storage_path()) as $file) {
            if (!$file->isFile() || !preg_match('/\A([a-f0-9]{64})\.bin\.onedrive\.json\z/', $file->getFilename(), $match)) continue;
            $reference = od_reference($match[1]);
            if (empty($reference['pending_delete'])) continue;
            $exists = db()->prepare('SELECT id FROM lens_files WHERE storage_key = ?');
            $exists->execute([$match[1]]);
            if (!$exists->fetch()) model_remove($match[1]);
        }
        db()->commit();
    } catch (Throwable $error) {
        if (db()->inTransaction()) db()->rollBack();
        throw $error;
    }
}
