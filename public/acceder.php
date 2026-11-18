<?php
require_once __DIR__ . '/../config/paths.php';
require_once CONFIG_DIR . '/csrf.php';
require_once CONFIG_DIR . '/autenticacion.php';
require_once CONFIG_DIR . '/conexion.php';

iniciarSesionSegura();
if (!csrf_token_valido()) {
    header('Location: ' . BASE_URL . '/public/login.php?csrf=1');
    exit();
}
// Un intento nuevo no debe conservar datos de autenticación anteriores.
$_SESSION = [];

$correo = isset($_POST['email']) ? (string)$_POST['email'] : '';
$password = isset($_POST['password']) ? (string)$_POST['password'] : '';
$usuario = autenticarUsuario($conn, $correo, $password);

if ($usuario !== null && crearSesionAutenticada($usuario)) {
    header('Location: ' . BASE_URL . '/views/comunes/index.php');
    exit();
}

// Respuesta idéntica para correo inexistente, contraseña incorrecta y cuenta inactiva.
header('Location: ' . BASE_URL . '/public/login.php?err=1');
exit();
