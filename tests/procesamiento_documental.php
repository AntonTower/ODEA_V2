<?php
// Pruebas aisladas: crean y eliminan una base temporal y archivos temporales propios.
if (PHP_SAPI!=='cli') exit;
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
chdir(dirname(__DIR__));
require_once 'config/paths.php';
require_once CONFIG_DIR.'/seguridad.php';
require_once CONFIG_DIR.'/procesamiento_documental.php';
$admin=new mysqli('127.0.0.1','root','','',3308); $admin->set_charset('utf8mb4');
$database='gestor_test_procesamiento_'.bin2hex(random_bytes(5));
$runner=tempnam(sys_get_temp_dir(),'odea_processing_');
$created=false; $filePaths=[]; $checks=0;
function checkProcessing(bool $ok,string $message): void { global $checks; if(!$ok) throw new RuntimeException($message); $checks++; echo "OK: {$message}\n"; }
class ProcessingSuccess implements DocumentoProcessorProvider {
    public int $calls=0; public function nombre():string{return 'test-success';}
    public function procesar(array $documento,string $ruta,int $limiteSegundos):array{$this->calls++;return ['texto'=>'Texto de prueba validable por una persona.','metadatos'=>['mock'=>true]];}
}
class ProcessingFailure implements DocumentoProcessorProvider {
    public function nombre():string{return 'test-failure';}
    public function procesar(array $documento,string $ruta,int $limiteSegundos):array{throw new DocumentoProcessingException('Proveedor simulado no disponible.','provider_error');}
}
class ProcessingTimeout implements DocumentoProcessorProvider {
    public function nombre():string{return 'test-timeout';}
    public function procesar(array $documento,string $ruta,int $limiteSegundos):array{throw new DocumentoProcessingException('El procesamiento excedió el tiempo máximo.','timeout');}
}
function processingEvidence(mysqli $db,string $filename,string $sha,int $bytes):int {
    $stmt=$db->prepare('INSERT INTO evidencias (titulo,archivo,archivo_sha256,archivo_mime,archivo_bytes,id_docente,id_tipo_evidencia,ocultar) VALUES (?,?,?,?,?,2,1,0)');
    $mime='application/pdf'; $title='Documento temporal'; $stmt->bind_param('ssssi',$title,$filename,$sha,$mime,$bytes); $stmt->execute(); $id=(int)$stmt->insert_id; $stmt->close(); return $id;
}
function processingFile(string $content):array {
    global $filePaths;
    $dir=BASE_DIR.'/uploads/files'; if(!is_dir($dir)) mkdir($dir,0775,true);
    $name='odea_test_'.bin2hex(random_bytes(8)).'.pdf'; $path=$dir.'/'.$name; file_put_contents($path,$content); $filePaths[]=$path;
    return [$name,hash('sha256',$content),strlen($content)];
}
try {
    $admin->multi_query("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci; USE `$database`;\n".
      file_get_contents('sql/gestor_evidencia_produccion_v2.sql')."\n".
      file_get_contents('sql/migrations/20261118_integridad_documental.sql')."\n".
      file_get_contents('sql/migrations/20261119_modelo_academico_versionado.sql')."\n".
      file_get_contents('sql/migrations/20261120_historial_estados_evaluacion.sql')."\n".
      file_get_contents('sql/migrations/20261121_procesamiento_documental.sql'));
    $created=true; do { if($r=$admin->store_result())$r->free(); } while($admin->more_results()&&$admin->next_result());
    $db=new mysqli('127.0.0.1','root','',$database,3308); $db->set_charset('utf8mb4');
    putenv('ODEA_DOCUMENT_PROCESSOR=disabled');

    // Ruta feliz y deduplicación entre el mismo archivo y dos asociaciones.
    [$file,$sha,$bytes]=processingFile('archivo de prueba procesable'); $e1=processingEvidence($db,$file,$sha,$bytes);
    $first=documentoProcesamientoEncolar($db,$e1,'EVIDENCIA',$e1,$sha,'application/pdf',$bytes);
    $again=documentoProcesamientoEncolar($db,$e1,'EVIDENCIA',$e1,$sha,'application/pdf',$bytes);
    $e2=processingEvidence($db,$file,$sha,$bytes);
    documentoProcesamientoEncolar($db,$e2,'EVIDENCIA',$e2,$sha,'application/pdf',$bytes);
    checkProcessing($first['id_trabajo']===$again['id_trabajo'],'Encolar dos veces el mismo SHA reutiliza el trabajo');
    checkProcessing((int)$db->query('SELECT COUNT(*) FROM origenes_trabajo_documental WHERE id_trabajo='.(int)$first['id_trabajo'])->fetch_row()[0]===2,'Una extracción puede asociarse a orígenes independientes');
    $success=new ProcessingSuccess(); $done=documentoProcesamientoEjecutarSiguiente($db,$success);
    checkProcessing($done['status']==='PROCESADO'&&$success->calls===1,'Proveedor correcto guarda resultado y completa el trabajo');
    $cache=documentoProcesamientoEncolar($db,$e1,'EVIDENCIA',$e1,$sha,'application/pdf',$bytes);
    checkProcessing($cache['id_trabajo']===$first['id_trabajo']&&$success->calls===1,'Documento ya procesado no se ejecuta nuevamente');

    // Servicio no configurado y error controlado del proveedor.
    [$f2,$h2,$b2]=processingFile('documento para servicio deshabilitado'); $e3=processingEvidence($db,$f2,$h2,$b2);
    documentoProcesamientoEncolar($db,$e3,'EVIDENCIA',$e3,$h2,'application/pdf',$b2);
    $unavailable=documentoProcesamientoEjecutarSiguiente($db);
    checkProcessing($unavailable['status']==='ERROR','Servicio no configurado deja la carga y marca error recuperable');
    [$f3,$h3,$b3]=processingFile('documento para error de proveedor'); $e4=processingEvidence($db,$f3,$h3,$b3);
    documentoProcesamientoEncolar($db,$e4,'EVIDENCIA',$e4,$h3,'application/pdf',$b3);
    $failed=documentoProcesamientoEjecutarSiguiente($db,new ProcessingFailure());
    checkProcessing($failed['status']==='ERROR','Error del proveedor queda registrado sin contenido sensible');
    $retried=documentoProcesamientoReintentar($db,(int)$failed['id_trabajo']);
    checkProcessing(documentoProcesamientoEjecutarSiguiente($db,new ProcessingSuccess())['status']==='PROCESADO','Reintento autorizado retorna el trabajo a la cola');

    [$f4,$h4,$b4]=processingFile('documento para timeout'); $e5=processingEvidence($db,$f4,$h4,$b4);
    documentoProcesamientoEncolar($db,$e5,'EVIDENCIA',$e5,$h4,'application/pdf',$b4);
    $timed=documentoProcesamientoEjecutarSiguiente($db,new ProcessingTimeout());
    checkProcessing($timed['status']==='ERROR'&&$timed['error']==='timeout','Timeout se conserva como error reintentable');

    [$f5,$h5,$b5]=processingFile('hash deliberadamente distinto'); $e6=processingEvidence($db,$f5,$h5,$b5);
    $wrong=str_repeat('a',64); if($wrong===$h5)$wrong=str_repeat('b',64);
    documentoProcesamientoEncolar($db,$e6,'EVIDENCIA',$e6,$wrong,'application/pdf',$b5);
    checkProcessing(documentoProcesamientoEjecutarSiguiente($db,new ProcessingSuccess())['status']==='cancelled','Archivo inválido o reemplazado se cancela antes del proveedor');

    // Dos workers reclaman secuencialmente sobre conexiones distintas: el lease evita duplicar.
    [$f6,$h6,$b6]=processingFile('documento para concurrencia'); $e7=processingEvidence($db,$f6,$h6,$b6);
    $queued=documentoProcesamientoEncolar($db,$e7,'EVIDENCIA',$e7,$h6,'application/pdf',$b6);
    $claim=documentoProcesamientoReclamar($db);
    $second=new mysqli('127.0.0.1','root','',$database,3308); $second->set_charset('utf8mb4');
    $claim2=documentoProcesamientoReclamar($second);
    checkProcessing((int)$claim['id_trabajo']===(int)$queued['id_trabajo']&&$claim2===null,'Dos workers no reclaman el mismo trabajo en proceso');
    $db->query("UPDATE trabajos_documentales SET estado='PENDIENTE',token_worker=NULL,lease_hasta=NULL WHERE id_trabajo=".(int)$queued['id_trabajo']);
    $db->query('DROP TABLE origenes_trabajo_documental');
    $queueDown=documentoProcesamientoIntentarEncolar($db,$e7,'EVIDENCIA',$e7,$h6,'application/pdf',$b6);
    checkProcessing($queueDown['estado']==='NO_DISPONIBLE','La cola indisponible se reporta sin interrumpir la evidencia almacenada');
    $db->close(); $admin->query("DROP DATABASE `$database`"); $created=false;
    echo "{$checks} pruebas de procesamiento correctas.\n";
} finally {
    if($created){try{$admin->query("DROP DATABASE `$database`");}catch(Throwable $e){}}
    if(is_file($runner))unlink($runner);
    foreach($filePaths as $path)if(is_file($path))unlink($path);
}
