<?php
/** Pruebas aisladas de autenticación y registro. Solo crea/elimina su BD y sesiones temporales. */
if (PHP_SAPI !== 'cli') {
    exit("Ejecuta esta prueba desde PHP CLI.\n");
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../config/autenticacion.php';

$servidor = new mysqli('127.0.0.1', 'root', '', '', 3308);
$servidor->set_charset('utf8mb4');
$nombreDb = 'odea_auth_test_' . bin2hex(random_bytes(5));
$sesionDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $nombreDb;
$creada = false;
$resultados = [];
$comprobar = static function (bool $ok, string $mensaje) use (&$resultados): void {
    if (!$ok) {
        throw new RuntimeException('FALLO: ' . $mensaje);
    }
    $resultados[] = 'OK: ' . $mensaje;
};

try {
    $servidor->query("CREATE DATABASE `$nombreDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $creada = true;
    $db = new mysqli('127.0.0.1', 'root', '', $nombreDb, 3308);
    $db->set_charset('utf8mb4');
    $db->query("CREATE TABLE roles (
        id_rol int NOT NULL PRIMARY KEY,
        nombre varchar(50) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE usuarios (
        id_usuario int NOT NULL AUTO_INCREMENT PRIMARY KEY,
        nombre varchar(100) NOT NULL,
        apellidop varchar(100) NOT NULL,
        apellidom varchar(100) NOT NULL,
        correo varchar(150) NOT NULL UNIQUE,
        password varchar(255) NOT NULL,
        rol int NOT NULL,
        activo tinyint(1) NOT NULL DEFAULT 1,
        created_at timestamp NOT NULL DEFAULT current_timestamp(),
        updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("INSERT INTO roles (id_rol, nombre) VALUES
        (1, 'Super Usuario'), (2, 'Administrador'), (3, 'Evaluador'), (4, 'Docente')");

    $hash = password_hash('Clave-activa-prueba-2026', PASSWORD_DEFAULT);
    $insertar = $db->prepare('INSERT INTO usuarios (nombre, apellidop, apellidom, correo, password, rol, activo) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $nombre = 'Docente';
    $apellidoP = 'Sintetico';
    $apellidoM = 'Activo';
    $correo = 'docente.activo@example.invalid';
    $rol = 4;
    $activo = 1;
    $insertar->bind_param('sssssii', $nombre, $apellidoP, $apellidoM, $correo, $hash, $rol, $activo);
    $insertar->execute();
    $usuarioActivoId = (int)$insertar->insert_id;
    $nombreInactivo = 'Docente inactivo';
    $correoInactivo = 'docente.inactivo@example.invalid';
    $activo = 0;
    $insertar->bind_param('sssssii', $nombreInactivo, $apellidoP, $apellidoM, $correoInactivo, $hash, $rol, $activo);
    $insertar->execute();
    $insertar->close();

    $usuario = autenticarUsuario($db, ' DOCENTE.ACTIVO@EXAMPLE.INVALID ', 'Clave-activa-prueba-2026');
    $comprobar($usuario !== null && (int)$usuario['id_usuario'] === $usuarioActivoId, 'usuario activo con contraseña correcta');
    $comprobar(autenticarUsuario($db, $correo, 'incorrecta') === null, 'usuario activo con contraseña incorrecta');
    $comprobar(autenticarUsuario($db, $correoInactivo, 'Clave-activa-prueba-2026') === null, 'usuario inactivo con contraseña correcta');
    $comprobar(autenticarUsuario($db, 'no.existe@example.invalid', 'Clave-activa-prueba-2026') === null, 'correo inexistente');

    $registro = [
        'nombre' => 'Cuenta', 'apellidop' => 'Prueba', 'apellidom' => 'Registro',
        'email' => 'nueva.docente@example.invalid',
        'password' => 'Clave-nueva-segura-2026',
        'password_confirm' => 'Clave-nueva-segura-2026',
        'rol' => 1,
    ];
    $alta = registrarDocente($db, $registro);
    $comprobar($alta['status'] === 'pending', 'registro con correo nuevo queda pendiente de activación');
    $st = $db->prepare('SELECT rol, activo, password FROM usuarios WHERE id_usuario = ?');
    $st->bind_param('i', $alta['id']);
    $st->execute();
    $registrado = $st->get_result()->fetch_assoc();
    $st->close();
    $comprobar((int)$registrado['rol'] === 4, 'rol enviado por el cliente no sustituye el rol Docente');
    $comprobar((int)$registrado['activo'] === 0, 'cuenta registrada no puede autenticarse antes de activación');
    $comprobar(password_verify($registro['password'], $registrado['password']), 'contraseña de registro almacenada como hash');
    $comprobar(registrarDocente($db, $registro)['code'] === 'exists', 'registro con correo duplicado');

    if (!mkdir($sesionDir, 0700, true)) {
        throw new RuntimeException('No se pudo crear directorio temporal de sesiones.');
    }
    session_save_path($sesionDir);
    $_SERVER['HTTPS'] = 'off';
    $_SERVER['SERVER_PORT'] = '80';
    session_id(str_repeat('a', 32));
    iniciarSesionSegura();
    $cookie = session_get_cookie_params();
    $comprobar((bool)$cookie['httponly'] && ($cookie['samesite'] ?? '') === 'Lax' && !$cookie['secure'], 'cookies HttpOnly/SameSite y Secure desactivado en HTTP local');
    $idAnterior = session_id();
    $usuarioSesion = autenticarUsuario($db, $correo, 'Clave-activa-prueba-2026');
    $comprobar($usuarioSesion !== null && crearSesionAutenticada($usuarioSesion), 'creación de sesión tras autenticar');
    $idNuevo = session_id();
    $comprobar($idNuevo !== $idAnterior, 'regeneración del identificador de sesión');
    $comprobar(!is_file($sesionDir . DIRECTORY_SEPARATOR . 'sess_' . $idAnterior), 'sesión anterior invalidada tras regenerar identificador');
    $comprobar((int)$_SESSION['ID'] === $usuarioActivoId, 'sesión contiene identidad autenticada completa');

    session_write_close();
    session_id($idNuevo);
    iniciarSesionSegura();
    destruirSesionSegura();
    $comprobar(session_status() === PHP_SESSION_NONE, 'cierre destruye la sesión activa');
    session_id($idNuevo);
    iniciarSesionSegura();
    $comprobar(!isset($_SESSION['SESUSUARIO'], $_SESSION['ID']), 'acceso posterior al cierre no conserva autenticación');
    destruirSesionSegura();

    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = '443';
    session_id(str_repeat('b', 32));
    iniciarSesionSegura();
    $cookieHttps = session_get_cookie_params();
    $comprobar((bool)$cookieHttps['secure'] && (bool)$cookieHttps['httponly'] && ($cookieHttps['samesite'] ?? '') === 'Lax', 'cookie Secure habilitada bajo HTTPS');
    $_SESSION['SESUSUARIO'] = 'Debe vencer';
    $_SESSION['_LAST_ACTIVITY'] = time() - SESSION_IDLE_TIMEOUT - 1;
    $idExpirado = session_id();
    session_write_close();
    session_id($idExpirado);
    iniciarSesionSegura();
    $comprobar(session_id() !== $idExpirado && !isset($_SESSION['SESUSUARIO']), 'sesión anterior expira tras 30 minutos de inactividad');
    destruirSesionSegura();

    foreach ($resultados as $resultado) {
        echo $resultado . PHP_EOL;
    }
    echo count($resultados) . " pruebas correctas.\n";
} finally {
    if (session_status() === PHP_SESSION_ACTIVE) {
        destruirSesionSegura();
    }
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
    if ($creada) {
        $servidor->query("DROP DATABASE `$nombreDb`");
    }
    if (is_dir($sesionDir)) {
        foreach (glob($sesionDir . DIRECTORY_SEPARATOR . '*') ?: [] as $archivo) {
            if (is_file($archivo)) {
                unlink($archivo);
            }
        }
        rmdir($sesionDir);
    }
    $servidor->close();
}
