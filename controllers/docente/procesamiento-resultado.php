<?php
header('Content-Type: application/json; charset=utf-8');
if (!defined('BASE_DIR')) define('BASE_DIR',realpath(__DIR__.'/../..'));
require_once BASE_DIR.'/config/paths.php';
require_once CONFIG_DIR.'/conexion.php';
require_once CONFIG_DIR.'/validacion.php';
require_once CONFIG_DIR.'/seguridad.php';
require_once CONFIG_DIR.'/procesamiento_documental.php';
function procesamientoResultadoJson(bool $ok,string $message='',array $extra=[]): void { echo json_encode(array_merge(['status'=>$ok?'ok':'error','message'=>$message],$extra),JSON_UNESCAPED_UNICODE); exit; }
$usuario=seguridadUsuarioActual($conn); $id=(int)($_GET['id']??0);
if (!documentoProcesamientoDisponible($conn)) procesamientoResultadoJson(false,'La cola de procesamiento aún no está configurada.');
$st=$conn->prepare("SELECT e.id_evidencia,e.id_docente,e.id_tipo_evidencia,e.ocultar,t.estado,t.procesado_en,r.texto_extraido,r.metadatos_json
    FROM trabajos_documentales t JOIN origenes_trabajo_documental o ON o.id_trabajo=t.id_trabajo
    JOIN evidencias e ON e.id_evidencia=o.id_evidencia LEFT JOIN resultados_trabajo_documental r ON r.id_trabajo=t.id_trabajo
    WHERE t.id_trabajo=?");
$st->bind_param('i',$id); $st->execute(); $rows=$st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
$row=null;
foreach ($rows as $candidate) if (seguridadPuedeVerEvidencia($conn,$usuario,$candidate)) { $row=$candidate; break; }
if (!$row) procesamientoResultadoJson(false,'No tienes acceso a este resultado.');
if (!in_array($row['estado'],['PROCESADO','REQUIERE_REVISION'],true) || !isset($row['texto_extraido'])) procesamientoResultadoJson(false,'El resultado aún no está disponible.');
$meta=json_decode((string)$row['metadatos_json'],true);
procesamientoResultadoJson(true,'',['estado'=>$row['estado'],'procesado_en'=>$row['procesado_en'],'texto'=>$row['texto_extraido'],'metadatos'=>is_array($meta)?$meta:[]]);
