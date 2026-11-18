<?php
header('Content-Type: application/json; charset=utf-8');
if (!defined('BASE_DIR')) define('BASE_DIR', realpath(__DIR__ . '/../..'));
require_once BASE_DIR . '/config/paths.php';
require_once CONFIG_DIR . '/validacion.php';
require_once CONFIG_DIR . '/conexion.php';
require_once BASE_DIR . '/views/evaluador/permisos-instrumentos.php';

function historialRespuesta(bool $ok, string $mensaje='', array $extra=[]): void
{
    echo json_encode(array_merge(['status'=>$ok?'ok':'error','message'=>$mensaje],$extra),JSON_UNESCAPED_UNICODE);
    exit;
}

$usuario=usuarioActualInstrumentos($conn);
$userId=(int)$usuario['id_usuario'];
$rol=(int)$usuario['rol'];
$evidenciaId=(int)($_GET['id_evidencia']??0);
$instrumentoId=(int)($_GET['id_instrumento']??0);
if ($evidenciaId<1 || $instrumentoId<1) historialRespuesta(false,'Parámetros inválidos.');
if (!in_array($rol,[1,2,3,4],true)) historialRespuesta(false,'No autorizado.');

$st=$conn->prepare('SELECT e.id_docente,e.id_tipo_evidencia FROM evidencias e WHERE e.id_evidencia=? AND e.ocultar=0');
$st->bind_param('i',$evidenciaId); $st->execute(); $evidencia=$st->get_result()->fetch_assoc(); $st->close();
if (!$evidencia) historialRespuesta(false,'Evidencia no encontrada.');
if ($rol===4 && (int)$evidencia['id_docente']!==$userId) historialRespuesta(false,'No autorizado.');
if ($rol!==4) {
    $alcance=alcanceInstrumentos($usuario);
    $st=$conn->prepare("SELECT 1 FROM instrumento_tipo_evidencia ite JOIN instrumentos i ON i.id_instrumento=ite.id_instrumento AND i.activo=1 WHERE ($alcance) AND ite.id_tipo_evidencia=? AND ite.id_instrumento=? LIMIT 1");
    $tipo=(int)$evidencia['id_tipo_evidencia']; $st->bind_param('ii',$tipo,$instrumentoId); $st->execute(); $permitido=(bool)$st->get_result()->fetch_row(); $st->close();
    if (!$permitido) historialRespuesta(false,'No autorizado para este instrumento.');
} else {
    $st=$conn->prepare('SELECT 1 FROM instrumento_tipo_evidencia WHERE id_tipo_evidencia=? AND id_instrumento=? LIMIT 1');
    $tipo=(int)$evidencia['id_tipo_evidencia']; $st->bind_param('ii',$tipo,$instrumentoId); $st->execute(); $permitido=(bool)$st->get_result()->fetch_row(); $st->close();
    if (!$permitido) historialRespuesta(false,'Instrumento no asociado a esta evidencia.');
}

$st=$conn->prepare("SELECT h.id_historial,h.tipo_evento,h.resultado_anterior,h.resultado_nuevo,
        h.comentario_anterior,h.comentario_nuevo,h.motivo,h.creado_en,h.actor_nombre,
        ea.nombre AS estado_anterior,en.nombre AS estado_nuevo,ev.id_convocatoria,ev.id_version
    FROM evaluaciones_versionadas ev JOIN historial_evaluaciones h ON h.id_evaluacion=ev.id_evaluacion
    LEFT JOIN estados_evaluacion ea ON ea.id_estado=h.id_estado_anterior
    LEFT JOIN estados_evaluacion en ON en.id_estado=h.id_estado_nuevo
    WHERE ev.id_evidencia=? AND ev.id_instrumento=? ORDER BY h.creado_en DESC,h.id_historial DESC");
$st->bind_param('ii',$evidenciaId,$instrumentoId); $st->execute(); $result=$st->get_result(); $historial=[];
while ($row=$result->fetch_assoc()) $historial[]=$row;
$st->close();
historialRespuesta(true,'',['historial'=>$historial]);
