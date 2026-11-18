<?php
header('Content-Type: application/json; charset=utf-8');

if (!defined('BASE_DIR')) {
    define('BASE_DIR', realpath(__DIR__ . '/../..'));
}
require_once BASE_DIR . '/config/paths.php';
require_once CONFIG_DIR . '/conexion.php';
require_once CONFIG_DIR . '/validacion.php';
require_once CONFIG_DIR . '/evidencias.php';

function jexit($ok, $msg = '', $extra = [])
{
    echo json_encode(array_merge(['status' => $ok ? 'ok' : 'error', 'message' => $msg], $extra));
    exit;
}

$usuario = evidenciaUsuarioActual($conn);
$userId = (int)$usuario['id_usuario'];
if ($userId <= 0) jexit(false, 'La sesión ya no es válida. Inicia sesión nuevamente.');

$id = (int)($_POST['id_evidencia'] ?? 0);
$titulo = trim((string)($_POST['titulo'] ?? ''));
$tipoId = (int)($_POST['id_tipo_evidencia'] ?? 0);
if ($id <= 0 || $titulo === '' || $tipoId <= 0) jexit(false, 'Datos incompletos.');
if (mb_strlen($titulo) > 255) jexit(false, 'El título no puede superar 255 caracteres.');

$stmt = $conn->prepare('SELECT archivo, id_docente FROM evidencias WHERE id_evidencia = ? AND ocultar = 0');
$stmt->bind_param('i', $id);
$stmt->execute();
$evidencia = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$evidencia) jexit(false, 'Evidencia no encontrada.');
if (!evidenciaPuedeEditar($usuario, $evidencia)) jexit(false, 'No tienes permiso para editar esta evidencia.');

$stmt = $conn->prepare('SELECT 1 FROM tipos_de_evidencia WHERE id_tipo_evidencia = ?');
$stmt->bind_param('i', $tipoId);
$stmt->execute();
$tipoExiste = $stmt->get_result()->num_rows > 0;
$stmt->close();
if (!$tipoExiste) jexit(false, 'El tipo de evidencia seleccionado ya no existe.');

$destDir = BASE_DIR . '/uploads/files';
$archivoNuevo = basename((string)$evidencia['archivo']);
$rutaArchivoNuevo = null;
$hayArchivo = isset($_FILES['archivo']) && (int)($_FILES['archivo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

if ($hayArchivo) {
    $uploadError = (int)($_FILES['archivo']['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['archivo']['tmp_name'] ?? '')) {
        jexit(false, 'No se pudo recibir el archivo seleccionado.');
    }

    $size = (int)$_FILES['archivo']['size'];
    if ($size <= 0 || $size > 10 * 1024 * 1024) jexit(false, 'El archivo debe pesar como máximo 10 MB.');

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $_FILES['archivo']['tmp_name']) : false;
    if ($finfo) finfo_close($finfo);
    $extension = strtolower(pathinfo((string)$_FILES['archivo']['name'], PATHINFO_EXTENSION));
    $mimeExtensiones = [
        'application/pdf' => ['pdf'],
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
    ];
    if (!$mime || !isset($mimeExtensiones[$mime]) || !in_array($extension, $mimeExtensiones[$mime], true)) {
        jexit(false, 'Tipo de archivo no permitido. Usa PDF, JPG, PNG o WEBP.');
    }

    if (!is_dir($destDir) && !mkdir($destDir, 0775, true) && !is_dir($destDir)) {
        jexit(false, 'No se pudo crear la carpeta de archivos.');
    }

    $slug = trim(preg_replace('/[^a-z0-9_]+/i', '_', strtolower($titulo)), '_') ?: 'evidencia';
    $archivoNuevo = $slug . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $rutaArchivoNuevo = $destDir . '/' . $archivoNuevo;
    if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $rutaArchivoNuevo)) {
        jexit(false, 'No se pudo guardar el archivo.');
    }
}

$sql = 'UPDATE evidencias SET titulo = ?, id_tipo_evidencia = ?, archivo = ? WHERE id_evidencia = ? AND ocultar = 0';
if ((int)$usuario['rol'] === 4) $sql .= ' AND id_docente = ?';
$stmt = $conn->prepare($sql);
if (!$stmt) {
    if ($rutaArchivoNuevo && is_file($rutaArchivoNuevo)) @unlink($rutaArchivoNuevo);
    jexit(false, 'No se pudo preparar la actualización.');
}
if ((int)$usuario['rol'] === 4) {
    $stmt->bind_param('sisii', $titulo, $tipoId, $archivoNuevo, $id, $userId);
} else {
    $stmt->bind_param('sisi', $titulo, $tipoId, $archivoNuevo, $id);
}

if (!$stmt->execute()) {
    $stmt->close();
    if ($rutaArchivoNuevo && is_file($rutaArchivoNuevo)) @unlink($rutaArchivoNuevo);
    jexit(false, 'No se pudo actualizar la evidencia.');
}
$stmt->close();

if ($rutaArchivoNuevo && $evidencia['archivo']) {
    $rutaAnterior = $destDir . '/' . basename((string)$evidencia['archivo']);
    if (is_file($rutaAnterior)) @unlink($rutaAnterior);
}

jexit(true, 'Evidencia actualizada.');
