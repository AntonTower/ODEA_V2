<?php
require_once __DIR__.'/paths.php';
require_once BASE_DIR.'/services/documentos/DocumentoProcessor.php';

const ODEA_DOCUMENT_PIPELINE_VERSION='2';
const ODEA_DOCUMENT_MAX_SECONDS=45;
const ODEA_DOCUMENT_MAX_BYTES=10485760;

function documentoProcessorKey(): string
{
    $key=strtolower(trim((string)(getenv('ODEA_DOCUMENT_PROCESSOR')?:'disabled')));
    return in_array($key,['disabled','local'],true)?$key:'disabled';
}

function documentoProcessorProvider(?string $key=null): DocumentoProcessorProvider
{
    $key=$key??documentoProcessorKey();
    if ($key==='local') return new ProcesadorLocalCLI();
    return new ProcesadorDeshabilitado();
}

function documentoProcesamientoDisponible(mysqli $conn): bool
{
    $st=$conn->prepare("SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('trabajos_documentales','origenes_trabajo_documental','resultados_trabajo_documental')");
    $st->execute(); $ok=(int)$st->get_result()->fetch_assoc()['total']===3; $st->close(); return $ok;
}

/** Encola por huella, versión de pipeline y proveedor solicitado. Repetir el mismo
 * archivo desde varias evidencias enlaza el mismo trabajo y evita otra extracción. */
function documentoProcesamientoEncolar(mysqli $conn,int $evidenciaId,string $tipoOrigen,int $idOrigen,string $sha256,string $mime,int $bytes): array
{
    if ($evidenciaId<1 || !in_array($tipoOrigen,['EVIDENCIA','ATRIBUTO'],true) || $idOrigen<1
        || !preg_match('/^[a-f0-9]{64}$/',$sha256) || $bytes<1 || $bytes>ODEA_DOCUMENT_MAX_BYTES) {
        throw new InvalidArgumentException('Metadatos no válidos para encolar el documento.');
    }
    $proveedor=documentoProcessorKey(); $pipeline=ODEA_DOCUMENT_PIPELINE_VERSION;
    $conn->begin_transaction();
    try {
        $st=$conn->prepare("INSERT INTO trabajos_documentales (sha256,mime,bytes,pipeline_version,proveedor_solicitado,estado)
            VALUES (?,?,?,?,?,'PENDIENTE') ON DUPLICATE KEY UPDATE id_trabajo=LAST_INSERT_ID(id_trabajo)");
        $st->bind_param('ssiss',$sha256,$mime,$bytes,$pipeline,$proveedor); $st->execute();
        $idTrabajo=(int)$conn->insert_id; $st->close();
        $source=(string)$idOrigen;
        $st=$conn->prepare('INSERT IGNORE INTO origenes_trabajo_documental (id_trabajo,id_evidencia,tipo_origen,id_origen) VALUES (?,?,?,?)');
        $st->bind_param('iiss',$idTrabajo,$evidenciaId,$tipoOrigen,$source); $st->execute(); $st->close();
        $st=$conn->prepare('SELECT estado,procesado_en FROM trabajos_documentales WHERE id_trabajo=?');
        $st->bind_param('i',$idTrabajo); $st->execute(); $trabajo=$st->get_result()->fetch_assoc(); $st->close();
        $conn->commit();
        return ['id_trabajo'=>$idTrabajo,'estado'=>$trabajo['estado']??'PENDIENTE','reutilizado'=>($trabajo['estado']??'')!=='PENDIENTE'];
    } catch (Throwable $e) { $conn->rollback(); throw $e; }
}

function documentoProcesamientoIntentarEncolar(mysqli $conn,int $evidenciaId,string $tipoOrigen,int $idOrigen,string $sha256,string $mime,int $bytes): array
{
    try { return documentoProcesamientoEncolar($conn,$evidenciaId,$tipoOrigen,$idOrigen,$sha256,$mime,$bytes); }
    catch (Throwable $e) {
        error_log('ODEA document queue unavailable evidence='.$evidenciaId.' source='.$tipoOrigen);
        return ['id_trabajo'=>null,'estado'=>'NO_DISPONIBLE','reutilizado'=>false];
    }
}

function documentoProcesamientoReintentar(mysqli $conn,int $idTrabajo): int
{
    $st=$conn->prepare('SELECT * FROM trabajos_documentales WHERE id_trabajo=?');
    $st->bind_param('i',$idTrabajo); $st->execute(); $row=$st->get_result()->fetch_assoc(); $st->close();
    if (!$row || !in_array($row['estado'],['ERROR','CANCELADO'],true)) throw new RuntimeException('Este trabajo no está disponible para reintento.');
    $origen=documentoProcesamientoResolverOrigen($conn,$row);
    if (!$origen) throw new RuntimeException('El documento fue reemplazado o ya no está disponible.');
    $nuevo=documentoProcesamientoEncolar($conn,(int)$origen['id_evidencia'],(string)$origen['tipo_origen'],(int)$origen['id_origen'],(string)$row['sha256'],(string)$row['mime'],(int)$row['bytes']);
    if ($nuevo['estado']==='ERROR' || $nuevo['estado']==='CANCELADO') {
        $st=$conn->prepare("UPDATE trabajos_documentales SET estado='PENDIENTE',intentos=0,mensaje_error=NULL,token_worker=NULL,lease_hasta=NULL WHERE id_trabajo=? AND estado IN ('ERROR','CANCELADO')");
        $id=(int)$nuevo['id_trabajo']; $st->bind_param('i',$id); $st->execute(); $st->close();
    }
    return (int)$nuevo['id_trabajo'];
}

function documentoProcesamientoReclamar(mysqli $conn,int $leaseSegundos=90): ?array
{
    $conn->begin_transaction();
    try {
        $conn->query("UPDATE trabajos_documentales SET estado=IF(intentos>=max_intentos,'ERROR','PENDIENTE'),token_worker=NULL,lease_hasta=NULL,
            mensaje_error=IF(intentos>=max_intentos,'El worker se interrumpió y se alcanzó el límite de intentos.','El worker anterior no terminó; el trabajo volvió a la cola.')
            WHERE estado='EN_PROCESO' AND lease_hasta<CURRENT_TIMESTAMP");
        $rs=$conn->query("SELECT id_trabajo FROM trabajos_documentales WHERE estado='PENDIENTE' AND intentos<max_intentos ORDER BY creado_en,id_trabajo LIMIT 1 FOR UPDATE");
        $row=$rs->fetch_assoc(); $rs->free();
        if (!$row) { $conn->commit(); return null; }
        $id=(int)$row['id_trabajo']; $token=bin2hex(random_bytes(16));
        $st=$conn->prepare("UPDATE trabajos_documentales SET estado='EN_PROCESO',intentos=intentos+1,token_worker=?,lease_hasta=DATE_ADD(CURRENT_TIMESTAMP,INTERVAL ? SECOND),iniciado_en=COALESCE(iniciado_en,CURRENT_TIMESTAMP),mensaje_error=NULL WHERE id_trabajo=? AND estado='PENDIENTE'");
        $st->bind_param('sii',$token,$leaseSegundos,$id); $st->execute(); $st->close();
        $st=$conn->prepare('SELECT * FROM trabajos_documentales WHERE id_trabajo=?'); $st->bind_param('i',$id); $st->execute(); $job=$st->get_result()->fetch_assoc(); $st->close();
        $conn->commit(); return $job?:null;
    } catch (Throwable $e) { $conn->rollback(); throw $e; }
}

function documentoProcesamientoResolverOrigen(mysqli $conn,array $job): ?array
{
    $id=(int)$job['id_trabajo'];
    $st=$conn->prepare('SELECT id_evidencia,tipo_origen,id_origen FROM origenes_trabajo_documental WHERE id_trabajo=? ORDER BY id_origen_trabajo');
    $st->bind_param('i',$id); $st->execute(); $origenes=$st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
    foreach ($origenes as $origen) {
        if ($origen['tipo_origen']==='EVIDENCIA') {
            $q=$conn->prepare('SELECT e.archivo,e.archivo_sha256 AS sha256,e.archivo_mime AS mime,e.archivo_bytes AS bytes,e.titulo,t.nombre_tipo AS tipo_documental FROM evidencias e LEFT JOIN tipos_de_evidencia t ON t.id_tipo_evidencia=e.id_tipo_evidencia WHERE e.id_evidencia=? AND e.ocultar=0');
        } else {
            $q=$conn->prepare('SELECT v.valor_archivo AS archivo,v.valor_archivo_sha256 AS sha256,v.valor_archivo_mime AS mime,v.valor_archivo_bytes AS bytes,e.titulo,t.nombre_tipo AS tipo_documental FROM evidencia_valores_atributo v JOIN evidencias e ON e.id_evidencia=v.id_evidencia LEFT JOIN tipos_de_evidencia t ON t.id_tipo_evidencia=e.id_tipo_evidencia WHERE v.id_eva=? AND v.id_evidencia=?');
        }
        if ($origen['tipo_origen']==='EVIDENCIA') { $originId=(int)$origen['id_origen']; $q->bind_param('i',$originId); }
        else { $originId=(string)$origen['id_origen']; $evidencia=(int)$origen['id_evidencia']; $q->bind_param('si',$originId,$evidencia); }
        $q->execute(); $doc=$q->get_result()->fetch_assoc(); $q->close();
        if (!$doc || !hash_equals((string)$job['sha256'],(string)$doc['sha256'])
            || (int)$doc['bytes']!==(int)$job['bytes'] || (string)$doc['mime']!==(string)$job['mime']) continue;
        if (!in_array((string)$doc['mime'],['application/pdf','image/jpeg','image/png','image/webp'],true)) continue;
        $path=seguridadResolverArchivo((string)$doc['archivo']);
        if (!$path || filesize($path)!==(int)$job['bytes'] || !hash_equals((string)$job['sha256'],(string)hash_file('sha256',$path))) continue;
        return ['id_evidencia'=>(int)$origen['id_evidencia'],'tipo_origen'=>$origen['tipo_origen'],'id_origen'=>(int)$origen['id_origen'],'archivo'=>$doc['archivo'],'ruta'=>$path,'mime'=>$job['mime'],'bytes'=>(int)$job['bytes'],'sha256'=>$job['sha256']];
    }
    return null;
}

function documentoProcesamientoEjecutarSiguiente(mysqli $conn,?DocumentoProcessorProvider $provider=null,int $timeout=ODEA_DOCUMENT_MAX_SECONDS): array
{
    if (!documentoProcesamientoDisponible($conn)) throw new RuntimeException('Falta aplicar la migración de procesamiento documental.');
    $job=documentoProcesamientoReclamar($conn);
    if (!$job) return ['status'=>'empty'];
    // El worker usa la configuración vigente al ejecutarse; esto permite habilitar
    // localmente trabajos que se encolaron mientras el proveedor estaba desactivado.
    $id=(int)$job['id_trabajo']; $provider=$provider??documentoProcessorProvider();
    $origen=documentoProcesamientoResolverOrigen($conn,$job);
    if (!$origen) {
        documentoProcesamientoFinalizarError($conn,$id,(string)$job['token_worker'],'El archivo ya no está disponible o cambió desde que se encoló.','invalid_document','CANCELADO','none');
        return ['status'=>'cancelled','id_trabajo'=>$id];
    }
    try {
        $resultado=$provider->procesar($origen,$origen['ruta'],$timeout);
        if (!isset($resultado['texto']) || !is_string($resultado['texto']) || !is_array($resultado['metadatos']??null)) throw new DocumentoProcessingException('El proveedor devolvió un resultado no válido.','provider_error');
        $state=!empty($resultado['requiere_revision'])?'REQUIERE_REVISION':'PROCESADO';
        $texto=mb_substr($resultado['texto'],0,1000000);
        $meta=json_encode($resultado['metadatos'],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
        $conn->begin_transaction();
        $st=$conn->prepare('INSERT INTO resultados_trabajo_documental (id_trabajo,texto_extraido,metadatos_json) VALUES (?,?,?) ON DUPLICATE KEY UPDATE texto_extraido=VALUES(texto_extraido),metadatos_json=VALUES(metadatos_json)');
        $st->bind_param('iss',$id,$texto,$meta); $st->execute(); $st->close();
        $st=$conn->prepare("UPDATE trabajos_documentales SET estado=?,proveedor_usado=?,procesado_en=CURRENT_TIMESTAMP,token_worker=NULL,lease_hasta=NULL,mensaje_error=NULL WHERE id_trabajo=? AND token_worker=? AND estado='EN_PROCESO'");
        $name=$provider->nombre(); $token=(string)$job['token_worker']; $st->bind_param('ssis',$state,$name,$id,$token); $st->execute(); $updated=$st->affected_rows; $st->close();
        if (!$updated) throw new RuntimeException('Se perdió el lease del trabajo.');
        $conn->commit(); return ['status'=>$state,'id_trabajo'=>$id];
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
        $code=$e instanceof DocumentoProcessingException?$e->codigoError:'internal_error';
        $message=$e instanceof DocumentoProcessingException?$e->getMessage():'Ocurrió un error interno al procesar el documento.';
        error_log('ODEA document processing job='.$id.' code='.$code);
        documentoProcesamientoFinalizarError($conn,$id,(string)$job['token_worker'],$message,$code,'ERROR',$provider->nombre());
        return ['status'=>'ERROR','id_trabajo'=>$id,'error'=>$code];
    }
}

function documentoProcesamientoFinalizarError(mysqli $conn,int $id,string $token,string $mensaje,string $codigo,string $estado='ERROR',string $proveedor='none'): void
{
    $safe=mb_substr($mensaje,0,480);
    $st=$conn->prepare("UPDATE trabajos_documentales SET estado=?,mensaje_error=?,token_worker=NULL,lease_hasta=NULL,proveedor_usado=COALESCE(proveedor_usado,?) WHERE id_trabajo=? AND token_worker=? AND estado='EN_PROCESO'");
    $provider=substr($proveedor,0,100); $st->bind_param('sssis',$estado,$safe,$provider,$id,$token); $st->execute(); $st->close();
}
