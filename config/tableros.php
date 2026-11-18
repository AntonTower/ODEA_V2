<?php
require_once __DIR__.'/seguridad.php';

function tableroH($value): string { return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8'); }

function tableroKpi(string $label,$value,string $hint=''): string
{
    $display=$value===null?'No disponible':number_format((int)$value);
    return '<article class="card dashboard-kpi"><div class="card-content"><span class="dashboard-kpi-label">'.tableroH($label).'</span><strong class="dashboard-kpi-value">'.tableroH($display).'</strong>'.
      ($hint!==''?'<span class="dashboard-kpi-hint">'.tableroH($hint).'</span>':'').'</div></article>';
}

function tableroEsquema(mysqli $conn): array
{
    static $tables=null;
    if ($tables!==null) return $tables;
    $tables=[]; $rs=$conn->query('SELECT table_name FROM information_schema.tables WHERE table_schema=DATABASE()');
    while($row=$rs->fetch_assoc()) $tables[strtolower($row['table_name'])]=true;
    $rs->free(); return $tables;
}

function tableroTieneTablas(mysqli $conn,array $required): bool
{
    $tables=tableroEsquema($conn);
    foreach($required as $name) if(empty($tables[strtolower($name)])) return false;
    return true;
}

function tableroObtenerDocente(mysqli $conn,int $docenteId): ?array
{
    $st=$conn->prepare('SELECT id_usuario,nombre,apellidop,apellidom,correo,activo FROM usuarios WHERE id_usuario=? AND rol=4 AND activo=1');
    $st->bind_param('i',$docenteId); $st->execute(); $row=$st->get_result()->fetch_assoc(); $st->close();
    if($row) $row['nombre_completo']=trim($row['nombre'].' '.$row['apellidop'].' '.$row['apellidom']);
    return $row?:null;
}

function tableroExpedienteDocenteId(mysqli $conn,array $usuario,int $solicitado): ?int
{
    $uid=(int)($usuario['id_usuario']??0); $role=(int)($usuario['rol']??0);
    if($uid<=0)return null;
    if($role===4)return $uid;
    if(!in_array($role,[1,2],true)||$solicitado<=0)return null;
    return tableroObtenerDocente($conn,$solicitado)?$solicitado:null;
}

function tableroDatosDocente(mysqli $conn,int $docenteId): array
{
    $st=$conn->prepare('SELECT COUNT(*) total FROM evidencias WHERE id_docente=? AND ocultar=0');
    $st->bind_param('i',$docenteId); $st->execute(); $total=(int)$st->get_result()->fetch_assoc()['total']; $st->close();
    $data=['total_evidencias'=>$total,'documentos_procesados'=>null,'documentos_pendientes_revision'=>null,
      'evidencias_clasificadas'=>null,'observadas'=>null,'aceptadas'=>null,'instrumentos'=>[],
      'requisitos_configurados'=>tableroTieneTablas($conn,['periodos_academicos','convocatorias','versiones_criterios','requisitos_documentales','requisito_tipo_documental'])];

    if(tableroTieneTablas($conn,['trabajos_documentales','origenes_trabajo_documental'])){
      $st=$conn->prepare("SELECT COUNT(DISTINCT CASE WHEN t.estado IN ('PROCESADO','REQUIERE_REVISION') THEN CONCAT(o.tipo_origen,':',o.id_origen) END) procesados,
          COUNT(DISTINCT CASE WHEN t.estado='REQUIERE_REVISION' THEN CONCAT(o.tipo_origen,':',o.id_origen) END) por_validar
          FROM origenes_trabajo_documental o JOIN trabajos_documentales t ON t.id_trabajo=o.id_trabajo
          JOIN evidencias e ON e.id_evidencia=o.id_evidencia WHERE e.id_docente=? AND e.ocultar=0");
      $st->bind_param('i',$docenteId); $st->execute(); $r=$st->get_result()->fetch_assoc(); $st->close();
      $data['documentos_procesados']=(int)$r['procesados']; $data['documentos_pendientes_revision']=(int)$r['por_validar'];
    }
    if(tableroTieneTablas($conn,['evidencia_criterio_versionado'])){
      $st=$conn->prepare('SELECT COUNT(DISTINCT ec.id_evidencia) total FROM evidencia_criterio_versionado ec JOIN evidencias e ON e.id_evidencia=ec.id_evidencia WHERE e.id_docente=? AND e.ocultar=0');
      $st->bind_param('i',$docenteId); $st->execute(); $data['evidencias_clasificadas']=(int)$st->get_result()->fetch_assoc()['total']; $st->close();
    }
    if(tableroTieneTablas($conn,['evaluaciones_versionadas','estados_evaluacion'])){
      $st=$conn->prepare("SELECT ee.codigo,COUNT(DISTINCT v.id_evidencia) total FROM evaluaciones_versionadas v
          JOIN evidencias e ON e.id_evidencia=v.id_evidencia JOIN estados_evaluacion ee ON ee.id_estado=v.id_estado
          WHERE e.id_docente=? AND e.ocultar=0 AND ee.codigo IN ('OBSERVADA','ACEPTADA') GROUP BY ee.codigo");
      $st->bind_param('i',$docenteId); $st->execute(); $rs=$st->get_result();
      while($r=$rs->fetch_assoc()) $data[$r['codigo']==='OBSERVADA'?'observadas':'aceptadas']=(int)$r['total']; $st->close();
      $data['observadas']=$data['observadas']??0; $data['aceptadas']=$data['aceptadas']??0;
    }

    $rs=$conn->query('SELECT id_instrumento,abreviatura,nombre_completo FROM instrumentos WHERE activo=1 ORDER BY id_instrumento');
    while($instrument=$rs->fetch_assoc()){
      $id=(int)$instrument['id_instrumento'];
      $st=$conn->prepare('SELECT COUNT(DISTINCT e.id_evidencia) total FROM instrumento_tipo_evidencia ite JOIN evidencias e ON e.id_tipo_evidencia=ite.id_tipo_evidencia AND e.ocultar=0 WHERE ite.id_instrumento=? AND e.id_docente=?');
      $st->bind_param('ii',$id,$docenteId); $st->execute(); $compatible=(int)$st->get_result()->fetch_assoc()['total']; $st->close();
      $statuses=['observadas'=>null,'aceptadas'=>null];
      if(tableroTieneTablas($conn,['evaluaciones_versionadas','estados_evaluacion'])){
        $st=$conn->prepare("SELECT ee.codigo,COUNT(DISTINCT v.id_evidencia) total FROM evaluaciones_versionadas v
          JOIN evidencias e ON e.id_evidencia=v.id_evidencia JOIN estados_evaluacion ee ON ee.id_estado=v.id_estado
          WHERE v.id_instrumento=? AND e.id_docente=? AND e.ocultar=0 AND ee.codigo IN ('OBSERVADA','ACEPTADA') GROUP BY ee.codigo");
        $st->bind_param('ii',$id,$docenteId); $st->execute(); $rr=$st->get_result();
        while($x=$rr->fetch_assoc()) $statuses[$x['codigo']==='OBSERVADA'?'observadas':'aceptadas']=(int)$x['total']; $st->close();
        $statuses['observadas']=$statuses['observadas']??0; $statuses['aceptadas']=$statuses['aceptadas']??0;
      }
      $requirements=$data['requisitos_configurados']?tableroRequisitosDocenteInstrumento($conn,$docenteId,$id):null;
      $data['instrumentos'][]=$instrument+['compatibles'=>$compatible]+$statuses+['requisitos'=>$requirements];
    }
    $rs->free();
    return $data;
}

function tableroRequisitosDocenteInstrumento(mysqli $conn,int $docenteId,int $instrumentoId): array
{
    $st=$conn->prepare("SELECT r.id_requisito,r.obligatorio,
        (SELECT COUNT(*) FROM requisito_tipo_documental rt WHERE rt.id_requisito=r.id_requisito) tipos,
        (SELECT COUNT(DISTINCT e.id_tipo_evidencia) FROM requisito_tipo_documental rt JOIN evidencias e ON e.id_tipo_evidencia=rt.id_tipo_evidencia
          WHERE rt.id_requisito=r.id_requisito AND e.id_docente=? AND e.ocultar=0) documentado
      FROM requisitos_documentales r JOIN versiones_criterios v ON v.id_version=r.id_version
      JOIN convocatorias c ON c.id_convocatoria=v.id_convocatoria JOIN periodos_academicos p ON p.id_periodo=c.id_periodo
      WHERE c.id_instrumento=? AND c.estado='ACTIVA' AND p.estado='ACTIVO' AND v.estado='PUBLICADA' AND r.activo=1");
    $st->bind_param('ii',$docenteId,$instrumentoId); $st->execute(); $rs=$st->get_result();
    $out=['documentados'=>0,'pendientes'=>0,'por_validar'=>0,'total'=>0,'no_aplicable'=>null];
    while($r=$rs->fetch_assoc()){
      $out['total']++; $types=(int)$r['tipos']; $docs=(int)$r['documentado'];
      if($r['obligatorio']===null || $types===0) { $out['por_validar']++; continue; }
      if((int)$r['obligatorio']===0) continue;
      if($docs>0) $out['documentados']++; else $out['pendientes']++;
    }
    $st->close(); return $out;
}

function tableroDatosAdministrador(mysqli $conn): array
{
    $data=[];
    $data['docentes']=(int)$conn->query('SELECT COUNT(*) FROM usuarios WHERE rol=4 AND activo=1')->fetch_row()[0];
    $data['evidencias']=(int)$conn->query('SELECT COUNT(*) FROM evidencias WHERE ocultar=0')->fetch_row()[0];
    $data['procesamiento_disponible']=tableroTieneTablas($conn,['trabajos_documentales']);
    $data['procesamiento']=[];
    if($data['procesamiento_disponible']){
      $rs=$conn->query('SELECT estado,COUNT(*) total FROM trabajos_documentales GROUP BY estado');
      while($r=$rs->fetch_assoc()) $data['procesamiento'][$r['estado']]=(int)$r['total']; $rs->free();
    }
    $data['convocatorias_disponibles']=tableroTieneTablas($conn,['convocatorias','periodos_academicos']);
    $data['convocatorias_activas']=$data['convocatorias_disponibles']?(int)$conn->query("SELECT COUNT(*) FROM convocatorias c JOIN periodos_academicos p ON p.id_periodo=c.id_periodo WHERE c.estado='ACTIVA' AND p.estado='ACTIVO'")->fetch_row()[0]:null;
    $data['actividad_disponible']=tableroTieneTablas($conn,['historial_evaluaciones']); $data['actividad_evaluadores']=[];
    if($data['actividad_disponible']){
      $rs=$conn->query("SELECT h.actor_nombre,COUNT(*) eventos,MAX(h.creado_en) ultimo FROM historial_evaluaciones h
        JOIN usuarios u ON u.id_usuario=h.id_usuario_actor AND u.rol=3 GROUP BY h.id_usuario_actor,h.actor_nombre ORDER BY ultimo DESC LIMIT 8");
      while($r=$rs->fetch_assoc()) $data['actividad_evaluadores'][]=$r; $rs->free();
    }
    $data['estadistica_documental']=[];
    $rs=$conn->query('SELECT t.id_tipo_evidencia,t.nombre_tipo,COUNT(DISTINCT e.id_evidencia) evidencias FROM tipos_de_evidencia t LEFT JOIN evidencias e ON e.id_tipo_evidencia=t.id_tipo_evidencia AND e.ocultar=0 GROUP BY t.id_tipo_evidencia,t.nombre_tipo ORDER BY t.nombre_tipo LIMIT 10');
    while($r=$rs->fetch_assoc()) $data['estadistica_documental'][]=$r; $rs->free();
    return $data;
}

function tableroDatosEvaluador(mysqli $conn,int $evaluadorId): array
{
    $st=$conn->prepare('SELECT i.id_instrumento,i.abreviatura,i.nombre_completo FROM evaluador_instrumento ei JOIN instrumentos i ON i.id_instrumento=ei.id_instrumento AND i.activo=1 WHERE ei.id_evaluador=? ORDER BY i.id_instrumento');
    $st->bind_param('i',$evaluadorId); $st->execute(); $ins=$st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
    $total=0; $observed=0; $reviewed=0;
    foreach($ins as &$i){
      $id=(int)$i['id_instrumento'];
      $st=$conn->prepare('SELECT COUNT(DISTINCT e.id_evidencia) total FROM instrumento_tipo_evidencia ite JOIN evidencias e ON e.id_tipo_evidencia=ite.id_tipo_evidencia AND e.ocultar=0 WHERE ite.id_instrumento=?');
      $st->bind_param('i',$id); $st->execute(); $i['evidencias_asignadas']=(int)$st->get_result()->fetch_assoc()['total']; $st->close(); $total+=$i['evidencias_asignadas'];
      $i['revisadas']=0; $i['observadas']=0;
      if(tableroTieneTablas($conn,['evaluaciones_versionadas','estados_evaluacion'])){
        $st=$conn->prepare("SELECT COUNT(DISTINCT CASE WHEN ee.codigo IN ('OBSERVADA','ACEPTADA','RECHAZADA') THEN v.id_evidencia END) revisadas,
          COUNT(DISTINCT CASE WHEN ee.codigo='OBSERVADA' THEN v.id_evidencia END) observadas
          FROM evaluaciones_versionadas v JOIN estados_evaluacion ee ON ee.id_estado=v.id_estado
          JOIN evidencias e ON e.id_evidencia=v.id_evidencia AND e.ocultar=0 WHERE v.id_instrumento=?");
        $st->bind_param('i',$id); $st->execute(); $r=$st->get_result()->fetch_assoc(); $st->close();
        $i['revisadas']=(int)$r['revisadas']; $i['observadas']=(int)$r['observadas'];
      } elseif(tableroTieneTablas($conn,['calificacion_evidencia'])) {
        $st=$conn->prepare('SELECT COUNT(DISTINCT ce.id_evidencia) revisadas FROM calificacion_evidencia ce JOIN evidencias e ON e.id_evidencia=ce.id_evidencia AND e.ocultar=0 WHERE ce.id_instrumento=?');
        $st->bind_param('i',$id); $st->execute(); $i['revisadas']=(int)$st->get_result()->fetch_assoc()['revisadas']; $st->close();
      }
      $i['pendientes']=max(0,$i['evidencias_asignadas']-$i['revisadas']); $reviewed+=$i['revisadas']; $observed+=$i['observadas'];
    }
    unset($i);
    return ['instrumentos'=>$ins,'total_asignadas'=>$total,'revisadas'=>$reviewed,'pendientes'=>max(0,$total-$reviewed),'observadas'=>$observed,
      'historial_disponible'=>tableroTieneTablas($conn,['historial_evaluaciones','evaluaciones_versionadas'])];
}
