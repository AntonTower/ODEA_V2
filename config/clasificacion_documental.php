<?php
require_once __DIR__.'/esquemas_metadatos_documentales.php';

function clasificacionTieneInfraestructura(mysqli $db): bool
{
    $need=['actividades_academicas','criterio_actividad','reglas_clasificacion_documental','evidencia_aplicaciones_academicas','propuestas_clasificacion_documental','historial_clasificacion_documental','versiones_criterios','fuentes_normativas','revisiones_metadatos_documentales','convocatorias','periodos_academicos','criterios_academicos','subcriterios_academicos','criterio_fuente_normativa','requisitos_documentales','requisito_criterio','requisito_subcriterio','requisito_tipo_documental'];
    $in=implode(',',array_fill(0,count($need),'?'));$types=str_repeat('s',count($need));$stmt=$db->prepare("SELECT COUNT(*) n FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ($in)");$stmt->bind_param($types,...$need);$stmt->execute();$ok=(int)$stmt->get_result()->fetch_assoc()['n']===count($need);$stmt->close();return $ok;
}

function clasificacionValorCampo(array $campos,string $campo): array
{
    $field=$campos[$campo]??null;if(!is_array($field))return ['known'=>false,'value'=>null,'state'=>'no_encontrado'];
    $state=(string)($field['estado']??$field['estado_extraccion']??'no_encontrado');$value=$field['valor']??null;
    if(in_array($state,['RECHAZADO','no_encontrado','ambiguo','PENDIENTE'],true)||$value===null||$value==='')return ['known'=>false,'value'=>$value,'state'=>$state];
    return ['known'=>true,'value'=>$value,'state'=>$state];
}

function clasificacionCompararRegla(array $rule,array $fields,array $context): array
{
    if((int)($rule['version']??0)!==1||!is_array($rule['condiciones']??null)||!$rule['condiciones'])return ['state'=>'INFORMACION_INSUFICIENTE','satisfied'=>[],'missing'=>[],'unknown'=>['La regla no define condiciones documentales versionadas.'],'used'=>[],'confidence'=>null];
    $satisfied=[];$missing=[];$unknown=[];$used=[];$confidence=1.0;
    foreach($rule['condiciones'] as $condition){
        if(!is_array($condition)||!isset($condition['campo'],$condition['operador'])){$unknown[]='Configuración de condición inválida';continue;}
        $name=(string)$condition['campo'];$operator=(string)$condition['operador'];$field=clasificacionValorCampo($fields,$name);$evidence=['campo'=>$name,'operador'=>$operator,'valor'=>$field['value'],'estado_fuente'=>$field['state']];$ok=null;
        if($operator==='tipo_evidencia_id'){$ok=(int)$context['id_tipo_evidencia']===(int)($condition['valor']??-1);$evidence['valor_evidencia']=(int)$context['id_tipo_evidencia'];}
        elseif($operator==='presente')$ok=$field['known']?($field['value']!==''&&$field['value']!==[]):null;
        elseif(!$field['known'])$ok=null;
        elseif($operator==='igual')$ok=mb_strtolower(is_array($field['value'])?implode(' ', $field['value']):(string)$field['value'],'UTF-8')===mb_strtolower((string)($condition['valor']??''),'UTF-8');
        elseif($operator==='contiene'){$hay=is_array($field['value'])?implode(' ',$field['value']):(string)$field['value'];$ok=mb_stripos($hay,(string)($condition['valor']??''),0,'UTF-8')!==false;}
        elseif($operator==='anio_en_periodo'){$year=preg_match('/^(?:19|20)\d{2}$/',(string)$field['value'])?(int)$field['value']:null;$start=$context['periodo_inicio']? (int)substr($context['periodo_inicio'],0,4):null;$end=$context['periodo_fin']?(int)substr($context['periodo_fin'],0,4):null;$ok=($year!==null&&$start!==null&&$end!==null)?($year>=$start&&$year<=$end):null;$evidence['periodo']=['inicio'=>$context['periodo_inicio'],'fin'=>$context['periodo_fin']];}
        elseif($operator==='fecha_en_periodo'){$date=null;foreach(['!Y-m-d','!d/m/Y','!d-m-Y','!j/n/Y'] as $format){$parsed=DateTimeImmutable::createFromFormat($format,(string)$field['value']);if($parsed&&$parsed->format(str_replace('!','',$format))===(string)$field['value']){$date=$parsed->format('Y-m-d');break;}}$ok=($date&&$context['periodo_inicio']&&$context['periodo_fin'])?($date>=$context['periodo_inicio']&&$date<=$context['periodo_fin']):null;$evidence['fecha_normalizada']=$date;$evidence['periodo']=['inicio'=>$context['periodo_inicio'],'fin'=>$context['periodo_fin']];}
        elseif($operator==='incluye_docente'){
            $values=is_array($field['value'])?$field['value']:preg_split('/\s*(?:,|;|\s+y\s+|\s+and\s+)\s*/iu',(string)$field['value'],-1,PREG_SPLIT_NO_EMPTY);
            $owner=mb_strtolower(trim((string)$context['docente_nombre']),'UTF-8');$ok=null;
            foreach($values as $author){$normalized=mb_strtolower(trim((string)$author),'UTF-8');if($normalized!==''&&$owner!==''&&($normalized===$owner||str_contains($normalized,$owner))){$ok=null;$evidence['posible_mencion_docente']=true;break;}}
            $unknown[]='La autoría o pertenencia del docente requiere validación humana.';
        }
        else {$unknown[]='Operador no soportado: '.$operator;continue;}
        $evidence['resultado']=$ok===true?'cumplido':($ok===false?'no_cumplido':'por_validar');$used[]=$evidence;
        if($ok===true){$satisfied[]=$name;$fieldConfidence=(float)($fields[$name]['confianza']??0.5);$confidence=min($confidence,max(0,min(1,$fieldConfidence)));}
        elseif($ok===false)$missing[]=$name;
        else $unknown[]='Sin información validada para '.$name;
    }
    $state=$unknown?'INFORMACION_INSUFICIENTE':($missing?'NO_COINCIDE':'COINCIDE');
    return ['state'=>$state,'satisfied'=>array_values(array_unique($satisfied)),'missing'=>array_values(array_unique($missing)),'unknown'=>array_values(array_unique($unknown)),'used'=>$used,'confidence'=>$state==='COINCIDE'?$confidence:null];
}

function clasificacionCamposVigentes(mysqli $db,int $evidence): array
{
    $st=$db->prepare("SELECT t.id_trabajo,r.metadatos_json FROM trabajos_documentales t JOIN origenes_trabajo_documental o ON o.id_trabajo=t.id_trabajo JOIN resultados_trabajo_documental r ON r.id_trabajo=t.id_trabajo WHERE o.id_evidencia=? AND t.estado IN ('PROCESADO','REQUIERE_REVISION') ORDER BY t.procesado_en DESC,t.id_trabajo DESC LIMIT 1");$st->bind_param('i',$evidence);$st->execute();$row=$st->get_result()->fetch_assoc();$st->close();
    if(!$row)return ['job'=>0,'fields'=>[],'source'=>'no_encontrado'];$meta=json_decode((string)$row['metadatos_json'],true);$extraction=$meta['extraccion']??[];$fields=is_array($extraction['campos']??null)?$extraction['campos']:[];$source='extraccion';
    $st=$db->prepare('SELECT datos_json FROM revisiones_metadatos_documentales WHERE id_evidencia=? AND id_trabajo=? ORDER BY numero_revision DESC LIMIT 1');$job=(int)$row['id_trabajo'];$st->bind_param('ii',$evidence,$job);$st->execute();$revision=$st->get_result()->fetch_assoc();$st->close();
    if($revision){$snap=json_decode((string)$revision['datos_json'],true);if(is_array($snap['campos']??null)){$fields=$snap['campos'];$source='revision_humana';}}
    return ['job'=>$job,'fields'=>$fields,'source'=>$source];
}

function clasificacionEjecutar(mysqli $db,int $evidence): array
{
    if(!clasificacionTieneInfraestructura($db))return ['disponible'=>false,'aplicaciones'=>[],'mensaje'=>'Faltan las migraciones de clasificación, modelo académico o metadatos.'];
    $st=$db->prepare('SELECT e.id_evidencia,e.id_docente,e.id_tipo_evidencia,e.titulo,e.ocultar,TRIM(CONCAT_WS(" ",u.nombre,u.apellidop,u.apellidom)) docente_nombre FROM evidencias e JOIN usuarios u ON u.id_usuario=e.id_docente AND u.activo=1 WHERE e.id_evidencia=?');$st->bind_param('i',$evidence);$st->execute();$doc=$st->get_result()->fetch_assoc();$st->close();
    if(!$doc||(int)$doc['ocultar']!==0)throw new RuntimeException('La evidencia no está disponible.');
    $metadata=clasificacionCamposVigentes($db,$evidence);
    if(!$metadata['job'])return ['disponible'=>true,'aplicaciones'=>[],'mensaje'=>'Se requiere una extracción documental antes de proponer aplicaciones.','sin_informacion'=>true];
    $sql="SELECT r.id_regla,r.id_version,r.id_criterio,r.id_subcriterio,r.id_actividad,r.regla_json,
      c.nombre criterio,c.descripcion criterio_descripcion,sc.nombre subcriterio,ac.nombre actividad,
      conv.id_convocatoria,conv.nombre convocatoria,conv.codigo convocatoria_codigo,conv.id_instrumento,i.abreviatura instrumento,i.nombre_completo,
      p.fecha_inicio periodo_inicio,p.fecha_fin periodo_fin,cv.vigencia_desde version_desde,cv.vigencia_hasta version_hasta,
      f.nombre_documento,f.fecha_publicacion,f.version_documento,f.referencia_url,f.vigencia_desde fuente_desde,f.vigencia_hasta fuente_hasta,
      c.regla_vigencia_json criterio_vigencia,sc.regla_vigencia_json subcriterio_vigencia
      FROM reglas_clasificacion_documental r
      JOIN criterios_academicos c ON c.id_criterio=r.id_criterio AND c.id_version=r.id_version AND c.activo=1
      LEFT JOIN subcriterios_academicos sc ON sc.id_subcriterio=r.id_subcriterio AND sc.id_version=r.id_version AND sc.id_criterio=r.id_criterio AND sc.activo=1
      JOIN actividades_academicas ac ON ac.id_actividad=r.id_actividad AND ac.id_version=r.id_version AND ac.activa=1
      JOIN criterio_actividad ca ON ca.id_criterio=c.id_criterio AND ca.id_actividad=ac.id_actividad AND ca.id_version=r.id_version
      JOIN criterio_fuente_normativa cf ON cf.id_criterio=c.id_criterio AND cf.id_fuente=r.id_fuente AND cf.id_version=r.id_version
      JOIN fuentes_normativas f ON f.id_fuente=r.id_fuente AND f.id_version=r.id_version AND f.validacion_estado='VALIDADA' AND f.id_responsable_validacion IS NOT NULL AND f.validada_en IS NOT NULL
      JOIN versiones_criterios cv ON cv.id_version=r.id_version AND cv.estado='PUBLICADA'
      JOIN convocatorias conv ON conv.id_convocatoria=cv.id_convocatoria AND conv.estado='ACTIVA'
      JOIN periodos_academicos p ON p.id_periodo=conv.id_periodo AND p.estado='ACTIVO'
      JOIN instrumentos i ON i.id_instrumento=conv.id_instrumento AND i.activo=1
      WHERE r.activa=1 AND (r.id_subcriterio IS NULL OR sc.id_subcriterio IS NOT NULL)
      AND (cv.vigencia_desde IS NULL OR cv.vigencia_desde<=CURRENT_DATE()) AND (cv.vigencia_hasta IS NULL OR cv.vigencia_hasta>=CURRENT_DATE())
      AND (conv.fecha_apertura IS NULL OR conv.fecha_apertura<=CURRENT_DATE()) AND (conv.fecha_cierre IS NULL OR conv.fecha_cierre>=CURRENT_DATE())
      AND (f.vigencia_desde IS NULL OR f.vigencia_desde<=COALESCE(p.fecha_fin,CURRENT_DATE()))
      AND (f.vigencia_hasta IS NULL OR f.vigencia_hasta>=COALESCE(p.fecha_inicio,CURRENT_DATE()))
      ORDER BY i.id_instrumento,conv.id_convocatoria,r.id_criterio,r.id_subcriterio,r.id_actividad,r.id_regla";
    $rs=$db->query($sql);$rules=$rs->fetch_all(MYSQLI_ASSOC);$rs->free();$groups=[];
    foreach($rules as $row){
        $key=(int)$row['id_convocatoria'].':'.(int)$row['id_version'];$groups[$key]['context']=['id_convocatoria'=>(int)$row['id_convocatoria'],'id_version'=>(int)$row['id_version'],'id_instrumento'=>(int)$row['id_instrumento'],'instrumento'=>$row['instrumento'],'instrumento_nombre'=>$row['nombre_completo'],'convocatoria'=>$row['convocatoria'],'convocatoria_codigo'=>$row['convocatoria_codigo'],'periodo_inicio'=>$row['periodo_inicio'],'periodo_fin'=>$row['periodo_fin'],'version_desde'=>$row['version_desde'],'version_hasta'=>$row['version_hasta']];
        $groups[$key]['rules'][]=$row;
    }
    $out=[];
    foreach($groups as $group){$ctx=$group['context'];$db->begin_transaction();try{
        $appStatus='PROPUESTA';$summary=json_encode(['documento_tipo'=>$doc['id_tipo_evidencia'],'fuente_campos'=>$metadata['source'],'trabajo'=>$metadata['job']],JSON_UNESCAPED_UNICODE);
        $st=$db->prepare("INSERT INTO evidencia_aplicaciones_academicas (id_evidencia,id_instrumento,id_convocatoria,id_version,estado,resumen_json) VALUES (?,?,?,?,'PROPUESTA',?) ON DUPLICATE KEY UPDATE id_aplicacion=LAST_INSERT_ID(id_aplicacion)");$st->bind_param('iiiis',$evidence,$ctx['id_instrumento'],$ctx['id_convocatoria'],$ctx['id_version'],$summary);$st->execute();$application=(int)($db->insert_id?:$st->insert_id);$st->close();
        $applicationState=(string)$db->query('SELECT estado FROM evidencia_aplicaciones_academicas WHERE id_aplicacion='.(int)$application)->fetch_row()[0];$docsComplete=true;$insufficient=false;$hasMatch=false;$appProposals=[];
        foreach($group['rules'] as $ruleRow){
            $rule=json_decode((string)$ruleRow['regla_json'],true);$configuredRule=is_array($rule)?$rule:[];$validity=[];
            foreach(['criterio_vigencia','subcriterio_vigencia'] as $validityColumn){if(!empty($ruleRow[$validityColumn])){$valid=json_decode((string)$ruleRow[$validityColumn],true);if(!is_array($valid)||($valid['version']??null)!==1||!is_array($valid['condiciones']??null))$configuredRule['condiciones'][]=['campo'=>'__configuracion_vigencia_invalida','operador'=>'desconocido'];else{$configuredRule['condiciones']=array_merge($configuredRule['condiciones']??[],$valid['condiciones']);$validity[]=$validityColumn;}}}
            $evaluation=clasificacionCompararRegla($configuredRule,$metadata['fields'],['id_tipo_evidencia'=>(int)$doc['id_tipo_evidencia'],'docente_nombre'=>$doc['docente_nombre'],'periodo_inicio'=>$ctx['periodo_inicio'],'periodo_fin'=>$ctx['periodo_fin']]);
            $required=$rule['requisitos']??[];$reqOk=[];$reqMissing=[];$reqUnknown=[];$reqNotApplicable=[];
            if(!is_array($required))$reqUnknown[]='Configuración de requisitos inválida';
            $criterion=(int)$ruleRow['id_criterio'];$subcriterion=$ruleRow['id_subcriterio']!==null?(int)$ruleRow['id_subcriterio']:0;$vReq=(int)$ruleRow['id_version'];
            $linkedReq=[];
            if($subcriterion>0){$q=$db->prepare('SELECT id_requisito FROM requisito_subcriterio WHERE id_subcriterio=? AND id_version=?');$q->bind_param('ii',$subcriterion,$vReq);$q->execute();foreach($q->get_result()->fetch_all(MYSQLI_ASSOC) as $link){$linkedReq[(int)$link['id_requisito']]=true;$required[]=['id_requisito'=>(int)$link['id_requisito']];}$q->close();}
            else{$q=$db->prepare('SELECT id_requisito FROM requisito_criterio WHERE id_criterio=? AND id_version=?');$q->bind_param('ii',$criterion,$vReq);$q->execute();foreach($q->get_result()->fetch_all(MYSQLI_ASSOC) as $link){$linkedReq[(int)$link['id_requisito']]=true;$required[]=['id_requisito'=>(int)$link['id_requisito']];}$q->close();}
            $seenReq=[];
            foreach(is_array($required)?$required:[] as $req){if(!is_array($req)||!isset($req['id_requisito'])){$reqUnknown[]='Requisito sin referencia configurada';continue;}
                $idReq=(int)$req['id_requisito'];if(!isset($linkedReq[$idReq])){$reqUnknown[]='Un requisito configurado no está vinculado al criterio/subcriterio de esta regla.';continue;}if(isset($seenReq[$idReq]))continue;$seenReq[$idReq]=true;$q=$db->prepare('SELECT nombre,obligatorio,vigencia_desde,vigencia_hasta,regla_vigencia_json FROM requisitos_documentales WHERE id_requisito=? AND id_version=? AND activo=1');$q->bind_param('ii',$idReq,$ctx['id_version']);$q->execute();$requirement=$q->get_result()->fetch_assoc();$q->close();
                if(!$requirement||$requirement['obligatorio']===null){$reqUnknown[]=(string)($requirement['nombre']??'Requisito').' (regla no validada)';continue;}
                if((int)$requirement['obligatorio']===0)continue;
                if($requirement['vigencia_desde']||$requirement['vigencia_hasta']){if(!$ctx['periodo_inicio']||!$ctx['periodo_fin']){$reqUnknown[]=$requirement['nombre'].' (periodo insuficiente para verificar vigencia)';continue;}$rStart=$requirement['vigencia_desde']??'0000-01-01';$rEnd=$requirement['vigencia_hasta']??'9999-12-31';if($rEnd<$ctx['periodo_inicio']||$rStart>$ctx['periodo_fin']){$reqNotApplicable[]=(string)$requirement['nombre'];continue;}}
                if($requirement['regla_vigencia_json']){$reqRule=json_decode((string)$requirement['regla_vigencia_json'],true);if(!is_array($reqRule)||($reqRule['version']??null)!==1||!is_array($reqRule['condiciones']??null)){$reqUnknown[]=$requirement['nombre'].' (regla de vigencia inválida)';continue;}$reqEval=clasificacionCompararRegla($reqRule,$metadata['fields'],['id_tipo_evidencia'=>(int)$doc['id_tipo_evidencia'],'docente_nombre'=>$doc['docente_nombre'],'periodo_inicio'=>$ctx['periodo_inicio'],'periodo_fin'=>$ctx['periodo_fin']]);if($reqEval['state']==='INFORMACION_INSUFICIENTE'){$reqUnknown[]=$requirement['nombre'].' (vigencia por validar)';continue;}if($reqEval['state']==='NO_COINCIDE'){$reqNotApplicable[]=(string)$requirement['nombre'];continue;}}
                $q=$db->prepare('SELECT 1 FROM requisito_tipo_documental WHERE id_requisito=? AND id_tipo_evidencia=?');$type=(int)$doc['id_tipo_evidencia'];$q->bind_param('ii',$idReq,$type);$q->execute();$covered=(bool)$q->get_result()->fetch_row();$q->close();
                if($covered)$reqOk[]=(string)$requirement['nombre'];else $reqMissing[]=(string)$requirement['nombre'];
            }
            if($reqUnknown&&$evaluation['state']!=='NO_COINCIDE')$evaluation['state']='INFORMACION_INSUFICIENTE';
            if($evaluation['state']==='NO_COINCIDE')continue;
            $state=$evaluation['state']==='INFORMACION_INSUFICIENTE'?'INFORMACION_INSUFICIENTE':'PROPUESTA';$justification=$evaluation['state']==='COINCIDE'?'Coincidencia con reglas configuradas y fuente normativa validada.':'Revisión preliminar; condiciones no confirmadas o requisitos documentales pendientes.';
            $used=json_encode(['metadatos'=>$evaluation['used'],'fuente_campos'=>$metadata['source'],'vigencias_aplicadas'=>array_merge($validity,$reqNotApplicable?['requisitos_documentales']:[]),'norma'=>['documento'=>$ruleRow['nombre_documento'],'version'=>$ruleRow['version_documento'],'publicacion'=>$ruleRow['fecha_publicacion'],'vigencia_desde'=>$ruleRow['fuente_desde'],'vigencia_hasta'=>$ruleRow['fuente_hasta'],'referencia'=>$ruleRow['referencia_url']]],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
            $okJson=json_encode($reqOk,JSON_UNESCAPED_UNICODE);$missingJson=json_encode(array_merge($reqMissing,$reqUnknown,$evaluation['unknown']),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);$notApplicableJson=json_encode($reqNotApplicable,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);$confidence=$evaluation['confidence'];$criterion=(int)$ruleRow['id_criterio'];$sub=$ruleRow['id_subcriterio']!==null?(int)$ruleRow['id_subcriterio']:null;$ruleId=(int)$ruleRow['id_regla'];$version=(int)$ruleRow['id_version'];$app=(int)$application;
            $ruleState=$evaluation['state'];$activity=(int)$ruleRow['id_actividad'];$st=$db->prepare("INSERT INTO propuestas_clasificacion_documental (id_aplicacion,id_version,id_criterio,id_subcriterio,id_actividad,id_regla,estado,coincidencia_regla,justificacion,datos_utilizados_json,requisitos_cumplidos_json,requisitos_faltantes_json,requisitos_no_aplicables_json,confianza) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE estado=IF(estado IN ('PROPUESTA','INFORMACION_INSUFICIENTE'),VALUES(estado),estado),coincidencia_regla=IF(estado IN ('PROPUESTA','INFORMACION_INSUFICIENTE'),VALUES(coincidencia_regla),coincidencia_regla),justificacion=IF(estado IN ('PROPUESTA','INFORMACION_INSUFICIENTE'),VALUES(justificacion),justificacion),datos_utilizados_json=IF(estado IN ('PROPUESTA','INFORMACION_INSUFICIENTE'),VALUES(datos_utilizados_json),datos_utilizados_json),requisitos_cumplidos_json=IF(estado IN ('PROPUESTA','INFORMACION_INSUFICIENTE'),VALUES(requisitos_cumplidos_json),requisitos_cumplidos_json),requisitos_faltantes_json=IF(estado IN ('PROPUESTA','INFORMACION_INSUFICIENTE'),VALUES(requisitos_faltantes_json),requisitos_faltantes_json),requisitos_no_aplicables_json=IF(estado IN ('PROPUESTA','INFORMACION_INSUFICIENTE'),VALUES(requisitos_no_aplicables_json),requisitos_no_aplicables_json),confianza=IF(estado IN ('PROPUESTA','INFORMACION_INSUFICIENTE'),VALUES(confianza),confianza)");$st->bind_param('iiiiiisssssssd',$app,$version,$criterion,$sub,$activity,$ruleId,$state,$ruleState,$justification,$used,$okJson,$missingJson,$notApplicableJson,$confidence);$st->execute();$proposal=(int)($db->insert_id?:$st->insert_id);$st->close();
            $q=$db->prepare('SELECT id_propuesta,estado,decidido_por,decidido_en FROM propuestas_clasificacion_documental WHERE id_aplicacion=? AND id_criterio=? AND id_subcriterio_unico=IFNULL(?,0) AND id_actividad=?');$q->bind_param('iiii',$app,$criterion,$sub,$activity);$q->execute();$saved=$q->get_result()->fetch_assoc();$q->close();$proposal=(int)$saved['id_propuesta'];
            if($evaluation['state']==='COINCIDE')$hasMatch=true;if($evaluation['state']==='INFORMACION_INSUFICIENTE'||$reqUnknown)$insufficient=true;if($reqMissing)$docsComplete=false;
            $appProposals[]=['id_propuesta'=>$proposal,'criterio'=>$ruleRow['criterio'],'subcriterio'=>$ruleRow['subcriterio'],'actividad'=>$ruleRow['actividad'],'estado'=>$saved['estado'],'regla'=>$evaluation['state'],'justificacion'=>$justification,'utilizados'=>json_decode($used,true),'requisitos_cumplidos'=>$reqOk,'requisitos_faltantes'=>array_merge($reqMissing,$reqUnknown,$evaluation['unknown']),'requisitos_no_aplicables'=>$reqNotApplicable,'confianza'=>$confidence,'decidido_en'=>$saved['decidido_en']];
        }
        if(!$appProposals){$del=$db->prepare("DELETE FROM evidencia_aplicaciones_academicas WHERE id_aplicacion=? AND estado='PROPUESTA'");$del->bind_param('i',$application);$del->execute();$del->close();$db->commit();continue;}
        $docState=$insufficient?'SIN_INFORMACION':($docsComplete&&$hasMatch?'PRELIMINAR':'INCOMPLETA');$update=$db->prepare('UPDATE evidencia_aplicaciones_academicas SET validacion_documental=?,coincidencia_semantica=\'NO_CONFIGURADA\' WHERE id_aplicacion=?');$update->bind_param('si',$docState,$application);$update->execute();$update->close();$db->commit();
        $out[]=$ctx+['id_aplicacion'=>$application,'estado'=>$applicationState,'validacion_documental'=>$docState,'coincidencia_semantica'=>'NO_CONFIGURADA','dictamen_oficial'=>'NO_EMITIDO','propuestas'=>$appProposals];
    }catch(Throwable $e){$db->rollback();throw $e;}}
    return ['disponible'=>true,'aplicaciones'=>$out,'mensaje'=>$out?'Las propuestas provienen solo de reglas asociadas a fuentes validadas.':'No hay reglas activas con fuentes validadas para una convocatoria vigente.','sin_informacion'=>!$out];
}

function clasificacionListar(mysqli $db,int $evidence,int $evaluadorId=0): array
{
    if(!clasificacionTieneInfraestructura($db))return [];
    $scope=$evaluadorId>0?' AND i.activo=1 AND EXISTS(SELECT 1 FROM evaluador_instrumento ei WHERE ei.id_evaluador=? AND ei.id_instrumento=a.id_instrumento)':'';
    $st=$db->prepare('SELECT a.id_aplicacion,a.id_instrumento,a.id_convocatoria,a.id_version,a.estado,a.validacion_documental,a.coincidencia_semantica,a.dictamen_oficial,cv.codigo_version,cv.nombre version_nombre,conv.codigo convocatoria_codigo,conv.nombre convocatoria,i.abreviatura instrumento,i.nombre_completo instrumento_nombre FROM evidencia_aplicaciones_academicas a JOIN versiones_criterios cv ON cv.id_version=a.id_version JOIN convocatorias conv ON conv.id_convocatoria=a.id_convocatoria JOIN instrumentos i ON i.id_instrumento=a.id_instrumento WHERE a.id_evidencia=? AND EXISTS(SELECT 1 FROM propuestas_clasificacion_documental p WHERE p.id_aplicacion=a.id_aplicacion)'.$scope.' ORDER BY i.id_instrumento,conv.id_convocatoria,a.id_version DESC');
    if($evaluadorId>0)$st->bind_param('ii',$evidence,$evaluadorId);else $st->bind_param('i',$evidence);$st->execute();$apps=$st->get_result()->fetch_all(MYSQLI_ASSOC);$st->close();
    foreach($apps as &$app){$id=(int)$app['id_aplicacion'];$q=$db->prepare('SELECT p.id_propuesta,p.id_criterio,p.id_subcriterio,p.estado,p.coincidencia_regla,p.justificacion,p.datos_utilizados_json,p.requisitos_cumplidos_json,p.requisitos_faltantes_json,p.requisitos_no_aplicables_json,p.confianza,p.decidido_en,c.nombre criterio,sc.nombre subcriterio,ac.nombre actividad FROM propuestas_clasificacion_documental p JOIN criterios_academicos c ON c.id_criterio=p.id_criterio LEFT JOIN subcriterios_academicos sc ON sc.id_subcriterio=p.id_subcriterio JOIN actividades_academicas ac ON ac.id_actividad=p.id_actividad AND ac.id_version=p.id_version WHERE p.id_aplicacion=? ORDER BY c.orden,p.id_propuesta');$q->bind_param('i',$id);$q->execute();$proposals=$q->get_result()->fetch_all(MYSQLI_ASSOC);$q->close();
        foreach($proposals as &$p){$p['actividades']=[$p['actividad']];$p['regla']=$p['coincidencia_regla'];foreach(['datos_utilizados_json'=>'datos_utilizados','requisitos_cumplidos_json'=>'requisitos_cumplidos','requisitos_faltantes_json'=>'requisitos_faltantes','requisitos_no_aplicables_json'=>'requisitos_no_aplicables'] as $column=>$out)$p[$out]=json_decode((string)$p[$column],true)??[];unset($p['datos_utilizados_json'],$p['requisitos_cumplidos_json'],$p['requisitos_faltantes_json'],$p['requisitos_no_aplicables_json']);}unset($p);$app['propuestas']=$proposals;
    }unset($app);return $apps;
}

function clasificacionDecidir(mysqli $db,int $proposal,int $userId,string $userName,string $decision,string $reason=''): void
{
    if(!in_array($decision,['CONFIRMADA','RECHAZADA'],true))throw new InvalidArgumentException('Decisión no válida.');
    $db->begin_transaction();try{
        $st=$db->prepare("SELECT p.id_propuesta,p.id_aplicacion,p.estado,a.id_evidencia,a.estado estado_aplicacion,e.id_docente,e.ocultar FROM propuestas_clasificacion_documental p JOIN evidencia_aplicaciones_academicas a ON a.id_aplicacion=p.id_aplicacion JOIN evidencias e ON e.id_evidencia=a.id_evidencia WHERE p.id_propuesta=? FOR UPDATE");$st->bind_param('i',$proposal);$st->execute();$row=$st->get_result()->fetch_assoc();$st->close();if(!$row||(int)$row['ocultar']!==0)throw new RuntimeException('Propuesta no disponible.');
        if(!in_array($row['estado'],['PROPUESTA','INFORMACION_INSUFICIENTE','CONFIRMADA','RECHAZADA'],true))throw new RuntimeException('Estado no válido.');
        $previous=(string)$row['estado'];$st=$db->prepare('UPDATE propuestas_clasificacion_documental SET estado=?,decidido_por=?,decidido_en=CURRENT_TIMESTAMP WHERE id_propuesta=?');$st->bind_param('sii',$decision,$userId,$proposal);$st->execute();$st->close();
        $event='PROPUESTA_'.$decision;$app=(int)$row['id_aplicacion'];$actor=mb_substr($userName,0,255);$why=mb_substr(trim($reason),0,1000);$st=$db->prepare('INSERT INTO historial_clasificacion_documental (id_aplicacion,id_propuesta,estado_anterior,estado_nuevo,actor_id,actor_nombre,motivo) VALUES (?,?,?,?,?,?,?)');$st->bind_param('iississ',$app,$proposal,$previous,$event,$userId,$actor,$why);$st->execute();$st->close();$db->commit();
    }catch(Throwable $e){$db->rollback();throw $e;}
}
