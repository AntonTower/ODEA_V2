<?php
header('Content-Type: application/json; charset=utf-8');
if (!defined('BASE_DIR')) define('BASE_DIR', realpath(__DIR__ . '/../..'));
require_once BASE_DIR . '/config/paths.php';
require_once CONFIG_DIR . '/validacion.php';
require_once CONFIG_DIR . '/csrf.php';
require_once CONFIG_DIR . '/conexion.php';
require_once CONFIG_DIR . '/evaluaciones.php';
require_once BASE_DIR . '/views/evaluador/permisos-instrumentos.php';
csrf_exigir();

function evaluacionRespuesta(bool $ok, string $mensaje='', array $extra=[]): void
{
    echo json_encode(array_merge(['status'=>$ok?'ok':'error','message'=>$mensaje],$extra), JSON_UNESCAPED_UNICODE);
    exit;
}

$usuario=usuarioActualInstrumentos($conn);
$userId=(int)$usuario['id_usuario'];
$rol=(int)$usuario['rol'];
if (!in_array($rol,[1,2,3],true)) evaluacionRespuesta(false,'No autorizado para evaluar.');

$evidenciaId=(int)($_POST['id_evidencia']??0);
$instrumentoId=(int)($_POST['id_instrumento']??0);
$versionId=(int)($_POST['id_version']??0);
$versionDb=$versionId>0?$versionId:null;
$estadoNuevoId=(int)($_POST['id_estado']??0);
$codigoEstado=trim((string)($_POST['codigo_estado']??''));
$resultadoEnviado=array_key_exists('resultado',$_POST) && trim((string)$_POST['resultado'])!=='';
$resultadoRaw=$resultadoEnviado?trim((string)$_POST['resultado']):'';
$comentario=trim((string)($_POST['comentario']??''));
$motivo=trim((string)($_POST['motivo']??''));
$cerrar=isset($_POST['cerrar']) && (string)$_POST['cerrar']==='1';

if ($evidenciaId<=0 || $instrumentoId<=0) evaluacionRespuesta(false,'Datos incompletos.');
if ($cerrar && !in_array($rol,[1,2],true)) evaluacionRespuesta(false,'Solo una facultad administrativa puede cerrar evaluaciones.');

$qE="SELECT e.id_evidencia,e.id_docente,e.id_tipo_evidencia,
            COALESCE(ta.total,0) AS total_attrs,COALESCE(vv.filled,0) AS filled_attrs
     FROM evidencias e
     LEFT JOIN (SELECT id_tipo_evidencia,COUNT(*) AS total FROM atributos_tipo_evidencia WHERE requerido=1 GROUP BY id_tipo_evidencia) ta
       ON ta.id_tipo_evidencia=e.id_tipo_evidencia
     LEFT JOIN (SELECT v.id_evidencia,COUNT(DISTINCT v.id_ate) AS filled
                FROM evidencia_valores_atributo v JOIN atributos_tipo_evidencia a ON a.id_ate=v.id_ate AND a.requerido=1
                GROUP BY v.id_evidencia) vv ON vv.id_evidencia=e.id_evidencia
     WHERE e.id_evidencia=? AND e.ocultar=0";
$st=$conn->prepare($qE); $st->bind_param('i',$evidenciaId); $st->execute(); $evidencia=$st->get_result()->fetch_assoc(); $st->close();
if (!$evidencia) evaluacionRespuesta(false,'Evidencia no encontrada.');
if ((int)$evidencia['filled_attrs'] < (int)$evidencia['total_attrs']) evaluacionRespuesta(false,'La evidencia no tiene completos sus campos obligatorios.');

$alcance=alcanceInstrumentos($usuario);
$qI="SELECT i.id_instrumento,i.tipo_calificacion,i.min_calificacion,i.max_calificacion
     FROM instrumento_tipo_evidencia ite JOIN instrumentos i ON i.id_instrumento=ite.id_instrumento AND i.activo=1
     WHERE ($alcance) AND ite.id_tipo_evidencia=? AND ite.id_instrumento=?";
$st=$conn->prepare($qI); $tipoId=(int)$evidencia['id_tipo_evidencia']; $st->bind_param('ii',$tipoId,$instrumentoId);
$st->execute(); $instrumento=$st->get_result()->fetch_assoc(); $st->close();
if (!$instrumento) evaluacionRespuesta(false,'No autorizado: instrumento no asignado, inactivo o no asociado al tipo de evidencia.');

$convocatoriaId=null;
if ($versionId>0) {
    $st=$conn->prepare("SELECT v.id_version,v.id_convocatoria
        FROM versiones_criterios v
        JOIN convocatorias c ON c.id_convocatoria=v.id_convocatoria AND c.id_instrumento=?
        JOIN periodos_academicos p ON p.id_periodo=c.id_periodo AND p.id_instrumento=c.id_instrumento
        WHERE v.id_version=? AND v.estado='PUBLICADA' AND c.estado='ACTIVA' AND p.estado='ACTIVO'");
    $st->bind_param('ii',$instrumentoId,$versionId); $st->execute(); $ctx=$st->get_result()->fetch_assoc(); $st->close();
    if (!$ctx) evaluacionRespuesta(false,'La versión no está publicada o no pertenece a un periodo/convocatoria activa del instrumento.');
    $convocatoriaId=(int)$ctx['id_convocatoria'];
}

$resultValue=null;
if ($resultadoEnviado) {
    if ($instrumento['tipo_calificacion']==='NUMERICA') {
        $min=is_null($instrumento['min_calificacion'])?0.0:(float)$instrumento['min_calificacion'];
        $max=is_null($instrumento['max_calificacion'])?10.0:(float)$instrumento['max_calificacion'];
        if (!is_numeric($resultadoRaw)) evaluacionRespuesta(false,'El resultado debe ser numérico.');
        $resultValue=(float)$resultadoRaw;
        if ($resultValue<$min || $resultValue>$max) evaluacionRespuesta(false,"El resultado debe estar entre {$min} y {$max}.");
    } else {
        if ($resultadoRaw!=='0' && $resultadoRaw!=='1') evaluacionRespuesta(false,'Para este instrumento usa 1 (aprobado) o 0 (no aprobado).');
        $resultValue=(float)(int)$resultadoRaw;
    }
}
$estadoNuevo = $estadoNuevoId>0 ? evaluacionObtenerEstado($conn,$estadoNuevoId) : null;
if ($estadoNuevoId===0 && $codigoEstado!=='') {
    $st=$conn->prepare('SELECT id_estado,codigo,nombre FROM estados_evaluacion WHERE codigo=? AND activo=1');
    $st->bind_param('s',$codigoEstado); $st->execute(); $estadoNuevo=$st->get_result()->fetch_assoc() ?: null; $st->close();
    if (!$estadoNuevo) evaluacionRespuesta(false,'El estado seleccionado no está activo.');
    $estadoNuevoId=(int)($estadoNuevo['id_estado']??0);
}
if ($estadoNuevoId>0 && !$estadoNuevo) evaluacionRespuesta(false,'El estado seleccionado no está activo.');

$conn->begin_transaction();
try {
    // Serializa cambios concurrentes sobre la misma evidencia; historia y vigente se confirman juntos.
    $lock=$conn->prepare('SELECT id_evidencia FROM evidencias WHERE id_evidencia=? AND ocultar=0 FOR UPDATE');
    $lock->bind_param('i',$evidenciaId); $lock->execute(); $exists=$lock->get_result()->num_rows>0; $lock->close();
    if (!$exists) throw new RuntimeException('Evidencia no disponible.');

    if ($versionId>0) {
        $st=$conn->prepare('SELECT * FROM evaluaciones_versionadas WHERE id_evidencia=? AND id_instrumento=? AND id_version=? FOR UPDATE');
        $st->bind_param('iii',$evidenciaId,$instrumentoId,$versionId);
    } else {
        $st=$conn->prepare('SELECT * FROM evaluaciones_versionadas WHERE id_evidencia=? AND id_instrumento=? AND id_version IS NULL FOR UPDATE');
        $st->bind_param('ii',$evidenciaId,$instrumentoId);
    }
    $st->execute(); $actual=$st->get_result()->fetch_assoc(); $st->close();
    $creada=!$actual;
    if ($actual && (int)$actual['cerrada']===1 && !in_array($rol,[1,2],true)) {
        throw new RuntimeException('La evaluación está cerrada; solicita su reapertura administrativa.');
    }
    if ($actual && (int)$actual['cerrada']===1 && $motivo==='') {
        throw new RuntimeException('Indica el motivo administrativo para modificar una evaluación cerrada.');
    }

    $resultadoAnterior=$actual && $actual['resultado']!==null?(float)$actual['resultado']:null;
    $comentarioAnterior=$actual['comentario']??null;
    $estadoAnterior=$actual && $actual['id_estado']!==null?(int)$actual['id_estado']:null;
    $nuevoResultado=$resultadoEnviado?$resultValue:$resultadoAnterior;
    $nuevoComentario=$comentario;
    $nuevoEstadoId=$estadoNuevo ? (int)$estadoNuevo['id_estado'] : ($estadoAnterior ?? 0);
    if ($nuevoEstadoId<=0) {
        $st=$conn->prepare("SELECT id_estado FROM estados_evaluacion WHERE codigo='PENDIENTE_REVISION' AND activo=1");
        $st->execute(); $nuevoEstadoId=(int)($st->get_result()->fetch_assoc()['id_estado']??0); $st->close();
        if (!$nuevoEstadoId) throw new RuntimeException('No está configurado el estado inicial de evaluación.');
    }
    $transicion=evaluacionValidarTransicion($conn,$instrumentoId,$estadoAnterior,$nuevoEstadoId);
    if (!$transicion['permitida']) throw new RuntimeException('La transición de estado no está habilitada para este instrumento.');
    $estadoCambio=$estadoAnterior!==$nuevoEstadoId;
    $resultadoCambio=$resultadoAnterior!==$nuevoResultado;
    $comentarioCambio=(string)$comentarioAnterior!==$nuevoComentario;
    if ($transicion['requiere_motivo'] && $motivo==='') throw new RuntimeException('Esta transición requiere un motivo.');

    $nombreActor=trim((string)($_SESSION['SESUSUARIO']??''));
    $correoActor=(string)($_SESSION['EMAIL']??'');
    $actor=$conn->prepare('SELECT nombre,apellidop,apellidom,correo FROM usuarios WHERE id_usuario=?');
    $actor->bind_param('i',$userId); $actor->execute(); $actorData=$actor->get_result()->fetch_assoc(); $actor->close();
    if ($actorData) { $nombreActor=trim($actorData['nombre'].' '.$actorData['apellidop'].' '.$actorData['apellidom']); $correoActor=$actorData['correo']; }

    if ($creada) {
        $st=$conn->prepare('INSERT INTO evaluaciones_versionadas (id_evidencia,id_instrumento,id_convocatoria,id_version,id_estado,resultado,comentario,id_usuario_eval) VALUES (?,?,?,?,?,?,?,?)');
        $st->bind_param('iiiiidsi',$evidenciaId,$instrumentoId,$convocatoriaId,$versionDb,$nuevoEstadoId,$nuevoResultado,$nuevoComentario,$userId);
        $st->execute(); $evaluacionId=(int)$st->insert_id; $st->close();
    } else {
        if (!$resultadoCambio && !$comentarioCambio && !$estadoCambio && !$cerrar && (int)$actual['cerrada']===0) {
            $conn->commit();
            evaluacionRespuesta(true,'Sin cambios.',['id_evaluacion'=>(int)$actual['id_evaluacion'],'id_estado'=>$nuevoEstadoId]);
        }
        $evaluacionId=(int)$actual['id_evaluacion'];
        $nuevoCerrada=$cerrar?1:0;
        $st=$conn->prepare('UPDATE evaluaciones_versionadas SET id_estado=?,resultado=?,comentario=?,id_usuario_eval=?,cerrada=?,cerrada_en=IF(?,CURRENT_TIMESTAMP,NULL) WHERE id_evaluacion=?');
        $st->bind_param('idsiiii',$nuevoEstadoId,$nuevoResultado,$nuevoComentario,$userId,$nuevoCerrada,$nuevoCerrada,$evaluacionId);
        $st->execute(); $st->close();
    }

    // Materializa solamente evaluaciones sin versión para que los reportes históricos sigan funcionando.
    if ($versionId===0 && $nuevoResultado!==null) {
        $legacy=$conn->prepare('INSERT INTO calificacion_evidencia (id_evidencia,id_instrumento,resultado,comentario,id_usuario_eval) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE resultado=VALUES(resultado),comentario=VALUES(comentario),id_usuario_eval=VALUES(id_usuario_eval),actualizado_en=CURRENT_TIMESTAMP');
        $legacy->bind_param('iidsi',$evidenciaId,$instrumentoId,$nuevoResultado,$nuevoComentario,$userId);
        $legacy->execute(); $legacyId=(int)$conn->insert_id; $legacy->close();
        if ($legacyId<=0) {
            $lookup=$conn->prepare('SELECT id_calificacion FROM calificacion_evidencia WHERE id_evidencia=? AND id_instrumento=?');
            $lookup->bind_param('ii',$evidenciaId,$instrumentoId); $lookup->execute(); $legacyId=(int)$lookup->get_result()->fetch_assoc()['id_calificacion']; $lookup->close();
        }
        $link=$conn->prepare('UPDATE evaluaciones_versionadas SET id_calificacion_legada=? WHERE id_evaluacion=?');
        $link->bind_param('ii',$legacyId,$evaluacionId); $link->execute(); $link->close();
    }

    $evento=$creada?'CREADA':($actual && (int)$actual['cerrada']===1?'REAPERTURA':($estadoCambio && $estadoNuevo && $estadoNuevo['codigo']==='OBSERVADA'?'OBSERVACION':($estadoCambio?'CAMBIO_ESTADO':'ACTUALIZADA')));
    if ($cerrar) $evento='CIERRE';
    $hist=$conn->prepare('INSERT INTO historial_evaluaciones (id_evaluacion,tipo_evento,resultado_anterior,resultado_nuevo,comentario_anterior,comentario_nuevo,id_estado_anterior,id_estado_nuevo,id_usuario_actor,actor_nombre,actor_correo,motivo) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
    $hist->bind_param('isddssiiisss',$evaluacionId,$evento,$resultadoAnterior,$nuevoResultado,$comentarioAnterior,$nuevoComentario,$estadoAnterior,$nuevoEstadoId,$userId,$nombreActor,$correoActor,$motivo);
    $hist->execute(); $histId=(int)$hist->insert_id; $hist->close();
    $conn->commit();
    evaluacionRespuesta(true,'Evaluación guardada.',['id_evaluacion'=>$evaluacionId,'id_historial'=>$histId,'id_estado'=>$nuevoEstadoId,'estado'=>$estadoNuevo['nombre']??'Pendiente de revisión']);
} catch (Throwable $e) {
    $conn->rollback();
    evaluacionRespuesta(false,$e instanceof RuntimeException?$e->getMessage():'No se pudo guardar la evaluación.');
}
