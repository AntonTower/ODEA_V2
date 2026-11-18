<?php
header('Content-Type: application/json; charset=utf-8');
if (!defined('BASE_DIR')) define('BASE_DIR',realpath(__DIR__.'/../..'));
require_once BASE_DIR.'/config/paths.php';
require_once CONFIG_DIR.'/conexion.php';
require_once CONFIG_DIR.'/validacion.php';
require_once CONFIG_DIR.'/csrf.php';
require_once CONFIG_DIR.'/seguridad.php';
require_once CONFIG_DIR.'/procesamiento_documental.php';
csrf_exigir();
function procesamientoRetryJson(bool $ok,string $message='',array $extra=[]): void { echo json_encode(array_merge(['status'=>$ok?'ok':'error','message'=>$message],$extra),JSON_UNESCAPED_UNICODE); exit; }
$usuario=seguridadUsuarioActual($conn); $id=(int)($_POST['id_trabajo']??0);
if (!documentoProcesamientoDisponible($conn)) procesamientoRetryJson(false,'La cola de procesamiento aún no está configurada.');
if (!$id) procesamientoRetryJson(false,'Trabajo no válido.');
$st=$conn->prepare("SELECT t.estado,e.id_evidencia,e.id_docente,e.id_tipo_evidencia,e.ocultar
    FROM trabajos_documentales t JOIN origenes_trabajo_documental o ON o.id_trabajo=t.id_trabajo
    JOIN evidencias e ON e.id_evidencia=o.id_evidencia WHERE t.id_trabajo=? ORDER BY o.id_origen_trabajo");
$st->bind_param('i',$id); $st->execute(); $rows=$st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
$row=null;
foreach ($rows as $candidate) if (seguridadPuedeEditarEvidencia($usuario,$candidate)) { $row=$candidate; break; }
if (!$row) procesamientoRetryJson(false,'No tienes permiso para reintentar este documento.');
if (!in_array($row['estado'],['ERROR','CANCELADO'],true)) procesamientoRetryJson(false,'Solo se pueden reintentar trabajos con error o cancelados.');
try { $newId=documentoProcesamientoReintentar($conn,$id); procesamientoRetryJson(true,'El documento quedó en cola para reintento.',['id_trabajo'=>$newId]); }
catch (Throwable $e) { procesamientoRetryJson(false,'No se pudo volver a encolar el documento.'); }
