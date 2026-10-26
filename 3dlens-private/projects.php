<?php
declare(strict_types=1);
if (!defined('THREEDLENS_BOOTSTRAPPED')) { http_response_code(404); exit; }

function ensure_projects_schema(): void
{
    // Only called after login by the archive API, outside a write transaction.
    try {
        db()->query('SELECT id FROM lens_projects LIMIT 0');
        return;
    } catch (PDOException $error) {
        if ($error->getCode() !== '42S02') throw $error;
    }
    db()->exec((string) file_get_contents(__DIR__ . '/migrations/003-projects.sql'));
}

function validated_project(array $input): array
{
    $id = input_id($input['id'] ?? null);
    $name = trim(bounded_text($input['name'] ?? null, 200, 'projectnaam'));
    if ($name === '') throw new HttpError(422, 'Geef het project een naam.');
    $parts = $input['parts'] ?? [];
    if (!is_array($parts) || !array_is_list($parts) || count($parts) > 500) {
        throw new HttpError(422, 'Gebruik maximaal 500 onderdelen per project.');
    }
    $seen = [];
    $clean = [];
    foreach ($parts as $part) {
        if (!is_array($part)) throw new HttpError(422, 'Ongeldig projectonderdeel.');
        $fileId = input_id($part['fileId'] ?? null);
        $quantity = $part['quantity'] ?? 1;
        if (isset($seen[$fileId]) || !is_int($quantity) || $quantity < 1 || $quantity > 9999) {
            throw new HttpError(422, 'Gebruik unieke onderdelen met een aantal van 1 tot 9999.');
        }
        $seen[$fileId] = true;
        $clean[] = ['fileId' => $fileId, 'quantity' => $quantity,
            'notes' => bounded_text($part['notes'] ?? '', 2000, 'onderdeelnotities')];
    }
    return ['id' => $id, 'name' => $name,
        'description' => bounded_text($input['description'] ?? '', 8000, 'montagenotities'), 'parts' => $clean];
}

function list_projects(): array
{
    return array_map(fn(array $row): array => json_decode($row['metadata'], true, 32, JSON_THROW_ON_ERROR),
        db()->query('SELECT metadata FROM lens_projects ORDER BY name, id')->fetchAll());
}

function save_project(array $input): array
{
    $project = validated_project($input);
    try {
        write_lock();
        $find = db()->prepare('SELECT id FROM lens_files WHERE id = ?');
        foreach ($project['parts'] as $part) {
            $find->execute([$part['fileId']]);
            if (!$find->fetch()) throw new HttpError(422, 'Een onderdeel bestaat niet meer. Vernieuw het archief.');
        }
        $save = db()->prepare('INSERT INTO lens_projects (id, name, metadata) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), metadata = VALUES(metadata)');
        $save->execute([$project['id'], $project['name'], json_encode($project, JSON_THROW_ON_ERROR)]);
        db()->commit();
        return $project;
    } catch (Throwable $error) {
        if (db()->inTransaction()) db()->rollBack();
        throw $error;
    }
}
