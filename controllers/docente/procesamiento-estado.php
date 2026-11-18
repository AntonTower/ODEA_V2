<?php
header('Content-Type: application/json; charset=utf-8');
if (!defined('BASE_DIR')) define('BASE_DIR',realpath(__DIR__.'/../..'));
require_once BASE_DIR.'/config/paths.php';
require_once CONFIG_DIR.'/conexion.php';
require_once CONFIG_DIR.'/validacion.php';
require_once CONFIG_DIR.'/seguridad.php';
require_once CONFIG_DIR.'/procesamiento_documental.php';

function procesamientoJson(bool $ok,string $message='',array $extra=[]): void { echo json_encode(array_merge(['status'=>$ok?'ok':'error','message'=>$message],$extra),JSON_UNESCAPED_UNICODE); exit; }
$usuario=seguridadUsuarioActual($conn); $id=(int)($_GET['id']??0);
if (!$id) procesamientoJson(false,'Evidencia no encontrada.');
$st=$conn->prepare('SELECT id_evidencia,id_docente,id_tipo_evidencia,ocultar FROM evidencias WHERE id_evidencia=?');
$st->bind_param('i',$id); $st->execute(); $e=$st->get_result()->fetch_assoc(); $st->close();
if (!$e || !seguridadPuedeVerEvidencia($conn,$usuario,$e)) procesamientoJson(false,'No tienes acceso a esta evidencia.');
if (!documentoProcesamientoDisponible($conn)) procesamientoJson(true,'',['disponible'=>false,'trabajos'=>[]]);
$st=$conn->prepare("SELECT t.id_trabajo,t.estado,t.proveedor_usado,t.intentos,t.mensaje_error,t.procesado_en,t.creado_en,
    o.tipo_origen,o.id_origen,EXISTS(SELECT 1 FROM resultados_trabajo_documental r WHERE r.id_trabajo=t.id_trabajo) AS resultado_disponible
    FROM origenes_trabajo_documental o JOIN trabajos_documentales t ON t.id_trabajo=o.id_trabajo
    WHERE o.id_evidencia=? ORDER BY t.creado_en DESC,t.id_trabajo DESC");
$st->bind_param('i',$id); $st->execute(); $rows=$st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
foreach ($rows as &$job) $job['can_retry']=seguridadPuedeEditarEvidencia($usuario,$e);
unset($job);
procesamientoJson(true,'',['disponible'=>true,'trabajos'=>$rows]);
