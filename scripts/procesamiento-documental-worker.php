<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
if (!defined('BASE_DIR')) define('BASE_DIR',realpath(__DIR__.'/..'));
require_once BASE_DIR.'/config/paths.php';
require_once CONFIG_DIR.'/conexion.php';
require_once CONFIG_DIR.'/seguridad.php';
require_once CONFIG_DIR.'/procesamiento_documental.php';

$once=in_array('--once',$argv??[],true);
$limit=0;
foreach ($argv??[] as $arg) if (preg_match('/^--max=(\d{1,4})$/',$arg,$m)) $limit=min(1000,(int)$m[1]);
$started=microtime(true); $processed=0;
do {
    try { $result=documentoProcesamientoEjecutarSiguiente($conn); }
    catch (Throwable $e) { fwrite(STDERR,"Error interno del worker; revisa el log del servidor.\n"); error_log('ODEA document worker error: '.$e->getMessage()); exit(1); }
    if ($result['status']==='empty') break;
    $processed++;
    fwrite(STDOUT,json_encode(['id_trabajo'=>$result['id_trabajo']??null,'status'=>$result['status']],JSON_UNESCAPED_UNICODE).PHP_EOL);
    if ($once || ($limit>0 && $processed>=$limit)) break;
} while (true);
fwrite(STDOUT,"Trabajos atendidos: {$processed}\n");
