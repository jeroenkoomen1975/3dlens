<?php
declare(strict_types=1);
if (!defined('THREEDLENS_BOOTSTRAPPED')) { http_response_code(404); exit; }

function recovery_gate(string $path): array
{
    if (!is_file($path)) throw new HttpError(404, 'De herstelpagina is niet beschikbaar.');
    $gate = json_decode((string) file_get_contents($path), true);
    if (!is_array($gate) || !is_int($gate['expires'] ?? null) || $gate['expires'] < time() ||
        $gate['expires'] > time() + 1800 || !is_string($gate['key_hash'] ?? null) ||
        !preg_match('/\A[a-f0-9]{64}\z/', $gate['key_hash'])) {
        throw new HttpError(404, 'De herstelpagina is verlopen of niet beschikbaar.');
    }
    return $gate;
}

function recovery_validate(array $gate, string $key, string $password, string $confirmation): void
{
    if (strlen($key) !== 64 || !hash_equals($gate['key_hash'], hash('sha256', $key))) {
        throw new HttpError(403, 'De eenmalige herstelsleutel klopt niet.');
    }
    if (strlen($password) < 14 || strlen($password) > 72 || str_contains($password, "\0")) {
        throw new HttpError(422, 'Kies een uniek wachtwoord van minimaal 14 en maximaal 72 bytes.');
    }
    if (!hash_equals($password, $confirmation)) throw new HttpError(422, 'De twee wachtwoorden zijn niet gelijk.');
}
