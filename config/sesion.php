<?php
/** Configuración común de sesiones para todos los puntos de entrada web. */

const SESSION_IDLE_TIMEOUT = 1800; // 30 minutos

function iniciarSesionSegura(): bool
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return true;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.gc_maxlifetime', (string)SESSION_IDLE_TIMEOUT);

    $https = isset($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== ''
        && strtolower((string)$_SERVER['HTTPS']) !== 'off';
    if (!$https && isset($_SERVER['SERVER_PORT'])) {
        $https = (int)$_SERVER['SERVER_PORT'] === 443;
    }

    $actual = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $actual['path'] !== '' ? $actual['path'] : '/',
        'domain' => $actual['domain'],
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    if (!session_start()) {
        return false;
    }

    $ahora = time();
    $ultimaActividad = (int)($_SESSION['_LAST_ACTIVITY'] ?? 0);
    if ($ultimaActividad > 0 && ($ahora - $ultimaActividad) > SESSION_IDLE_TIMEOUT) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['_LAST_ACTIVITY'] = $ahora;

    return true;
}

function destruirSesionSegura(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION = [];
    $params = session_get_cookie_params();
    if (ini_get('session.use_cookies')) {
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] !== '' ? $params['path'] : '/',
            'domain' => $params['domain'],
            'secure' => (bool)$params['secure'],
            'httponly' => true,
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}
