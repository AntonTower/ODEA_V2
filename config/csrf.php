<?php
require_once __DIR__ . '/sesion.php';

const CSRF_TOKEN_TTL = 7200;

/** Token ligado a la sesión; estable entre pestañas durante la vigencia de la sesión. */
function csrf_token(): string
{
    iniciarSesionSegura();
    $token = $_SESSION['_CSRF_TOKEN'] ?? '';
    $creado = (int)($_SESSION['_CSRF_CREATED'] ?? 0);

    if (!is_string($token) || strlen($token) !== 64 || $creado <= 0 || (time() - $creado) > CSRF_TOKEN_TTL) {
        $token = bin2hex(random_bytes(32));
        $_SESSION['_CSRF_TOKEN'] = $token;
        $_SESSION['_CSRF_CREATED'] = time();
    }

    return $token;
}

/** Valida el token enviado por formulario o por X-CSRF-Token. */
function csrf_token_valido(): bool
{
    iniciarSesionSegura();
    $esperado = $_SESSION['_CSRF_TOKEN'] ?? '';
    $creado = (int)($_SESSION['_CSRF_CREATED'] ?? 0);

    if (!is_string($esperado) || strlen($esperado) !== 64 || $creado <= 0 || (time() - $creado) > CSRF_TOKEN_TTL) {
        unset($_SESSION['_CSRF_TOKEN'], $_SESSION['_CSRF_CREATED']);
        return false;
    }

    $recibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    return is_string($recibido) && strlen($recibido) === 64 && hash_equals($esperado, $recibido);
}

/** Rechazo uniforme para todos los endpoints AJAX mutables. */
function csrf_exigir(): void
{
    if (csrf_token_valido()) {
        return;
    }

    http_response_code(419);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'error',
        'code' => 'csrf_invalid',
        'message' => 'La sesión o el token de seguridad venció. Recarga la página e inténtalo nuevamente.',
    ], JSON_UNESCAPED_UNICODE);
    exit();
}
