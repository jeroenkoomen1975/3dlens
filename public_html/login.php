<?php
declare(strict_types=1);
require dirname(__DIR__) . '/3dlens-private/bootstrap.php';
require dirname(__DIR__) . '/3dlens-private/auth-view.php';
if (logged_in()) {
    header('Location: index.php', true, 303);
    exit;
}
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    throw new HttpError(405, 'Gebruik het inlogformulier.');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $username = strtolower(trim(substr(post_string('username'), 0, 100)));
    $password = post_string('password');
    $now = time();
    $cutoff = $now - 900;
    $cleanup = db()->prepare('DELETE FROM lens_login_attempts WHERE window_start < ?');
    $cleanup->execute([$cutoff]);
    // Atomic counters shared between sessions. Creating a new cookie won't reset them.
    $limited = false;
    foreach ([['ip:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 15], ['user:' . $username, 40]] as [$value, $limit]) {
        $bucket = hash('sha256', $value);
        $count = db()->prepare('INSERT INTO lens_login_attempts (bucket, attempts, window_start) VALUES (?, 1, ?)
            ON DUPLICATE KEY UPDATE attempts = attempts + 1');
        $count->execute([$bucket, $now]);
        $read = db()->prepare('SELECT attempts FROM lens_login_attempts WHERE bucket = ?');
        $read->execute([$bucket]);
        $limited = (int) $read->fetchColumn() > $limit || $limited;
    }
    if ($limited) {
        http_response_code(429);
        header('Retry-After: 900');
        $error = 'Te veel inlogpogingen. Wacht 15 minuten en probeer opnieuw.';
    } else {
        $query = db()->prepare('SELECT id, password_hash FROM lens_users WHERE username = ?');
        $query->execute([$username]);
        $user = $query->fetch();
        // A valid dummy hash keeps unknown usernames on the same slow verification path.
        $hash = $user['password_hash'] ?? '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        $verified = strlen($password) <= 72 && password_verify($password, $hash);
        if ($user && $verified) {
            session_regenerate_id(true);
            $_SESSION = [
                'user_id' => (int) $user['id'], 'started' => $now,
                'last_seen' => $now, 'csrf' => bin2hex(random_bytes(32)),
            ];
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                $user['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                $rehash = db()->prepare('UPDATE lens_users SET password_hash = ? WHERE id = ?');
                $rehash->execute([$user['password_hash'], $user['id']]);
            }
            $_SESSION['auth_hash'] = hash('sha256', $user['password_hash']);
            header('Location: index.php', true, 303);
            exit;
        }
        http_response_code(401);
        $error = 'De gebruikersnaam of het wachtwoord klopt niet.';
    }
}
auth_page('Welkom terug', 'Log in om je modellen en collecties te openen.', $error,
    '<label for="username">Gebruikersnaam</label><input id="username" name="username" autocomplete="username" maxlength="100" required autofocus>
     <label for="password">Wachtwoord</label><input type="password" id="password" name="password" autocomplete="current-password" required>',
    'Inloggen');
