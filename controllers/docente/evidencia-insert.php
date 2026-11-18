<?php
// evidencia-insert.php
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

function jexit($ok, $msg = '', $extra = []) {
  echo json_encode(array_merge(['status' => $ok ? 'ok' : 'error', 'message' => $msg], $extra));
  exit;
}

$usuarioActual = evidenciaUsuarioActual($conn);
$user_id = (int)$usuarioActual['id_usuario'];
$user_role = (int)$usuarioActual['rol'];


// Permitir crear a admin y docentes
if ($user_id <= 0) jexit(false, 'La sesión ya no es válida. Inicia sesión nuevamente.');
if (!in_array($user_role, [1,2,4], true)) jexit(false, 'No tienes permisos para agregar evidencias.');

// Inputs
$titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
$id_tipo= isset($_POST['id_tipo_evidencia']) ? (int)$_POST['id_tipo_evidencia'] : 0;

if (!$titulo || $id_tipo <= 0) jexit(false, 'Datos incompletos.');
if (mb_strlen($titulo) > 255) jexit(false, 'El título no puede superar 255 caracteres.');

$docenteId = $user_id;
if (in_array($user_role, [1, 2], true)) {
  $docenteId = (int)($_POST['id_docente'] ?? 0);
  if ($docenteId <= 0) jexit(false, 'Selecciona el docente propietario de la evidencia.');
  $docenteStmt = $conn->prepare('SELECT id_usuario FROM usuarios WHERE id_usuario = ? AND rol = 4 AND activo = 1');
  $docenteStmt->bind_param('i', $docenteId);
  $docenteStmt->execute();
  $docenteValido = $docenteStmt->get_result()->num_rows > 0;
  $docenteStmt->close();
  if (!$docenteValido) jexit(false, 'El docente seleccionado no está activo o no existe.');
}

$tipoStmt = $conn->prepare('SELECT id_tipo_evidencia FROM tipos_de_evidencia WHERE id_tipo_evidencia = ?');
$tipoStmt->bind_param('i', $id_tipo);
$tipoStmt->execute();
$tipoExiste = $tipoStmt->get_result()->num_rows > 0;
$tipoStmt->close();
if (!$tipoExiste) jexit(false, 'El tipo de evidencia seleccionado ya no existe.');

$archivoSubido = $_FILES['archivo'] ?? null;
if (!$archivoSubido || !isset($archivoSubido['error'])) {
  jexit(false, 'Debes seleccionar un archivo.');
}
if ((int)$archivoSubido['error'] !== UPLOAD_ERR_OK) {
  $mensajeCarga = match ((int)$archivoSubido['error']) {
    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo supera el límite permitido por el servidor.',
    UPLOAD_ERR_PARTIAL => 'El archivo se recibió incompleto. Intenta subirlo nuevamente.',
    UPLOAD_ERR_NO_FILE => 'Debes seleccionar un archivo.',
    UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 'El servidor no pudo recibir el archivo. Contacta al administrador.',
    default => 'No se pudo recibir el archivo. Intenta nuevamente.',
  };
  jexit(false, $mensajeCarga);
}
if (empty($archivoSubido['tmp_name']) || !is_uploaded_file($archivoSubido['tmp_name'])) {
  jexit(false, 'El archivo no se recibió correctamente. Intenta seleccionarlo otra vez.');
}

try {
  $meta = documentoAnalizar((string)$archivoSubido['tmp_name'], (string)($archivoSubido['name'] ?? ''), (int)($archivoSubido['size'] ?? 0));
  $coincidencias = documentoCoincidencias($conn, $meta['sha256'], $docenteId);
  $filename = documentoNombreFisico($meta['extension']);
  $destPath = documentoDestinoSeguro($filename);
} catch (Throwable $error) {
  jexit(false, $error->getMessage());
}
if (!move_uploaded_file($archivoSubido['tmp_name'], $destPath)) {
  jexit(false, 'No se pudo guardar el archivo en el servidor.');
}
if (hash_file('sha256', $destPath) !== $meta['sha256']) {
  @unlink($destPath);
  jexit(false, 'La integridad del archivo cambió durante el almacenamiento.');
}

// Insert
try {
$sql = "INSERT INTO evidencias (titulo, archivo, archivo_sha256, archivo_mime, archivo_bytes, id_docente, id_tipo_evidencia, ocultar)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0)";
$stmt = $conn->prepare($sql);
if (!$stmt) jexit(false, 'Error de preparación: '.$conn->error);
$stmt->bind_param('ssssiii', $titulo, $filename, $meta['sha256'], $meta['mime'], $meta['bytes'], $docenteId, $id_tipo);

if ($stmt->execute()) {
  $newId = $stmt->insert_id;
  $stmt->close();
  $procesamiento=documentoProcesamientoIntentarEncolar($conn,$newId,'EVIDENCIA',$newId,$meta['sha256'],$meta['mime'],$meta['bytes']);
  $warnings=[];
  if ($coincidencias['mismo_docente']) $warnings[]='Ya existe un archivo idéntico entre tus evidencias.';
  if ($coincidencias['posible_coincidencia']) $warnings[]='El archivo coincide con otro registro. No se muestran datos de otros usuarios.';
  jexit(true, 'Evidencia creada.', ['id'=>$newId, 'archivo'=>$filename, 'sha256'=>$meta['sha256'], 'advertencias'=>$warnings,'procesamiento'=>$procesamiento]);
} else {
  $err = $stmt->error ?: $conn->error;
  $stmt->close();
  // Si falla, intenta eliminar el archivo subido para no dejar basura
  @unlink($destPath);
  jexit(false, 'Error al crear: '.$err);
}
} catch (Throwable $error) {
  if (is_file($destPath)) @unlink($destPath);
  error_log('Error al crear evidencia: ' . $error->getMessage());
  jexit(false, 'No se pudo registrar la evidencia en la base de datos. Verifica la conexión y vuelve a intentar.');
}
