<?php
// usuario-get.php
header('Content-Type: application/json; charset=utf-8');
if (!defined('BASE_DIR')) {
  define('BASE_DIR', realpath(__DIR__ . '/../..'));
}
require_once BASE_DIR . '/config/paths.php';
require_once CONFIG_DIR . '/conexion.php';
require_once BASE_DIR . '/views/evaluador/permisos-instrumentos.php';
exigirGestion($conn, 'gestion-usuarios.php');

function jexit($ok, $msg = '', $extra = []) {
  echo json_encode(array_merge(['status' => $ok ? 'ok' : 'error', 'message' => $msg], $extra));
  exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) jexit(false, 'ID inválido');

$sql = "SELECT id_usuario, nombre, apellidop, apellidom, correo, rol, activo
        FROM usuarios WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
  $row['instrumentos'] = [];
  if ((int)$row['rol'] === 3) {
    $rel = $conn->prepare('SELECT id_instrumento FROM evaluador_instrumento WHERE id_evaluador=?');
    $rel->bind_param('i', $id);
    $rel->execute();
    $row['instrumentos'] = array_map('intval', array_column($rel->get_result()->fetch_all(MYSQLI_ASSOC), 'id_instrumento'));
    $rel->close();
  }
  jexit(true, '', ['user' => $row]);
}
jexit(false, 'Usuario no encontrado');
