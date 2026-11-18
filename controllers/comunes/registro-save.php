<?php
require_once __DIR__ . '/../../config/paths.php';
require_once CONFIG_DIR . '/csrf.php';
require_once CONFIG_DIR . '/autenticacion.php';
require_once CONFIG_DIR . '/conexion.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ' . BASE_URL . '/public/registro.php');
    exit();
}
if (!csrf_token_valido()) {
    header('Location: ' . BASE_URL . '/public/registro.php?err=csrf');
    exit();
}

$resultado = registrarDocente($conn, $_POST);
if ($resultado['status'] === 'pending') {
    header('Location: ' . BASE_URL . '/public/registro.php?msg=pending');
    exit();
}

$codigo = $resultado['code'] ?? 'database';
error_log('Registro docente rechazado: ' . $codigo);
header('Location: ' . BASE_URL . '/public/registro.php?err=' . rawurlencode($codigo));
exit();
