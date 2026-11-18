<?php
header('Content-Type: application/json; charset=utf-8');

if (!defined('BASE_DIR')) {
    define('BASE_DIR', realpath(__DIR__ . '/../..'));
}
require_once BASE_DIR . '/config/paths.php';
require_once CONFIG_DIR . '/conexion.php';
require_once CONFIG_DIR . '/validacion.php';
require_once CONFIG_DIR . '/evidencias.php';

function jexit($ok, $msg = '')
{
    echo json_encode(['status' => $ok ? 'ok' : 'error', 'message' => $msg]);
    exit;
}

$usuario = evidenciaUsuarioActual($conn);
$userId = (int)$usuario['id_usuario'];
$id = (int)($_POST['id_evidencia'] ?? 0);
if ($userId <= 0) jexit(false, 'La sesión ya no es válida. Inicia sesión nuevamente.');
if ($id <= 0) jexit(false, 'ID de evidencia inválido.');

$stmt = $conn->prepare('SELECT id_docente FROM evidencias WHERE id_evidencia = ? AND ocultar = 0');
$stmt->bind_param('i', $id);
$stmt->execute();
$evidencia = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$evidencia) jexit(false, 'Evidencia no encontrada.');
if (!evidenciaPuedeEditar($usuario, $evidencia)) jexit(false, 'No tienes permiso para ocultar esta evidencia.');

$sql = 'UPDATE evidencias SET ocultar = 1 WHERE id_evidencia = ? AND ocultar = 0';
if ((int)$usuario['rol'] === 4) $sql .= ' AND id_docente = ?';
$stmt = $conn->prepare($sql);
if ((int)$usuario['rol'] === 4) {
    $stmt->bind_param('ii', $id, $userId);
} else {
    $stmt->bind_param('i', $id);
}

if (!$stmt->execute() || $stmt->affected_rows !== 1) {
    $stmt->close();
    jexit(false, 'No se pudo ocultar la evidencia.');
}
$stmt->close();
jexit(true, 'Evidencia ocultada.');
