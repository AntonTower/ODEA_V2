<?php
require_once __DIR__ . '/sesion.php';

/** Devuelve datos del usuario solo si las credenciales pertenecen a una cuenta activa. */
function autenticarUsuario(mysqli $conn, string $correo, string $password): ?array
{
    $correo = mb_strtolower(trim($correo), 'UTF-8');
    if ($correo === '' || $password === '') {
        return null;
    }

    $sql = 'SELECT u.id_usuario, u.nombre, u.apellidop, u.apellidom,
                   u.correo, u.password, u.rol, r.nombre AS nombrerol
            FROM usuarios u
            INNER JOIN roles r ON r.id_rol = u.rol
            WHERE u.correo = ? AND u.activo = 1
            LIMIT 1';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $correo);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$usuario) {
        // Igualar aproximadamente el costo de verificación cuando no hay cuenta activa.
        static $hashInexistente = null;
        if ($hashInexistente === null) {
            $hashInexistente = password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT);
        }
        password_verify($password, $hashInexistente);
        return null;
    }
    if (!password_verify($password, (string)$usuario['password'])) {
        return null;
    }

    unset($usuario['password']);
    return $usuario;
}

/** Crea una sesión autenticada limpia y rota el identificador contra fijación. */
function crearSesionAutenticada(array $usuario): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE && !iniciarSesionSegura()) {
        return false;
    }

    $_SESSION = [];
    if (!session_regenerate_id(true)) {
        return false;
    }

    $_SESSION['ID'] = (int)$usuario['id_usuario'];
    $_SESSION['SESUSUARIO'] = (string)$usuario['nombre'];
    $_SESSION['APELLIDO'] = trim((string)$usuario['apellidop'] . ' ' . (string)$usuario['apellidom']);
    $_SESSION['EMAIL'] = (string)$usuario['correo'];
    $_SESSION['ROL'] = (int)$usuario['rol'];
    $_SESSION['RNOMBRE'] = (string)$usuario['nombrerol'];
    $_SESSION['MENU'] = 1;
    $_SESSION['_LAST_ACTIVITY'] = time();

    return true;
}

/** Registra siempre como cuenta pendiente, con el rol Docente leído del catálogo. */
function registrarDocente(mysqli $conn, array $datos): array
{
    $nombre = trim((string)($datos['nombre'] ?? ''));
    $apellidoP = trim((string)($datos['apellidop'] ?? ''));
    $apellidoM = trim((string)($datos['apellidom'] ?? ''));
    $correo = mb_strtolower(trim((string)($datos['email'] ?? '')), 'UTF-8');
    $password = (string)($datos['password'] ?? '');
    $confirmacion = (string)($datos['password_confirm'] ?? '');

    if ($nombre === '' || $apellidoP === '' || $correo === '' || $password === '' || $confirmacion === '') {
        return ['status' => 'error', 'code' => 'empty'];
    }
    if (mb_strlen($nombre, 'UTF-8') > 100 || mb_strlen($apellidoP, 'UTF-8') > 100 || mb_strlen($apellidoM, 'UTF-8') > 100) {
        return ['status' => 'error', 'code' => 'invalid'];
    }
    if (mb_strlen($correo, 'UTF-8') > 150 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        return ['status' => 'error', 'code' => 'invalid'];
    }
    if ($password !== $confirmacion || strlen($password) < 12 || strlen($password) > 72) {
        return ['status' => 'error', 'code' => 'password'];
    }

    $rolStmt = $conn->prepare('SELECT id_rol FROM roles WHERE nombre = ? LIMIT 1');
    $nombreRol = 'Docente';
    $rolStmt->bind_param('s', $nombreRol);
    $rolStmt->execute();
    $rol = $rolStmt->get_result()->fetch_assoc();
    $rolStmt->close();
    if (!$rol) {
        return ['status' => 'error', 'code' => 'configuration'];
    }

    $correoStmt = $conn->prepare('SELECT 1 FROM usuarios WHERE correo = ? LIMIT 1');
    $correoStmt->bind_param('s', $correo);
    $correoStmt->execute();
    $correoExiste = $correoStmt->get_result()->num_rows > 0;
    $correoStmt->close();
    if ($correoExiste) {
        return ['status' => 'error', 'code' => 'exists'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    if ($hash === false) {
        return ['status' => 'error', 'code' => 'database'];
    }

    try {
        $sql = 'INSERT INTO usuarios
                    (nombre, apellidop, apellidom, correo, password, rol, activo)
                VALUES (?, ?, ?, ?, ?, ?, 0)';
        $stmt = $conn->prepare($sql);
        $rolId = (int)$rol['id_rol'];
        $stmt->bind_param('sssssi', $nombre, $apellidoP, $apellidoM, $correo, $hash, $rolId);
        if (!$stmt->execute()) {
            $errno = (int)$stmt->errno;
            $stmt->close();
            if ($errno === 1062) {
                return ['status' => 'error', 'code' => 'exists'];
            }
            error_log('Error al insertar cuenta docente; mysqli errno=' . $errno);
            return ['status' => 'error', 'code' => 'database'];
        }
        $id = (int)$stmt->insert_id;
        $stmt->close();

        return ['status' => 'pending', 'id' => $id];
    } catch (mysqli_sql_exception $e) {
        if ((int)$e->getCode() === 1062) {
            return ['status' => 'error', 'code' => 'exists'];
        }
        error_log('Error al registrar cuenta docente: ' . $e->getMessage());
        return ['status' => 'error', 'code' => 'database'];
    }
}
