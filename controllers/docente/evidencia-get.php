<?php
// evidencia-get.php
header('Content-Type: application/json; charset=utf-8');
if (!defined('BASE_DIR')) {
  define('BASE_DIR', realpath(__DIR__ . '/../..'));
}
require_once BASE_DIR . '/config/paths.php';
require_once CONFIG_DIR . '/conexion.php';
require_once CONFIG_DIR . '/validacion.php';
require_once CONFIG_DIR . '/evidencias.php';

function jexit($ok, $msg = '', $extra = []) {
  echo json_encode(array_merge(['status' => $ok ? 'ok' : 'error', 'message' => $msg], $extra));
  exit;
}

$usuarioActual = evidenciaUsuarioActual($conn);
if ((int)$usuarioActual['id_usuario'] <= 0) jexit(false, 'La sesión ya no es válida. Inicia sesión nuevamente.');


$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) jexit(false, 'ID inválido.');

$sql = "SELECT id_evidencia, titulo, archivo, id_docente, id_tipo_evidencia
        FROM evidencias
        WHERE id_evidencia = ? AND ocultar = 0";
$stmt = $conn->prepare($sql);
if (!$stmt) jexit(false, 'Error de preparación: '.$conn->error);
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$e = $res->fetch_assoc();
$stmt->close();

if (!$e) jexit(false, 'Evidencia no encontrada.');
if (!evidenciaPuedeEditar($usuarioActual, $e)) jexit(false, 'No tienes permiso para editar esta evidencia.');

jexit(true, '', ['evidencia' => $e]);
