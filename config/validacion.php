<?php
require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/seguridad.php';

iniciarSesionSegura();
// Verificar si el usuario está logueado
if (!isset($_SESSION["SESUSUARIO"]) || !($usuarioVigente = seguridadUsuarioActual($conn))['id_usuario']) {
    $esControlador = strpos(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/controllers/') !== false;
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    if ($esControlador) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status'=>'error', 'message'=>'La sesión no está activa. Inicia sesión nuevamente.']);
        exit;
    }
    // Redireccionar a la página de login si no está logueado
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

$user_role = (int)$usuarioVigente['rol'];
$user_name = $_SESSION['SESUSUARIO'];
$user_lastname = $_SESSION['APELLIDO'];
$user_email = $_SESSION['EMAIL'];
$user_id = $_SESSION['ID'];
$rol = $_SESSION['RNOMBRE'];

?>
