<?php
declare(strict_types=1);
require dirname(__DIR__) . '/3dlens-private/bootstrap.php';
require dirname(__DIR__) . '/3dlens-private/auth-view.php';

$setupKey = config('setup_key');
if (!is_string($setupKey) || strlen($setupKey) < 32) {
    throw new HttpError(404, 'De installatiepagina is niet beschikbaar.');
}
try {
    $exists = (bool) db()->query('SELECT id FROM lens_users LIMIT 1')->fetch();
} catch (PDOException $error) {
    // Only a genuinely missing table is an expected first-install state.
    if (($error->errorInfo[1] ?? 0) !== 1146) {
        throw $error;
    }
    $exists = false;
}
if ($exists) {
    throw new HttpError(404, 'De installatiepagina is niet beschikbaar.');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (!hash_equals($setupKey, post_string('setup_key'))) {
        http_response_code(403);
        $error = 'De installatiesleutel klopt niet.';
    } else {
        $username = strtolower(trim(post_string('username')));
        $password = post_string('password');
        if (!preg_match('/\A[a-z0-9_.@-]{3,100}\z/', $username)) {
            $error = 'Kies een gebruikersnaam van 3 tot 100 letters, cijfers of . _ @ -.';
        } elseif (strlen($password) < 14 || strlen($password) > 72) {
            $error = 'Kies een uniek wachtwoord van minimaal 14 en maximaal 72 bytes (gewone tekens tellen als één byte).';
        } elseif (!hash_equals($password, post_string('password_confirmation'))) {
            $error = 'De twee wachtwoorden zijn niet gelijk.';
        } else {
            storage_path();
            // No public registration. Primary key 1 permits only one owner, even under concurrent setup requests.
            $schema = file_get_contents(dirname(__DIR__) . '/3dlens-private/schema.sql');
            foreach (explode(';', $schema) as $statement) {
                if (trim($statement) !== '') {
                    db()->exec($statement);
                }
            }
            try {
                $insert = db()->prepare('INSERT INTO lens_users (id, username, password_hash) VALUES (1, ?, ?)');
                $insert->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            } catch (PDOException $exception) {
                if (($exception->errorInfo[1] ?? 0) === 1062) {
                    throw new HttpError(409, 'Het eigenaarsaccount bestaat al. Je kunt inloggen.');
                }
                throw $exception;
            }
            header('Location: login.php', true, 303);
            exit;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    throw new HttpError(405, 'Gebruik het installatieformulier.');
}
auth_page('Maak je eigenaarsaccount', 'Gebruik de eenmalige installatiesleutel uit je privéconfiguratie. Na installatie wordt deze pagina geblokkeerd.', $error,
    '<label for="setup_key">Installatiesleutel</label><input type="password" id="setup_key" name="setup_key" autocomplete="off" required>
     <label for="username">Gebruikersnaam</label><input id="username" name="username" autocomplete="username" minlength="3" maxlength="100" required>
     <label for="password">Wachtwoord (minimaal 14 tekens)</label><input type="password" id="password" name="password" autocomplete="new-password" minlength="14" required>
     <label for="password_confirmation">Herhaal wachtwoord</label><input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>',
    'Account aanmaken');
