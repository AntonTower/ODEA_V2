<?php
header('Content-Type: application/json; charset=utf-8');

if (!defined('BASE_DIR')) {
    define('BASE_DIR', realpath(__DIR__ . '/../..'));
}
require_once BASE_DIR . '/config/paths.php';
require_once CONFIG_DIR . '/conexion.php';
require_once CONFIG_DIR . '/validacion.php';
require_once CONFIG_DIR . '/evidencias.php';
require_once CONFIG_DIR . '/documentos.php';
require_once CONFIG_DIR . '/procesamiento_documental.php';

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

$stmt = $conn->prepare('SELECT archivo, archivo_sha256, archivo_mime, archivo_bytes, id_docente, id_tipo_evidencia FROM evidencias WHERE id_evidencia = ? AND ocultar = 0');
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
if ((int)$evidencia['id_tipo_evidencia'] !== $tipoId) {
    $valores = $conn->prepare('SELECT 1 FROM evidencia_valores_atributo WHERE id_evidencia=? LIMIT 1');
    $valores->bind_param('i', $id); $valores->execute();
    $tieneValores = $valores->get_result()->num_rows > 0; $valores->close();
    if ($tieneValores) jexit(false, 'No se puede cambiar el tipo porque ya hay atributos capturados. Conserva el tipo o registra otra evidencia para evitar perder su correspondencia documental.');
}

$archivoNuevo = basename((string)$evidencia['archivo']);
$meta = ['sha256'=>$evidencia['archivo_sha256'], 'mime'=>$evidencia['archivo_mime'], 'bytes'=>$evidencia['archivo_bytes']];
$coincidencias = ['mismo_docente'=>false, 'posible_coincidencia'=>false];
$rutaArchivoNuevo = null;
$hayArchivo = isset($_FILES['archivo']) && (int)($_FILES['archivo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

if ($hayArchivo) {
    $uploadError = (int)($_FILES['archivo']['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK || !is_uploaded_file($_FILES['archivo']['tmp_name'] ?? '')) {
        jexit(false, 'No se pudo recibir el archivo seleccionado.');
    }

    try {
        $meta = documentoAnalizar((string)$_FILES['archivo']['tmp_name'], (string)$_FILES['archivo']['name'], (int)$_FILES['archivo']['size']);
        $coincidencias = documentoCoincidencias($conn, $meta['sha256'], (int)$evidencia['id_docente'], $id);
        $archivoNuevo = documentoNombreFisico($meta['extension']);
        $rutaArchivoNuevo = documentoDestinoSeguro($archivoNuevo);
    } catch (Throwable $error) {
        jexit(false, $error->getMessage());
    }
    if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $rutaArchivoNuevo)) {
        jexit(false, 'No se pudo guardar el archivo.');
    }
    if (hash_file('sha256', $rutaArchivoNuevo) !== $meta['sha256']) {
        @unlink($rutaArchivoNuevo);
        jexit(false, 'La integridad del archivo cambió durante el almacenamiento.');
    }
}

$sql = 'UPDATE evidencias SET titulo = ?, id_tipo_evidencia = ?, archivo = ?, archivo_sha256 = ?, archivo_mime = ?, archivo_bytes = ? WHERE id_evidencia = ? AND ocultar = 0';
if ((int)$usuario['rol'] === 4) $sql .= ' AND id_docente = ?';
$stmt = $conn->prepare($sql);
if (!$stmt) {
    if ($rutaArchivoNuevo && is_file($rutaArchivoNuevo)) @unlink($rutaArchivoNuevo);
    jexit(false, 'No se pudo preparar la actualización.');
}
if ((int)$usuario['rol'] === 4) {
    $stmt->bind_param('sisssiii', $titulo, $tipoId, $archivoNuevo, $meta['sha256'], $meta['mime'], $meta['bytes'], $id, $userId);
} else {
    $stmt->bind_param('sisssii', $titulo, $tipoId, $archivoNuevo, $meta['sha256'], $meta['mime'], $meta['bytes'], $id);
}

if (!$stmt->execute()) {
    $stmt->close();
    if ($rutaArchivoNuevo && is_file($rutaArchivoNuevo)) @unlink($rutaArchivoNuevo);
    jexit(false, 'No se pudo actualizar la evidencia.');
}
$stmt->close();

if ($rutaArchivoNuevo && $evidencia['archivo']) {
    $rutaAnterior = seguridadResolverArchivo((string)$evidencia['archivo']);
    if ($rutaAnterior) @unlink($rutaAnterior);
}

$procesamiento=$rutaArchivoNuevo
    ? documentoProcesamientoIntentarEncolar($conn,$id,'EVIDENCIA',$id,(string)$meta['sha256'],(string)$meta['mime'],(int)$meta['bytes'])
    : null;

$warnings=[];
if ($coincidencias['mismo_docente']) $warnings[]='Ya existe un archivo idéntico entre tus evidencias.';
if ($coincidencias['posible_coincidencia']) $warnings[]='El archivo coincide con otro registro. No se muestran datos de otros usuarios.';
jexit(true, 'Evidencia actualizada.', ['advertencias'=>$warnings,'procesamiento'=>$procesamiento]);
