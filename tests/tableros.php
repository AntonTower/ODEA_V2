<?php
if(PHP_SAPI!=='cli')exit;
ob_start();
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);chdir(dirname(__DIR__));
require_once 'config/paths.php';require_once CONFIG_DIR.'/tableros.php';
$admin=new mysqli('127.0.0.1','root','','',3308);$admin->set_charset('utf8mb4');$dbName='gestor_test_tableros_'.bin2hex(random_bytes(5));$created=false;$checks=0;
function tableroTest(bool $ok,string $msg):void{global $checks;if(!$ok)throw new RuntimeException($msg);$checks++;echo "OK: $msg\n";}
function renderDashboard(string $path,mysqli $conn,array $user):string{global $BASE_DIR;$usuarioActual=$user;$user_role=(int)$user['rol'];$user_id=(int)$user['id_usuario'];ob_start();include $path;return (string)ob_get_clean();}
function dashboardSession(int $userId,int $role,string $roleName):void{
    if(session_status()!==PHP_SESSION_ACTIVE){session_save_path(sys_get_temp_dir());session_id('odeapreview'.bin2hex(random_bytes(6)));session_start();}
    $_SESSION=['ID'=>$userId,'ROL'=>$role,'SESUSUARIO'=>'Alex','APELLIDO'=>'Ejemplo','EMAIL'=>'demo@example.invalid','RNOMBRE'=>$roleName];
    $_SERVER['SCRIPT_NAME']='/ODEA/gestor_evidencias/views/comunes/index.php';$_SERVER['REQUEST_URI']='/ODEA/gestor_evidencias/views/comunes/index.php';$_SERVER['DOCUMENT_ROOT']='C:/xampp/htdocs';
}
function captureDashboard(string $component,int $userId,int $role,string $roleName,string $file):void{
    global $conn,$dbName,$MENU_CONFIG;$usuarioActual=['id_usuario'=>$userId,'rol'=>$role];dashboardSession($userId,$role,$roleName);require_once CONFIG_DIR.'/menu_config.php';
    require_once CONFIG_DIR.'/menu_config.php';ob_start();include COMPONENTS_DIR.'/layout/header.php';include COMPONENTS_DIR.'/layout/left-menu.php';include $component;include COMPONENTS_DIR.'/layout/footer.php';$html=(string)ob_get_clean();
    $css='file:///'.str_replace('\\','/',BASE_DIR).'/styles/global.css';$html=preg_replace('~href="[^"]*styles/global\.css"~','href="'.$css.'"',$html);
    $dir=BASE_DIR.'/docs/screenshots';if(!is_dir($dir))mkdir($dir,0775,true);file_put_contents($dir.'/'.$file,$html);
    if(session_status()===PHP_SESSION_ACTIVE)session_destroy();
}
try{
 $admin->multi_query("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci; USE `$dbName`;\n".
   file_get_contents('sql/gestor_evidencia_produccion_v2.sql')."\n".
   file_get_contents('sql/migrations/20261118_integridad_documental.sql')."\n".
   file_get_contents('sql/migrations/20261119_modelo_academico_versionado.sql')."\n".
   file_get_contents('sql/migrations/20261120_historial_estados_evaluacion.sql')."\n".
   file_get_contents('sql/migrations/20261121_procesamiento_documental.sql'));
 $created=true;do{if($r=$admin->store_result())$r->free();}while($admin->more_results()&&$admin->next_result());
 $conn=new mysqli('127.0.0.1','root','',$dbName,3308);$conn->set_charset('utf8mb4');
 $conn->query("UPDATE usuarios SET nombre='Alex',apellidop='Ejemplo',apellidom='Docente',correo='alex.docente@example.invalid' WHERE id_usuario=2");
 $hash=password_hash('test-only-password',PASSWORD_DEFAULT);$conn->query("INSERT INTO usuarios (nombre,apellidop,apellidom,correo,password,rol,activo) VALUES ('Docente','Temporal','Dos','docente2@test.invalid','$hash',4,1)");$otherTeacher=(int)$conn->insert_id;
 $privateTitle='PRIVADO_DE_OTRO_DOCENTE_'.bin2hex(random_bytes(3));
 $conn->query("INSERT INTO tipos_de_evidencia (nombre_tipo) VALUES ('Tipo aislado de tablero')");$otherType=(int)$conn->insert_id;
 $st=$conn->prepare('INSERT INTO evidencias (titulo,archivo,id_docente,id_tipo_evidencia,ocultar) VALUES (?,\'privado.pdf\',?,?,0)');$st->bind_param('sii',$privateTitle,$otherTeacher,$otherType);$st->execute();$st->close();
 $ownTitle='EVIDENCIA_PROPIA_DE_TABLERO';$st=$conn->prepare('INSERT INTO evidencias (titulo,archivo,id_docente,id_tipo_evidencia,ocultar) VALUES (?,\'propia.pdf\',2,1,0)');$st->bind_param('s',$ownTitle);$st->execute();$st->close();

 $start=microtime(true);$teacher=tableroDatosDocente($conn,2);$elapsed=microtime(true)-$start;
 tableroTest($teacher['total_evidencias']===3,'El tablero docente cuenta solo evidencias propias y visibles');
 tableroTest($teacher['instrumentos']!==[] && count($teacher['instrumentos'])===3,'El resumen se separa por los instrumentos activos del esquema');
 tableroTest($elapsed<2.0,'Las métricas docentes responden en menos de dos segundos sobre el conjunto de prueba');
 $htmlTeacher=renderDashboard('views/comunes/dashboard-docente.php',$conn,['id_usuario'=>2,'rol'=>4]);
 tableroTest(strpos($htmlTeacher,$ownTitle)!==false && strpos($htmlTeacher,$privateTitle)===false,'La vista docente incluye sus registros y no filtra datos de otro docente');

 $eval=tableroDatosEvaluador($conn,3);$assignedIds=array_map('intval',array_column($eval['instrumentos'],'id_instrumento'));
 tableroTest(count($assignedIds)===1,'El panel evaluador muestra solo instrumentos asignados');
 $htmlEval=renderDashboard('views/comunes/dashboard-evaluador.php',$conn,['id_usuario'=>3,'rol'=>3]);
 tableroTest(strpos($htmlEval,$privateTitle)===false,'La bandeja del evaluador no incluye tipos fuera de sus asignaciones');
 tableroTest(tableroExpedienteDocenteId($conn,['id_usuario'=>3,'rol'=>3],2)===null,'El rol evaluador no puede abrir expedientes docentes');
 tableroTest(tableroExpedienteDocenteId($conn,['id_usuario'=>2,'rol'=>4],4)===2,'El docente solo puede abrir su propio expediente aunque cambie el ID');
 tableroTest(tableroExpedienteDocenteId($conn,['id_usuario'=>1,'rol'=>1],2)===2,'El rol administrativo autorizado puede seleccionar un docente activo');

 $adminStats=tableroDatosAdministrador($conn);
 tableroTest($adminStats['docentes']===2 && $adminStats['evidencias']===4,'El resumen administrativo usa conteos agregados reales');
 $htmlAdmin=renderDashboard('views/comunes/dashboard-administrador.php',$conn,['id_usuario'=>1,'rol'=>1]);
 tableroTest(strpos($htmlAdmin,'Cola no instalada.')===false,'El panel presenta el estado disponible de la cola sin error de consulta');
 if(in_array('--capture',$argv??[],true)){
   captureDashboard('views/comunes/dashboard-docente.php',2,4,'Docente','dashboard-docente.html');
   captureDashboard('views/comunes/dashboard-evaluador.php',3,3,'Evaluador','dashboard-evaluador.html');
   captureDashboard('views/comunes/dashboard-administrador.php',1,1,'Super Usuario','dashboard-administrador.html');
   putenv('GESTOR_DB_NAME='.$dbName);dashboardSession(2,4,'Docente');ob_start();include 'views/docente/expediente.php';$html=(string)ob_get_clean();
   $css='file:///'.str_replace('\\','/',BASE_DIR).'/styles/global.css';$html=preg_replace('~href="[^"]*styles/global\.css"~','href="'.$css.'"',$html);
   file_put_contents(BASE_DIR.'/docs/screenshots/expediente-docente.html',$html);if(session_status()===PHP_SESSION_ACTIVE)session_destroy();putenv('GESTOR_DB_NAME');
   echo "Capturas HTML anonimizadas en docs/screenshots; convertir a PNG con Edge/Chromium.\n";
 }
 echo "$checks pruebas de tableros y privacidad correctas.\n";
}finally{if($created)try{$admin->query("DROP DATABASE `$dbName`");}catch(Throwable $e){}}
ob_end_flush();
