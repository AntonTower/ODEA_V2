<?php
session_start(); // Inicia la sesión

// Definición de constantes de rutas
if (!defined('CONFIG_DIR')) {
    define('BASE_DIR', realpath(__DIR__ . '/..'));
    define('CONFIG_DIR', BASE_DIR . '/config');
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/ODEA/gestor_evidencias');
}

// Carga de la conexión a la base de datos
require_once CONFIG_DIR . '/conexion.php';

// === Normalizar entradas ===
$correo = isset($_POST['email']) ? trim(strtolower($_POST['email'])) : '';
$password = $_POST['password'] ?? '';

// Si faltan datos, vuelve al login
if ($correo === '' || $password === '') {
    header('Location: login.php?err=1');
    exit();
}

// Consulta preparada
$sqltxt = "SELECT u.id_usuario, u.nombre, u.apellidop, u.apellidom, u.correo, u.password, u.rol, u.activo, r.nombre as nombrerol
FROM usuarios u 
LEFT JOIN roles r ON r.id_rol = u.rol
WHERE u.correo = ?";

$stmt = $conn->prepare($sqltxt);
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (password_verify($password, $row['password'])) {
        // Prevenir fijación de sesión
        session_regenerate_id(true);

        $_SESSION['ID'] = $row['id_usuario'];
        $_SESSION['SESUSUARIO'] = $row['nombre'];
        $_SESSION['APELLIDO'] = $row['apellidop'] . " " . $row['apellidom'];
        $_SESSION['EMAIL'] = $row['correo'];
        $_SESSION['ROL'] = $row['rol'];
        $_SESSION['RNOMBRE'] = $row['nombrerol'];
        $_SESSION['MENU'] = 1;

        // Redirección al Dashboard principal
        header('Location: ' . BASE_URL . '/views/comunes/index.php');
        exit();
    }
}

// Credenciales inválidas
header('Location: login.php?err=1');
exit();