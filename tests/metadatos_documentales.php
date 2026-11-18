<?php
if(PHP_SAPI!=='cli')exit;
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);chdir(dirname(__DIR__));require_once 'config/paths.php';require_once CONFIG_DIR.'/esquemas_metadatos_documentales.php';require_once CONFIG_DIR.'/seguridad.php';require_once 'services/documentos/DocumentoProcessor.php';
$admin=new mysqli('127.0.0.1','root','','',3308);$admin->set_charset('utf8mb4');$dbName='gestor_test_metadata_'.bin2hex(random_bytes(5));$created=false;$checks=0;
function metadataCheck(bool $ok,string $label):void{global $checks;if(!$ok)throw new RuntimeException($label);$checks++;echo "OK: $label\n";}
try{
 $sql="CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci; USE `$dbName`;\n".file_get_contents('sql/gestor_evidencia_produccion_v2.sql');
 foreach(['20261118_integridad_documental.sql','20261119_modelo_academico_versionado.sql','20261120_historial_estados_evaluacion.sql','20261121_procesamiento_documental.sql','20261122_revision_metadatos_documentales.sql'] as $file)$sql.="\n".file_get_contents('sql/migrations/'.$file);
 $admin->multi_query($sql);$created=true;do{if($r=$admin->store_result())$r->free();}while($admin->more_results()&&$admin->next_result());
 $db=new mysqli('127.0.0.1','root','',$dbName,3308);$db->set_charset('utf8mb4');
 $sample="ARTÍCULO\nTítulo: Redes comunitarias de aprendizaje\nAutores: Ana Pérez, Luis Gómez y María Ruiz\nRevista: Ciencia Actual\nAño: 2024\nDOI: 10.1234/redes.2024\nISSN: 1234-567X\nEditorial: Ediciones Académicas";
 $metadata=metadatosExtraer($sample,['tipo_documental'=>'Artículo científico']);$fields=$metadata['campos'];$expected=['titulo'=>'Redes comunitarias de aprendizaje','autores'=>['Ana Pérez','Luis Gómez','María Ruiz'],'revista'=>'Ciencia Actual','anio'=>'2024','doi'=>'10.1234/redes.2024','issn'=>'1234-567X','editorial'=>'Ediciones Académicas'];$correct=0;
 foreach($expected as $field=>$value){$actual=$fields[$field]['valor']??null;if($actual===$value)$correct++;}
 metadataCheck($metadata['esquema']==='articulo_cientifico','El tipo documental configura un esquema extensible de artículo');
 $identified=(new DocumentoIdentificadorReglas())->identificar($sample,['tipo_documental'=>'Artículo científico']);$viaProvider=(new DocumentoExtractorMetadatosReglas())->extraer($sample,['documento'=>['tipo_documental'=>'Artículo científico'],'metodo'=>'pdftotext','esquema_identificado'=>$identified]);
 metadataCheck($viaProvider['campos']['doi']['valor']==='10.1234/redes.2024','Los adaptadores del pipeline conectan identificación y extracción estructurada');
 $fromText=metadatosIdentificarEsquema(['tipo_documental'=>'Documento académico'],"ISSN: 1234-567X\nDOI: 10.1000/example");metadataCheck($fromText['tipo']==='articulo_cientifico'&&$fromText['estado']==='inferido','La identificación puede usar indicios textuales y queda marcada como inferida');
 metadataCheck($correct===7,'Precisión exacta por campo en artículo sintético: 7/7 (100%); campos='.json_encode(array_map(static fn($f)=>$f['valor']??null,$fields),JSON_UNESCAPED_UNICODE));
 metadataCheck($fields['doi']['estado']==='encontrado_explicito'&&$fields['doi']['fuente']['linea']===6,'Cada dato explícito conserva campo, línea fuente y confianza');
 $ambiguous=metadatosExtraer("Artículo\nDOI: 10.1111/a\nDOI: 10.2222/b",['tipo_documental'=>'Artículo científico']);
 metadataCheck($ambiguous['campos']['doi']['estado']==='ambiguo'&&count($ambiguous['campos']['doi']['candidatos'])===2,'Datos contradictorios quedan ambiguos sin elegir uno');
 metadataCheck(count($ambiguous['campos']['doi']['fuentes_candidatas'])===2,'Los candidatos ambiguos conservan su fuente');
 $missing=metadatosExtraer("Constancia de participación académica\nParticipante: Persona Sintética",['tipo_documental'=>'Constancia']);
 metadataCheck($missing['campos']['fecha']['valor']===null&&$missing['campos']['fecha']['estado']==='no_encontrado','Un dato ausente se conserva como null');
 $fixtures=[
   ['Libro','Título: Sistemas abiertos\nAutor: Ana Pérez\nEditorial: Ediciones Uno\nISBN: 978-1-23456-789-0\nAño: 2023\nCapítulo: Redes\nPáginas: 10-24','libro_capitulo'],
   ['Constancia','Participante: Ana Pérez\nActividad: Taller de datos\nInstitución: Universidad Pública\nFecha: 18/11/2025\n20 horas\nParticipación: Asistente','constancia_diploma'],
   ['Tesis','Estudiante: Luis Gómez\nTítulo de tesis: Datos locales\nGrado: Maestría\nInstitución: Universidad Pública','tesis_tutoria'],
   ['Ponencia','Evento: Congreso de Sistemas\nPonencia: Redes académicas\nPonente: María Ruiz\nFecha: 10/10/2024\nOrganizado por: Asociación Sintética\nModalidad: Presencial','ponencia_congreso'],
 ];$schemaOk=true;foreach($fixtures as [$type,$text,$expectedSchema]){$schemaOk=$schemaOk&&metadatosExtraer($text,['tipo_documental'=>$type])['esquema']===$expectedSchema;}
 metadataCheck($schemaOk,'Libros, constancias, tesis y ponencias activan sus esquemas correspondientes');
 $low=metadatosExtraer('Este documento tiene texto deteriorado e incompleto',['tipo_documental'=>'Documento académico'],'ocr_pdf');
 metadataCheck($low['campos']['titulo']['estado']==='inferido'&&$low['campos']['titulo']['confianza']<0.5,'Texto poco legible queda como inferido con confianza reducida');
 metadataCheck(metadatosValidarEstructura($metadata)&&!metadatosValidarEstructura(['campos'=>[]]),'La estructura de extracción se valida antes de persistir');

 $file='sintetico_'.bin2hex(random_bytes(5)).'.pdf';$content='%PDF-1.4 synthetic';$sha=hash('sha256',$content);$mime='application/pdf';$bytes=strlen($content);$title='Artículo sintético de prueba';
 $st=$db->prepare('INSERT INTO evidencias (titulo,archivo,archivo_sha256,archivo_mime,archivo_bytes,id_docente,id_tipo_evidencia,ocultar) VALUES (?,?,?,?,?,2,1,0)');$st->bind_param('ssssi',$title,$file,$sha,$mime,$bytes);$st->execute();$evidence=(int)$st->insert_id;$st->close();
 $pipeline='1';$provider='disabled';$state='PROCESADO';$st=$db->prepare('INSERT INTO trabajos_documentales (sha256,mime,bytes,pipeline_version,proveedor_solicitado,estado) VALUES (?,?,?,?,?,?)');$st->bind_param('ssisss',$sha,$mime,$bytes,$pipeline,$provider,$state);$st->execute();$job=(int)$st->insert_id;$st->close();$origin='EVIDENCIA';$originId=(string)$evidence;$st=$db->prepare('INSERT INTO origenes_trabajo_documental (id_trabajo,id_evidencia,tipo_origen,id_origen) VALUES (?,?,?,?)');$st->bind_param('iiss',$job,$evidence,$origin,$originId);$st->execute();$st->close();
 $tech=['mime'=>$mime,'extraccion'=>$metadata];$json=json_encode($tech,JSON_UNESCAPED_UNICODE);$st=$db->prepare('INSERT INTO resultados_trabajo_documental (id_trabajo,texto_extraido,metadatos_json) VALUES (?,?,?)');$st->bind_param('iss',$job,$sample,$json);$st->execute();$st->close();
 $input=[];foreach($fields as $id=>$f){$v=$f['valor'];if(is_array($v))$v=implode('; ',$v);$input[$id]=['valor'=>$v,'estado'=>'PENDIENTE'];}$input['titulo']['estado']='CONFIRMADO';
 $rev=metadatosGuardarRevision($db,$evidence,$job,2,0,$input,false);metadataCheck($rev===1,'La primera validación humana se agrega al historial');
 $permissionEvidence=['id_docente'=>2,'ocultar'=>0];metadataCheck(seguridadPuedeEditarEvidencia(['id_usuario'=>2,'rol'=>4],$permissionEvidence)&&!seguridadPuedeEditarEvidencia(['id_usuario'=>4,'rol'=>4],$permissionEvidence)&&!seguridadPuedeEditarEvidencia(['id_usuario'=>3,'rol'=>3],$permissionEvidence),'La revisión solo permite al propietario o a roles administrativos');
 $input['titulo']['valor']='Título modificado';$input['titulo']['estado']='CORREGIDO';$blocked=false;try{metadatosGuardarRevision($db,$evidence,$job,2,1,$input,false);}catch(DomainException $ex){$blocked=true;}
 metadataCheck($blocked,'Un campo confirmado no se sobrescribe sin autorización explícita');
 $rev2=metadatosGuardarRevision($db,$evidence,$job,2,1,$input,true);metadataCheck($rev2===2,'La autorización explícita añade una revisión nueva');
 $count=(int)$db->query("SELECT COUNT(*) FROM revisiones_metadatos_documentales WHERE id_evidencia=$evidence AND id_trabajo=$job")->fetch_row()[0];metadataCheck($count===2,'La corrección conserva las dos versiones históricas');
 $stale=false;try{metadatosGuardarRevision($db,$evidence,$job,2,1,$input,true);}catch(DomainException $ex){$stale=true;}metadataCheck($stale,'La revisión concurrente obsoleta se rechaza');
 $immutable=false;try{$db->query("UPDATE revisiones_metadatos_documentales SET numero_revision=8 WHERE id_evidencia=$evidence");}catch(mysqli_sql_exception $ex){$immutable=true;}metadataCheck($immutable,'La base de datos impide editar eventos de revisión guardados');
 $actualSha=(string)$db->query("SELECT archivo_sha256 FROM evidencias WHERE id_evidencia=$evidence")->fetch_row()[0];metadataCheck(hash_equals($sha,$actualSha),'La revisión no modifica la referencia SHA-256 del original');
 $db->close();echo "$checks pruebas de extracción y revisión correctas.\n";
}finally{if($created)try{$admin->query("DROP DATABASE `$dbName`");}catch(Throwable $e){}}
