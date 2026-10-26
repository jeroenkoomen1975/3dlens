<?php
declare(strict_types=1);
define('THREEDLENS_BOOTSTRAPPED', true);
final class HttpError extends RuntimeException {
    public function __construct(public readonly int $status, string $message) { parent::__construct($message); }
}
function input_id(mixed $value): string {
    if (!is_string($value) || !preg_match('/\A[a-zA-Z0-9_-]{1,80}\z/', $value)) throw new HttpError(422, 'Bad ID');
    return $value;
}
require dirname(__DIR__) . '/3dlens-private/repository.php';
require dirname(__DIR__) . '/3dlens-private/projects.php';
function expect(bool $condition): void { if (!$condition) throw new RuntimeException('Assertion failed'); }
function rejects(callable $action): void {
    try { $action(); } catch (HttpError $e) { expect($e->status === 422); return; }
    throw new RuntimeException('Expected rejection');
}
$project = ['id'=>'robot','name'=>' Robot ','description'=>'Mount parts','parts'=>[['fileId'=>'arm','quantity'=>2,'notes'=>'Left + right']]];
$clean = validated_project($project);
expect($clean['name'] === 'Robot' && $clean['parts'][0]['quantity'] === 2);
rejects(fn()=>validated_project([...$project,'name'=>' ']));
rejects(fn()=>validated_project([...$project,'parts'=>[...$project['parts'],...$project['parts']]]));
foreach ([0,-1,1.5,'2',10000] as $bad) {
    rejects(fn()=>validated_project([...$project,'parts'=>[['fileId'=>'arm','quantity'=>$bad]]]));
}
rejects(fn()=>validated_project([...$project,'parts'=>[['fileId'=>'../secret']]]));
rejects(fn()=>validated_project([...$project,'description'=>str_repeat('x',8001)]));
rejects(fn()=>validated_project([...$project,'parts'=>array_fill(0,501,$project['parts'][0])]));
expect(validated_project([...$project,'parts'=>[]])['parts'] === []);
expect(validated_model_check(['checked'=>1,'boundaryEdges'=>3])['boundaryEdges'] === 3);
rejects(fn()=>validated_model_check(['boundaryEdges'=>-1]));
rejects(fn()=>validated_model_check(['boundaryEdges'=>'3']));
echo "Project and model-check validation: OK\n";

// SQLite adapts MySQL's upsert and row lock only for isolated transaction tests.
if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    echo "SKIP: database transaction tests require pdo_sqlite.\n";
    exit;
}
class ProjectTestDatabase extends PDO {
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false {
        return parent::query(str_replace(' FOR UPDATE', '', $query));
    }
    public function prepare(string $query, array $options = []): PDOStatement|false {
        return parent::prepare(str_replace('ON DUPLICATE KEY UPDATE name = VALUES(name), metadata = VALUES(metadata)',
            'ON CONFLICT(id) DO UPDATE SET name = excluded.name, metadata = excluded.metadata', $query), $options);
    }
}
$database = new ProjectTestDatabase('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
function db(): PDO { global $database; return $database; }
$fixture = sys_get_temp_dir() . '/lens-project-' . bin2hex(random_bytes(8));
mkdir($fixture,0700);
function stored_file_path(string $key): string { global $fixture; return $fixture . '/' . $key . '.bin'; }
$database->exec('CREATE TABLE lens_users (id INTEGER PRIMARY KEY); INSERT INTO lens_users VALUES (1);
    CREATE TABLE lens_files (id TEXT PRIMARY KEY, storage_key TEXT);
    CREATE TABLE lens_projects (id TEXT PRIMARY KEY, name TEXT, metadata TEXT)');
try {
    $key = str_repeat('a',64);
    $database->prepare('INSERT INTO lens_files VALUES (?,?)')->execute(['arm',$key]);
    file_put_contents(stored_file_path($key),'test model bytes');
    ensure_projects_schema(); // Existing schema must work without CREATE/DDL.
    $saved = save_project($project);
    expect(list_projects()[0]['parts'][0]['quantity'] === 2);
    $saved['parts'][0]['quantity'] = 3;
    save_project($saved);
    expect(list_projects()[0]['parts'][0]['quantity'] === 3);
    rejects(fn()=>save_project([...$project,'id'=>'missing','parts'=>[['fileId'=>'absent','quantity'=>1]]]));
    expect(count(list_projects()) === 1 && !db()->inTransaction());
    save_project([...$project,'id'=>'second']);
    delete_records('projects','second');
    expect(count(list_projects()) === 1 && is_file(stored_file_path($key)));
    // A failed model deletion must roll project references back too.
    $database->exec("CREATE TRIGGER deny_delete BEFORE DELETE ON lens_files BEGIN SELECT RAISE(ABORT, 'test rollback'); END");
    try { delete_records('files','arm'); throw new RuntimeException('Expected DB failure'); }
    catch (PDOException) { expect(count(list_projects()[0]['parts']) === 1 && is_file(stored_file_path($key))); }
    $database->exec('DROP TRIGGER deny_delete');
    delete_records('files','arm');
    expect(list_projects()[0]['parts'] === [] && !is_file(stored_file_path($key)));
    expect($database->query('SELECT COUNT(*) FROM lens_files')->fetchColumn() === 0);
    echo "Project save/update, missing references, non-destructive project deletion and atomic model deletion: OK\n";
} finally {
    foreach (glob($fixture . '/*') as $path) unlink($path);
    rmdir($fixture);
}
