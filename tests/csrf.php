<?php
if (PHP_SAPI !== 'cli') {
    exit("Ejecuta esta prueba desde PHP CLI.\n");
}

require_once __DIR__ . '/../config/csrf.php';
if (($argv[1] ?? '') === 'reject') {
    session_save_path($argv[2]);
    session_id(bin2hex(random_bytes(16)));
    iniciarSesionSegura();
    register_shutdown_function(static function (): void {
        echo '::http_status=' . http_response_code();
    });
    csrf_exigir();
}

$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'odea_csrf_' . bin2hex(random_bytes(5));
if (!mkdir($dir, 0700, true)) {
    throw new RuntimeException('No se pudo crear el directorio temporal de sesiones.');
}
session_save_path($dir);
$checks = [];
$assert = static function (bool $ok, string $label) use (&$checks): void {
    if (!$ok) {
        throw new RuntimeException('FALLO: ' . $label);
    }
    $checks[] = 'OK: ' . $label;
};

try {
    session_id(bin2hex(random_bytes(16)));
    iniciarSesionSegura();
    $tokenA = csrf_token();
    $assert((bool)preg_match('/^[a-f0-9]{64}$/', $tokenA), 'token aleatorio de 256 bits');

    $_POST['csrf_token'] = $tokenA;
    $assert(csrf_token_valido(), 'POST de formulario con token válido');
    unset($_POST['csrf_token']);
    unset($_SERVER['HTTP_X_CSRF_TOKEN']);
    $assert(!csrf_token_valido(), 'POST sin token rechazado');
    $_SERVER['HTTP_X_CSRF_TOKEN'] = str_repeat('0', 64);
    $assert(!csrf_token_valido(), 'POST con token incorrecto rechazado');
    $_SERVER['HTTP_X_CSRF_TOKEN'] = $tokenA;
    $assert(csrf_token_valido(), 'solicitud AJAX con cabecera X-CSRF-Token válida');

    $_SESSION['_CSRF_CREATED'] = time() - CSRF_TOKEN_TTL - 1;
    $assert(!csrf_token_valido(), 'token vencido rechazado');
    $tokenRenovado = csrf_token();
    $assert($tokenRenovado !== $tokenA, 'token vencido renovado al volver a cargar la vista');
    session_write_close();

    session_id(bin2hex(random_bytes(16)));
    iniciarSesionSegura();
    $tokenB = csrf_token();
    $_SERVER['HTTP_X_CSRF_TOKEN'] = $tokenA;
    $assert(!csrf_token_valido(), 'token de otra sesión rechazado');
    $_SERVER['HTTP_X_CSRF_TOKEN'] = $tokenB;
    $assert(csrf_token_valido(), 'token propio de la segunda sesión aceptado');
    $idB = session_id();
    destruirSesionSegura();

    session_id($idB);
    iniciarSesionSegura();
    $_SERVER['HTTP_X_CSRF_TOKEN'] = $tokenB;
    $assert(!csrf_token_valido(), 'token de sesión destruida rechazado');
    destruirSesionSegura();

    $runner = proc_open([PHP_BINARY, __FILE__, 'reject', $dir], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($runner);
    $parts = explode('::http_status=', $stdout, 2);
    $json = json_decode($parts[0] ?? '', true);
    $assert($status === 0 && $json['code'] === 'csrf_invalid' && $json['status'] === 'error', 'rechazo AJAX usa JSON consistente');
    $assert((int)($parts[1] ?? 0) === 419 && str_contains($stdout, 'Recarga la página') && $stderr === '', 'respuesta CSRF retorna HTTP 419 e instrucción de recuperación');

    foreach ($checks as $check) {
        echo $check . PHP_EOL;
    }
    echo count($checks) . " pruebas correctas.\n";
} finally {
    if (session_status() === PHP_SESSION_ACTIVE) {
        destruirSesionSegura();
    }
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    rmdir($dir);
}
