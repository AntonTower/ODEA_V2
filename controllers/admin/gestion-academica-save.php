<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../../config/paths.php';
require_once CONFIG_DIR . '/conexion.php';
require_once CONFIG_DIR . '/csrf.php';
require_once CONFIG_DIR . '/gestion_academica.php';

function academicaGuardarJson(int $status,string $message,array $extra=[]):void
{
    http_response_code($status);
    echo json_encode(array_merge(['status'=>$status<400?'ok':'error','message'=>$message],$extra),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function acaId($raw,string $label,bool $optional=false):?int
{
    if($optional&&($raw===null||$raw===''))return null;
    $value=filter_var($raw,FILTER_VALIDATE_INT);
    if(!$value||$value<1)throw new InvalidArgumentException("$label no es válido.");
    return (int)$value;
}
function acaDates(string $fromKey='vigencia_desde',string $toKey='vigencia_hasta'):array
{
    $from=academicaFecha($_POST[$fromKey]??null);$to=academicaFecha($_POST[$toKey]??null);
    if($from===false||$to===false)throw new InvalidArgumentException('La fecha debe tener formato válido AAAA-MM-DD.');
    if($from&&$to&&$to<$from)throw new InvalidArgumentException('La fecha final no puede ser anterior a la inicial.');
    return [$from,$to];
}
function acaAssertVersionParent(mysqli $conn,string $table,string $idCol,int $id,int $version):void
{
    $sql="SELECT 1 FROM `$table` WHERE `$idCol`=? AND id_version=?";$st=$conn->prepare($sql);$st->bind_param('ii',$id,$version);$st->execute();$ok=(bool)$st->get_result()->fetch_row();$st->close();
    if(!$ok)throw new InvalidArgumentException('Los registros seleccionados deben pertenecer a la misma versión borrador.');
}
function acaTableExists(mysqli $conn,string $table):bool
{
    $st=$conn->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=? LIMIT 1');$st->bind_param('s',$table);$st->execute();$ok=(bool)$st->get_result()->fetch_row();$st->close();return $ok;
}
function acaIdsPost(string $key):array
{
    $values=$_POST[$key]??[];if(!is_array($values))throw new InvalidArgumentException('Relación de datos no válida.');$ids=[];
    foreach($values as $raw){$id=acaId($raw,'Identificador');$ids[$id]=$id;}return array_values($ids);
}

if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){header('Allow: POST');academicaGuardarJson(405,'Método no permitido.');}
$transactionOpen=false;
try{
    $user=academicaUsuarioAdmin($conn);csrf_exigir();
    if(!academicaTablasInstaladas($conn))academicaGuardarJson(503,'El modelo académico no está instalado en esta base.');
    $action=(string)($_POST['accion']??'');$conn->begin_transaction();$transactionOpen=true;
    switch($action){
        case 'periodo_save':{
            $id=acaId($_POST['id_periodo']??null,'Periodo',true);$instrument=acaId($_POST['id_instrumento']??null,'Instrumento');
            $code=academicaTexto($_POST['codigo']??null,60,'Código');$name=academicaTexto($_POST['nombre']??null,150,'Nombre');[$from,$to]=acaDates('fecha_inicio','fecha_fin');
            $state=(string)($_POST['estado']??'BORRADOR');if(!in_array($state,['BORRADOR','ACTIVO','INACTIVO'],true))throw new InvalidArgumentException('Estado de periodo no válido.');
            if(!academicaFila($conn,'SELECT id_instrumento FROM instrumentos WHERE id_instrumento=? AND activo=1','i',[$instrument]))throw new InvalidArgumentException('Selecciona un instrumento activo.');
            if($id&&$state!=='ACTIVO'&&academicaFila($conn,"SELECT id_convocatoria FROM convocatorias WHERE id_periodo=? AND estado='ACTIVA' LIMIT 1 FOR UPDATE",'i',[$id]))throw new RuntimeException('No se puede desactivar un periodo con convocatorias activas.');
            if($id){$st=$conn->prepare('UPDATE periodos_academicos SET id_instrumento=?,codigo=?,nombre=?,fecha_inicio=?,fecha_fin=?,estado=? WHERE id_periodo=?');$st->bind_param('isssssi',$instrument,$code,$name,$from,$to,$state,$id);$st->execute();$st->close();$newId=$id;}
            else{$st=$conn->prepare('INSERT INTO periodos_academicos (id_instrumento,codigo,nombre,fecha_inicio,fecha_fin,estado) VALUES (?,?,?,?,?,?)');$st->bind_param('isssss',$instrument,$code,$name,$from,$to,$state);$st->execute();$newId=(int)$st->insert_id;$st->close();}
            break;
        }
        case 'periodo_eliminar':{
            $id=acaId($_POST['id_periodo']??null,'Periodo');
            $period=academicaFila($conn,'SELECT id_periodo FROM periodos_academicos WHERE id_periodo=? FOR UPDATE','i',[$id]);
            if(!$period)throw new RuntimeException('El periodo ya no existe.');
            if(academicaFila($conn,'SELECT id_convocatoria FROM convocatorias WHERE id_periodo=? LIMIT 1','i',[$id]))throw new RuntimeException('No se puede eliminar: una convocatoria ya está asociada a este periodo.');
            $st=$conn->prepare('DELETE FROM periodos_academicos WHERE id_periodo=?');$st->bind_param('i',$id);$st->execute();$st->close();$newId=$id;break;
        }
        case 'convocatoria_save':{
            $id=acaId($_POST['id_convocatoria']??null,'Convocatoria',true);$instrument=acaId($_POST['id_instrumento']??null,'Instrumento');$period=acaId($_POST['id_periodo']??null,'Periodo');
            $code=academicaTexto($_POST['codigo']??null,60,'Código');$name=academicaTexto($_POST['nombre']??null,150,'Nombre');$description=academicaTexto($_POST['descripcion']??'',10000,'Descripción',false);
            [$open,$close]=acaDates('fecha_apertura','fecha_cierre');
            $periodRow=academicaFila($conn,'SELECT id_periodo FROM periodos_academicos WHERE id_periodo=? AND id_instrumento=?','ii',[$period,$instrument]);if(!$periodRow)throw new InvalidArgumentException('El periodo debe pertenecer al mismo instrumento de la convocatoria.');
            if($id){$row=academicaFila($conn,'SELECT estado FROM convocatorias WHERE id_convocatoria=? FOR UPDATE','i',[$id]);if(!$row)throw new RuntimeException('La convocatoria no existe.');if($row['estado']!=='BORRADOR')throw new RuntimeException('Solo se editan convocatorias en borrador.');$st=$conn->prepare('UPDATE convocatorias SET id_instrumento=?,id_periodo=?,codigo=?,nombre=?,descripcion=?,fecha_apertura=?,fecha_cierre=? WHERE id_convocatoria=?');$st->bind_param('iisssssi',$instrument,$period,$code,$name,$description,$open,$close,$id);$st->execute();$st->close();$newId=$id;}
            else{$st=$conn->prepare("INSERT INTO convocatorias (id_instrumento,id_periodo,codigo,nombre,descripcion,fecha_apertura,fecha_cierre,estado) VALUES (?,?,?,?,?,?,?,'BORRADOR')");$st->bind_param('iisssss',$instrument,$period,$code,$name,$description,$open,$close);$st->execute();$newId=(int)$st->insert_id;$st->close();}
            break;
        }
        case 'convocatoria_estado':{
            $id=acaId($_POST['id_convocatoria']??null,'Convocatoria');$state=(string)($_POST['estado']??'');$row=academicaFila($conn,'SELECT c.estado,c.id_periodo,c.id_instrumento,p.estado AS estado_periodo FROM convocatorias c JOIN periodos_academicos p USING(id_periodo) WHERE c.id_convocatoria=? FOR UPDATE','i',[$id]);if(!$row)throw new RuntimeException('La convocatoria no existe.');
            if($state==='ACTIVA'){
                if($row['estado']!=='BORRADOR')throw new RuntimeException('Solo una convocatoria en borrador puede activarse.');if($row['estado_periodo']!=='ACTIVO')throw new RuntimeException('Activa primero el periodo académico asociado.');
                $published=academicaFila($conn,"SELECT id_version FROM versiones_criterios WHERE id_convocatoria=? AND estado='PUBLICADA' LIMIT 1",'i',[$id]);if(!$published)throw new RuntimeException('Publica una versión de criterios validada antes de activar la convocatoria.');
            }elseif($state==='CERRADA'){
                if($row['estado']!=='ACTIVA')throw new RuntimeException('Solo se pueden cerrar convocatorias activas.');
            }elseif($state==='INACTIVA'){
                if(!in_array($row['estado'],['BORRADOR','ACTIVA'],true))throw new RuntimeException('La convocatoria no puede pasar a inactiva desde su estado actual.');
            }else throw new InvalidArgumentException('Transición de estado no permitida.');
            $st=$conn->prepare('UPDATE convocatorias SET estado=? WHERE id_convocatoria=?');$st->bind_param('si',$state,$id);$st->execute();$st->close();$newId=$id;break;
        }
        case 'version_save':{
            $id=acaId($_POST['id_version']??null,'Versión',true);$call=acaId($_POST['id_convocatoria']??null,'Convocatoria');$code=academicaTexto($_POST['codigo_version']??null,40,'Código de versión');$name=academicaTexto($_POST['nombre']??'',150,'Nombre',false);[$from,$to]=acaDates();
            if(!academicaFila($conn,"SELECT id_convocatoria FROM convocatorias WHERE id_convocatoria=? AND estado IN ('BORRADOR','ACTIVA')",'i',[$call]))throw new InvalidArgumentException('Solo se crean versiones en convocatorias borrador o activas.');
            if($id){$row=academicaFila($conn,'SELECT estado,id_convocatoria FROM versiones_criterios WHERE id_version=? FOR UPDATE','i',[$id]);if(!$row||$row['estado']!=='BORRADOR')throw new RuntimeException('Solo se editan versiones en borrador.');if((int)$row['id_convocatoria']!==$call)throw new InvalidArgumentException('La versión pertenece a otra convocatoria.');$st=$conn->prepare('UPDATE versiones_criterios SET codigo_version=?,nombre=?,vigencia_desde=?,vigencia_hasta=? WHERE id_version=?');$st->bind_param('ssssi',$code,$name,$from,$to,$id);$st->execute();$st->close();$newId=$id;}
            else{$st=$conn->prepare("INSERT INTO versiones_criterios (id_convocatoria,codigo_version,nombre,vigencia_desde,vigencia_hasta,estado) VALUES (?,?,?, ?,?,'BORRADOR')");$st->bind_param('issss',$call,$code,$name,$from,$to);$st->execute();$newId=(int)$st->insert_id;$st->close();}break;
        }
        case 'version_publicar':{
            $id=acaId($_POST['id_version']??null,'Versión');$version=academicaVersionBorrador($conn,$id);
            $count=(int)$conn->query('SELECT COUNT(*) FROM categorias_academicas WHERE id_version='.(int)$id)->fetch_row()[0];if($count===0)throw new RuntimeException('Agrega al menos una categoría antes de publicar.');
            $count=(int)$conn->query('SELECT COUNT(*) FROM criterios_academicos WHERE id_version='.(int)$id)->fetch_row()[0];if($count===0)throw new RuntimeException('Agrega al menos un criterio antes de publicar.');
            $missing=(int)$conn->query("SELECT COUNT(*) FROM criterios_academicos c WHERE c.id_version=".(int)$id." AND NOT EXISTS (SELECT 1 FROM criterio_fuente_normativa cf JOIN fuentes_normativas f ON f.id_fuente=cf.id_fuente AND f.id_version=cf.id_version WHERE cf.id_criterio=c.id_criterio AND cf.id_version=c.id_version AND f.validacion_estado='VALIDADA')")->fetch_row()[0];if($missing>0)throw new RuntimeException('Asocia a cada criterio al menos una fuente normativa validada antes de publicar.');
            $st=$conn->prepare("UPDATE versiones_criterios SET estado='PUBLICADA',publicada_en=CURRENT_TIMESTAMP WHERE id_version=?");$st->bind_param('i',$id);$st->execute();$st->close();$newId=$id;break;
        }
        case 'version_clonar':{
            $sourceId=acaId($_POST['id_version']??null,'Versión de origen');$code=academicaTexto($_POST['codigo_version']??null,40,'Código de versión');$name=academicaTexto($_POST['nombre']??'',150,'Nombre',false);
            $src=academicaFila($conn,'SELECT * FROM versiones_criterios WHERE id_version=?','i',[$sourceId]);if(!$src)throw new RuntimeException('La versión de origen no existe.');
            $call=(int)$src['id_convocatoria'];if(!academicaFila($conn,"SELECT id_convocatoria FROM convocatorias WHERE id_convocatoria=? AND estado IN ('BORRADOR','ACTIVA')",'i',[$call]))throw new RuntimeException('La convocatoria de origen debe estar en borrador o activa para clonar la versión.');
            $st=$conn->prepare("INSERT INTO versiones_criterios (id_convocatoria,codigo_version,nombre,estado,vigencia_desde,vigencia_hasta) VALUES (?,?,?,'BORRADOR',?,?)");$st->bind_param('issss',$call,$code,$name,$src['vigencia_desde'],$src['vigencia_hasta']);$st->execute();$newId=(int)$st->insert_id;$st->close();
            $maps=['categorias_academicas'=>[],'criterios_academicos'=>[],'subcriterios_academicos'=>[],'requisitos_documentales'=>[],'fuentes_normativas'=>[]];
            $rs=$conn->query('SELECT * FROM categorias_academicas WHERE id_version='.$sourceId);foreach($rs as $r){$st=$conn->prepare('INSERT INTO categorias_academicas (id_version,codigo,nombre,descripcion,orden,activa,ponderacion) VALUES (?,?,?,?,?,?,?)');$st->bind_param('isssiid',$newId,$r['codigo'],$r['nombre'],$r['descripcion'],$r['orden'],$r['activa'],$r['ponderacion']);$st->execute();$maps['categorias_academicas'][(int)$r['id_categoria']]=(int)$st->insert_id;$st->close();}
            $rs=$conn->query('SELECT * FROM criterios_academicos WHERE id_version='.$sourceId);foreach($rs as $r){$cat=$maps['categorias_academicas'][(int)$r['id_categoria']];$st=$conn->prepare('INSERT INTO criterios_academicos (id_version,id_categoria,codigo,nombre,descripcion,orden,activo,ponderacion,regla_vigencia_json) VALUES (?,?,?,?,?,?,?,?,?)');$st->bind_param('iisssiids',$newId,$cat,$r['codigo'],$r['nombre'],$r['descripcion'],$r['orden'],$r['activo'],$r['ponderacion'],$r['regla_vigencia_json']);$st->execute();$maps['criterios_academicos'][(int)$r['id_criterio']]=(int)$st->insert_id;$st->close();}
            $rs=$conn->query('SELECT * FROM subcriterios_academicos WHERE id_version='.$sourceId);foreach($rs as $r){$criterion=$maps['criterios_academicos'][(int)$r['id_criterio']];$st=$conn->prepare('INSERT INTO subcriterios_academicos (id_version,id_criterio,codigo,nombre,descripcion,orden,activo,ponderacion,regla_vigencia_json) VALUES (?,?,?,?,?,?,?,?,?)');$st->bind_param('iisssiids',$newId,$criterion,$r['codigo'],$r['nombre'],$r['descripcion'],$r['orden'],$r['activo'],$r['ponderacion'],$r['regla_vigencia_json']);$st->execute();$maps['subcriterios_academicos'][(int)$r['id_subcriterio']]=(int)$st->insert_id;$st->close();}
            $rs=$conn->query('SELECT * FROM requisitos_documentales WHERE id_version='.$sourceId);foreach($rs as $r){$st=$conn->prepare('INSERT INTO requisitos_documentales (id_version,codigo,nombre,descripcion,obligatorio,activo,vigencia_desde,vigencia_hasta,regla_vigencia_json) VALUES (?,?,?,?,?,?,?,?,?)');$st->bind_param('isssiisss',$newId,$r['codigo'],$r['nombre'],$r['descripcion'],$r['obligatorio'],$r['activo'],$r['vigencia_desde'],$r['vigencia_hasta'],$r['regla_vigencia_json']);$st->execute();$maps['requisitos_documentales'][(int)$r['id_requisito']]=(int)$st->insert_id;$st->close();}
            foreach(['requisito_criterio'=>['id_requisito','id_criterio'],'requisito_subcriterio'=>['id_requisito','id_subcriterio']] as $table=>$cols){$rs=$conn->query("SELECT * FROM `$table` WHERE id_version=$sourceId");foreach($rs as $r){[$rCol,$targetCol]=$cols;$st=$conn->prepare("INSERT INTO `$table` (id_version,id_requisito,`$targetCol`) VALUES (?,?,?)");$left=$maps['requisitos_documentales'][(int)$r['id_requisito']];$right=$maps[$table==='requisito_criterio'?'criterios_academicos':'subcriterios_academicos'][(int)$r[$targetCol]];$st->bind_param('iii',$newId,$left,$right);$st->execute();$st->close();}}
            $rs=$conn->query('SELECT * FROM requisito_tipo_documental WHERE id_requisito IN (SELECT id_requisito FROM requisitos_documentales WHERE id_version='.$sourceId.')');foreach($rs as $r){$req=$maps['requisitos_documentales'][(int)$r['id_requisito']];$st=$conn->prepare('INSERT INTO requisito_tipo_documental (id_requisito,id_tipo_evidencia) VALUES (?,?)');$st->bind_param('ii',$req,$r['id_tipo_evidencia']);$st->execute();$st->close();}
            $rs=$conn->query('SELECT * FROM fuentes_normativas WHERE id_version='.$sourceId);foreach($rs as $r){$st=$conn->prepare("INSERT INTO fuentes_normativas (id_version,nombre_documento,fecha_publicacion,vigencia_desde,vigencia_hasta,version_documento,referencia_url,validacion_estado) VALUES (?,?,?,?,?,?,?,'PENDIENTE')");$st->bind_param('issssss',$newId,$r['nombre_documento'],$r['fecha_publicacion'],$r['vigencia_desde'],$r['vigencia_hasta'],$r['version_documento'],$r['referencia_url']);$st->execute();$maps['fuentes_normativas'][(int)$r['id_fuente']]=(int)$st->insert_id;$st->close();}
            $rs=$conn->query('SELECT * FROM criterio_fuente_normativa WHERE id_version='.$sourceId);foreach($rs as $r){$criterion=$maps['criterios_academicos'][(int)$r['id_criterio']];$source=$maps['fuentes_normativas'][(int)$r['id_fuente']];$st=$conn->prepare('INSERT INTO criterio_fuente_normativa (id_version,id_criterio,id_fuente) VALUES (?,?,?)');$st->bind_param('iii',$newId,$criterion,$source);$st->execute();$st->close();}
            // Si está instalado el motor multiinstrumento, conserva actividades y reglas versionadas.
            if(acaTableExists($conn,'actividades_academicas')){
                $maps['actividades_academicas']=[];$rs=$conn->query('SELECT * FROM actividades_academicas WHERE id_version='.$sourceId);
                foreach($rs as $r){$st=$conn->prepare('INSERT INTO actividades_academicas (id_version,codigo,nombre,descripcion,activa) VALUES (?,?,?,?,?)');$st->bind_param('isssi',$newId,$r['codigo'],$r['nombre'],$r['descripcion'],$r['activa']);$st->execute();$maps['actividades_academicas'][(int)$r['id_actividad']]=(int)$st->insert_id;$st->close();}
                if(acaTableExists($conn,'criterio_actividad')){$rs=$conn->query('SELECT * FROM criterio_actividad WHERE id_version='.$sourceId);foreach($rs as $r){$criterion=$maps['criterios_academicos'][(int)$r['id_criterio']];$activity=$maps['actividades_academicas'][(int)$r['id_actividad']];$st=$conn->prepare('INSERT INTO criterio_actividad (id_version,id_criterio,id_actividad) VALUES (?,?,?)');$st->bind_param('iii',$newId,$criterion,$activity);$st->execute();$st->close();}}
                if(acaTableExists($conn,'reglas_clasificacion_documental')){$rs=$conn->query('SELECT * FROM reglas_clasificacion_documental WHERE id_version='.$sourceId);foreach($rs as $r){$criterion=$maps['criterios_academicos'][(int)$r['id_criterio']];$sub=$r['id_subcriterio']===null?null:$maps['subcriterios_academicos'][(int)$r['id_subcriterio']];$activity=$maps['actividades_academicas'][(int)$r['id_actividad']];$source=$maps['fuentes_normativas'][(int)$r['id_fuente']];$st=$conn->prepare('INSERT INTO reglas_clasificacion_documental (id_version,id_criterio,id_subcriterio,id_actividad,id_fuente,regla_json,activa) VALUES (?,?,?,?,?,?,?)');$st->bind_param('iiiiisi',$newId,$criterion,$sub,$activity,$source,$r['regla_json'],$r['activa']);$st->execute();$st->close();}}
            }
            break;
        }
        case 'categoria_save':{
            $id=acaId($_POST['id_categoria']??null,'Categoría',true);$version=acaId($_POST['id_version']??null,'Versión');academicaVersionBorrador($conn,$version);$code=academicaTexto($_POST['codigo']??null,60,'Código');$name=academicaTexto($_POST['nombre']??null,150,'Nombre');$desc=academicaTexto($_POST['descripcion']??'',5000,'Descripción',false);$order=acaId($_POST['orden']??'1','Orden');$weight=trim((string)($_POST['ponderacion']??''));$weight=$weight===''?null:filter_var($weight,FILTER_VALIDATE_FLOAT);if($weight!==null&&($weight===false||$weight<0))throw new InvalidArgumentException('Ponderación no válida.');$active=isset($_POST['activa'])?1:0;
            if($id){acaAssertVersionParent($conn,'categorias_academicas','id_categoria',$id,$version);$st=$conn->prepare('UPDATE categorias_academicas SET codigo=?,nombre=?,descripcion=?,orden=?,activa=?,ponderacion=? WHERE id_categoria=?');$st->bind_param('sssiidi',$code,$name,$desc,$order,$active,$weight,$id);$st->execute();$st->close();$newId=$id;}else{$st=$conn->prepare('INSERT INTO categorias_academicas (id_version,codigo,nombre,descripcion,orden,activa,ponderacion) VALUES (?,?,?,?,?,?,?)');$st->bind_param('isssiid',$version,$code,$name,$desc,$order,$active,$weight);$st->execute();$newId=(int)$st->insert_id;$st->close();}break;
        }
        case 'criterio_save':{
            $id=acaId($_POST['id_criterio']??null,'Criterio',true);$version=acaId($_POST['id_version']??null,'Versión');$category=acaId($_POST['id_categoria']??null,'Categoría');academicaVersionBorrador($conn,$version);acaAssertVersionParent($conn,'categorias_academicas','id_categoria',$category,$version);$code=academicaTexto($_POST['codigo']??null,60,'Código');$name=academicaTexto($_POST['nombre']??null,180,'Nombre');$desc=academicaTexto($_POST['descripcion']??'',5000,'Descripción',false);$order=acaId($_POST['orden']??'1','Orden');$weight=trim((string)($_POST['ponderacion']??''));$weight=$weight===''?null:filter_var($weight,FILTER_VALIDATE_FLOAT);if($weight!==null&&($weight===false||$weight<0))throw new InvalidArgumentException('Ponderación no válida.');$rule=trim((string)($_POST['regla_vigencia_json']??''));if($rule!==''&&json_decode($rule,true)===null)throw new InvalidArgumentException('La regla opcional debe ser JSON válido.');$rule=$rule===""?null:$rule;$active=isset($_POST['activo'])?1:0;
            if($id){acaAssertVersionParent($conn,'criterios_academicos','id_criterio',$id,$version);$st=$conn->prepare('UPDATE criterios_academicos SET id_categoria=?,codigo=?,nombre=?,descripcion=?,orden=?,activo=?,ponderacion=?,regla_vigencia_json=? WHERE id_criterio=?');$st->bind_param('isssiidsi',$category,$code,$name,$desc,$order,$active,$weight,$rule,$id);$st->execute();$st->close();$newId=$id;}else{$st=$conn->prepare('INSERT INTO criterios_academicos (id_version,id_categoria,codigo,nombre,descripcion,orden,activo,ponderacion,regla_vigencia_json) VALUES (?,?,?,?,?,?,?,?,?)');$st->bind_param('iisssiids',$version,$category,$code,$name,$desc,$order,$active,$weight,$rule);$st->execute();$newId=(int)$st->insert_id;$st->close();}break;
        }
        case 'subcriterio_save':{
            $id=acaId($_POST['id_subcriterio']??null,'Subcriterio',true);$version=acaId($_POST['id_version']??null,'Versión');$criterion=acaId($_POST['id_criterio']??null,'Criterio');academicaVersionBorrador($conn,$version);acaAssertVersionParent($conn,'criterios_academicos','id_criterio',$criterion,$version);$code=academicaTexto($_POST['codigo']??null,60,'Código');$name=academicaTexto($_POST['nombre']??null,180,'Nombre');$desc=academicaTexto($_POST['descripcion']??'',5000,'Descripción',false);$order=acaId($_POST['orden']??'1','Orden');$weight=trim((string)($_POST['ponderacion']??''));$weight=$weight===''?null:filter_var($weight,FILTER_VALIDATE_FLOAT);if($weight!==null&&($weight===false||$weight<0))throw new InvalidArgumentException('Ponderación no válida.');$rule=trim((string)($_POST['regla_vigencia_json']??''));if($rule!==''&&json_decode($rule,true)===null)throw new InvalidArgumentException('La regla opcional debe ser JSON válido.');$rule=$rule===""?null:$rule;$active=isset($_POST['activo'])?1:0;
            if($id){acaAssertVersionParent($conn,'subcriterios_academicos','id_subcriterio',$id,$version);$st=$conn->prepare('UPDATE subcriterios_academicos SET id_criterio=?,codigo=?,nombre=?,descripcion=?,orden=?,activo=?,ponderacion=?,regla_vigencia_json=? WHERE id_subcriterio=?');$st->bind_param('isssiidsi',$criterion,$code,$name,$desc,$order,$active,$weight,$rule,$id);$st->execute();$st->close();$newId=$id;}else{$st=$conn->prepare('INSERT INTO subcriterios_academicos (id_version,id_criterio,codigo,nombre,descripcion,orden,activo,ponderacion,regla_vigencia_json) VALUES (?,?,?,?,?,?,?,?,?)');$st->bind_param('iisssiids',$version,$criterion,$code,$name,$desc,$order,$active,$weight,$rule);$st->execute();$newId=(int)$st->insert_id;$st->close();}break;
        }
        case 'requisito_save':{
            $id=acaId($_POST['id_requisito']??null,'Requisito',true);$version=acaId($_POST['id_version']??null,'Versión');academicaVersionBorrador($conn,$version);$code=academicaTexto($_POST['codigo']??null,60,'Código');$name=academicaTexto($_POST['nombre']??null,180,'Nombre');$desc=academicaTexto($_POST['descripcion']??'',5000,'Descripción',false);$mandatory=(string)($_POST['obligatorio']??'');$mandatory=$mandatory===''?null:(in_array($mandatory,['0','1'],true)?(int)$mandatory:null);if($mandatory===null&&isset($_POST['obligatorio'])&&$_POST['obligatorio']!=='')throw new InvalidArgumentException('Obligatoriedad no válida.');$active=isset($_POST['activo'])?1:0;[$from,$to]=acaDates();$rule=trim((string)($_POST['regla_vigencia_json']??''));if($rule!==''&&json_decode($rule,true)===null)throw new InvalidArgumentException('La regla opcional debe ser JSON válido.');$rule=$rule===""?null:$rule;
            if($id){acaAssertVersionParent($conn,'requisitos_documentales','id_requisito',$id,$version);$st=$conn->prepare('UPDATE requisitos_documentales SET codigo=?,nombre=?,descripcion=?,obligatorio=?,activo=?,vigencia_desde=?,vigencia_hasta=?,regla_vigencia_json=? WHERE id_requisito=?');$st->bind_param('sssiisssi',$code,$name,$desc,$mandatory,$active,$from,$to,$rule,$id);$st->execute();$st->close();$newId=$id;}
            else{$st=$conn->prepare('INSERT INTO requisitos_documentales (id_version,codigo,nombre,descripcion,obligatorio,activo,vigencia_desde,vigencia_hasta,regla_vigencia_json) VALUES (?,?,?,?,?,?,?,?,?)');$st->bind_param('isssiisss',$version,$code,$name,$desc,$mandatory,$active,$from,$to,$rule);$st->execute();$newId=(int)$st->insert_id;$st->close();}
            foreach(acaIdsPost('criterios') as $criterion){acaAssertVersionParent($conn,'criterios_academicos','id_criterio',$criterion,$version);$st=$conn->prepare('INSERT IGNORE INTO requisito_criterio (id_version,id_requisito,id_criterio) VALUES (?,?,?)');$st->bind_param('iii',$version,$newId,$criterion);$st->execute();$st->close();}
            foreach(acaIdsPost('subcriterios') as $sub){acaAssertVersionParent($conn,'subcriterios_academicos','id_subcriterio',$sub,$version);$st=$conn->prepare('INSERT IGNORE INTO requisito_subcriterio (id_version,id_requisito,id_subcriterio) VALUES (?,?,?)');$st->bind_param('iii',$version,$newId,$sub);$st->execute();$st->close();}
            foreach(acaIdsPost('tipos_documentales') as $type){if(!academicaFila($conn,'SELECT id_tipo_evidencia FROM tipos_de_evidencia WHERE id_tipo_evidencia=?','i',[$type]))throw new InvalidArgumentException('Uno de los tipos documentales no existe.');$st=$conn->prepare('INSERT IGNORE INTO requisito_tipo_documental (id_requisito,id_tipo_evidencia) VALUES (?,?)');$st->bind_param('ii',$newId,$type);$st->execute();$st->close();}break;
        }
        case 'fuente_save':{
            $id=acaId($_POST['id_fuente']??null,'Fuente',true);$version=acaId($_POST['id_version']??null,'Versión');academicaVersionBorrador($conn,$version);$name=academicaTexto($_POST['nombre_documento']??null,255,'Nombre del documento');$publication=academicaFecha($_POST['fecha_publicacion']??null);[$from,$to]=acaDates();if($publication===false)throw new InvalidArgumentException('Fecha de publicación no válida.');$docVersion=academicaTexto($_POST['version_documento']??'',100,'Versión documental',false);$url=academicaTexto($_POST['referencia_url']??'',2000,'URL',false);if($url&&!filter_var($url,FILTER_VALIDATE_URL))throw new InvalidArgumentException('La referencia debe ser una URL válida.');$state=(string)($_POST['validacion_estado']??'PENDIENTE');if(!in_array($state,['PENDIENTE','VALIDADA','RECHAZADA'],true))throw new InvalidArgumentException('Estado de validación no válido.');$responsible=$state==='VALIDADA'?(int)$user['id_usuario']:null;$validated=$state==='VALIDADA'?date('Y-m-d H:i:s'):null;
            if($id){$st=$conn->prepare('UPDATE fuentes_normativas SET nombre_documento=?,fecha_publicacion=?,vigencia_desde=?,vigencia_hasta=?,version_documento=?,referencia_url=?,validacion_estado=?,id_responsable_validacion=?,validada_en=? WHERE id_fuente=? AND id_version=?');$st->bind_param('sssssssisii',$name,$publication,$from,$to,$docVersion,$url,$state,$responsible,$validated,$id,$version);$st->execute();$st->close();$newId=$id;}
            else{$st=$conn->prepare('INSERT INTO fuentes_normativas (id_version,nombre_documento,fecha_publicacion,vigencia_desde,vigencia_hasta,version_documento,referencia_url,validacion_estado,id_responsable_validacion,validada_en) VALUES (?,?,?,?,?,?,?,?,?,?)');$st->bind_param('isssssssis',$version,$name,$publication,$from,$to,$docVersion,$url,$state,$responsible,$validated);$st->execute();$newId=(int)$st->insert_id;$st->close();}break;
        }
        case 'fuente_criterio':{
            $version=acaId($_POST['id_version']??null,'Versión');academicaVersionBorrador($conn,$version);$criterion=acaId($_POST['id_criterio']??null,'Criterio');$source=acaId($_POST['id_fuente']??null,'Fuente normativa');acaAssertVersionParent($conn,'criterios_academicos','id_criterio',$criterion,$version);acaAssertVersionParent($conn,'fuentes_normativas','id_fuente',$source,$version);$st=$conn->prepare('INSERT IGNORE INTO criterio_fuente_normativa (id_version,id_criterio,id_fuente) VALUES (?,?,?)');$st->bind_param('iii',$version,$criterion,$source);$st->execute();$st->close();$newId=$criterion;break;
        }
        default:throw new InvalidArgumentException('Operación académica no reconocida.');
    }
    $conn->commit();$transactionOpen=false;academicaGuardarJson(200,'Cambios guardados correctamente.',['id'=>$newId??null,'accion'=>$action]);
}catch(InvalidArgumentException $e){if($transactionOpen)$conn->rollback();academicaGuardarJson(422,$e->getMessage());}
catch(mysqli_sql_exception $e){if($transactionOpen)$conn->rollback();error_log('Conflicto al guardar configuración académica: '.$e->getMessage());academicaGuardarJson(409,'No se guardó el registro. Revisa códigos duplicados y relaciones seleccionadas.');}
catch(RuntimeException $e){if($transactionOpen)$conn->rollback();academicaGuardarJson(http_response_code()===403?403:409,$e->getMessage());}
catch(Throwable $e){if($transactionOpen)$conn->rollback();error_log('Error al guardar la configuración académica: '.$e->getMessage());academicaGuardarJson(500,'No se pudo guardar la configuración académica.');}
