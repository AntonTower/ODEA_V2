<?php
header('Content-Type: application/json; charset=utf-8');
if(!defined('BASE_DIR'))define('BASE_DIR',realpath(__DIR__.'/../..'));
require_once BASE_DIR.'/config/paths.php';require_once CONFIG_DIR.'/conexion.php';require_once CONFIG_DIR.'/validacion.php';
require_once CONFIG_DIR.'/csrf.php';require_once CONFIG_DIR.'/seguridad.php';require_once CONFIG_DIR.'/esquemas_metadatos_documentales.php';require_once CONFIG_DIR.'/procesamiento_documental.php';
csrf_exigir();
function revisionMetaRespuesta(int $status,string $message,array $data=[]):void{http_response_code($status);echo json_encode(array_merge(['status'=>$status<400?'ok':'error','message'=>$message],$data),JSON_UNESCAPED_UNICODE);exit;}
$user=seguridadUsuarioActual($conn);$evidencia=(int)($_POST['id_evidencia']??0);$job=(int)($_POST['id_trabajo']??0);$revision=(int)($_POST['revision']??-1);
$fields=json_decode((string)($_POST['campos']??''),true);$replace=($_POST['autorizar_reemplazo']??'')==='1';
if(!seguridadTieneRol($user,[1,2,4]))revisionMetaRespuesta(403,'No tienes permiso para revisar metadatos.');
if(!documentoProcesamientoDisponible($conn))revisionMetaRespuesta(503,'La infraestructura de extracción documental no está configurada.');
if($evidencia<1||$job<1||$revision<0||!is_array($fields))revisionMetaRespuesta(400,'Solicitud de revisión incompleta.');
$st=$conn->prepare('SELECT id_evidencia,id_docente,id_tipo_evidencia,ocultar FROM evidencias WHERE id_evidencia=?');$st->bind_param('i',$evidencia);$st->execute();$e=$st->get_result()->fetch_assoc();$st->close();
if(!$e||(int)$e['ocultar']!==0||!seguridadPuedeEditarEvidencia($user,$e))revisionMetaRespuesta(403,'No tienes permiso para esta evidencia.');
try{$newRevision=metadatosGuardarRevision($conn,$evidencia,$job,(int)$user['id_usuario'],$revision,$fields,$replace);revisionMetaRespuesta(200,'La revisión quedó registrada.',['revision'=>$newRevision]);}
catch(DomainException $ex){revisionMetaRespuesta(409,$ex->getMessage());}
catch(InvalidArgumentException $ex){revisionMetaRespuesta(422,$ex->getMessage());}
catch(Throwable $ex){error_log('ODEA metadata review failed evidence='.$evidencia.' job='.$job.' code='.get_class($ex));revisionMetaRespuesta(500,'No se pudo guardar la revisión. Recarga la página e inténtalo de nuevo.');}
