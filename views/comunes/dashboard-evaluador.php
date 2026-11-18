<?php
$evaluadorId=(int)$usuarioActual['id_usuario'];
$stats=tableroDatosEvaluador($conn,$evaluadorId);
$q=trim((string)($_GET['q']??'')); $instrumentoFiltro=(int)($_GET['instrumento']??0); $estadoFiltro=(string)($_GET['estado']??'todas');
$versioned=tableroTieneTablas($conn,['evaluaciones_versionadas','estados_evaluacion']);
$joinVersioned=$versioned?"LEFT JOIN evaluaciones_versionadas ev ON ev.id_evaluacion=(SELECT ev2.id_evaluacion FROM evaluaciones_versionadas ev2 WHERE ev2.id_evidencia=e.id_evidencia AND ev2.id_instrumento=i.id_instrumento ORDER BY ev2.actualizada_en DESC,ev2.id_evaluacion DESC LIMIT 1) LEFT JOIN estados_evaluacion ee ON ee.id_estado=ev.id_estado":"LEFT JOIN (SELECT NULL id_estado,NULL codigo,NULL nombre) ee ON 1=0";
$stateExpr=$versioned?"CASE WHEN ee.codigo IS NOT NULL THEN ee.codigo WHEN ce.id_calificacion IS NOT NULL THEN 'REVISADA' ELSE 'PENDIENTE' END":"CASE WHEN ce.id_calificacion IS NOT NULL THEN 'REVISADA' ELSE 'PENDIENTE' END";
$where=" WHERE e.ocultar=0 AND EXISTS (SELECT 1 FROM evaluador_instrumento ei WHERE ei.id_evaluador=? AND ei.id_instrumento=i.id_instrumento)";
$types='i'; $params=[$evaluadorId];
if($instrumentoFiltro>0){$where.=' AND i.id_instrumento=?';$types.='i';$params[]=$instrumentoFiltro;}
if($q!==''){$where.=' AND (e.titulo LIKE ? OR i.abreviatura LIKE ? OR t.nombre_tipo LIKE ?)';$types.='sss';$needle='%'.$q.'%';$params[]=$needle;$params[]=$needle;$params[]=$needle;}
$allowedStates=['pendiente','observada','aceptada','rechazada','revisada'];
if(in_array($estadoFiltro,$allowedStates,true)){
  if($estadoFiltro==='pendiente')$where.=" AND ($stateExpr IN ('PENDIENTE','PENDIENTE_REVISION','EN_REVISION','REENVIADA_REVISION'))";
  elseif($estadoFiltro==='observada')$where.=" AND ($stateExpr='OBSERVADA')";
  elseif($estadoFiltro==='aceptada')$where.=" AND ($stateExpr='ACEPTADA')";
  elseif($estadoFiltro==='rechazada')$where.=" AND ($stateExpr='RECHAZADA')";
  else $where.=" AND ($stateExpr IN ('REVISADA','OBSERVADA','ACEPTADA','RECHAZADA'))";
}
$sql="SELECT e.id_evidencia,e.titulo,e.fecha_subida,t.nombre_tipo,i.id_instrumento,i.abreviatura,
      CONCAT_WS(' ',u.nombre,u.apellidop,u.apellidom) docente,$stateExpr estado_codigo,
      COALESCE(ee.nombre,IF(ce.id_calificacion IS NOT NULL,'Evaluada (legada)','Pendiente')) estado_nombre
    FROM evidencias e JOIN instrumento_tipo_evidencia ite ON ite.id_tipo_evidencia=e.id_tipo_evidencia
    JOIN instrumentos i ON i.id_instrumento=ite.id_instrumento AND i.activo=1
    JOIN usuarios u ON u.id_usuario=e.id_docente LEFT JOIN tipos_de_evidencia t ON t.id_tipo_evidencia=e.id_tipo_evidencia
    LEFT JOIN calificacion_evidencia ce ON ce.id_evidencia=e.id_evidencia AND ce.id_instrumento=i.id_instrumento
    $joinVersioned $where ORDER BY e.fecha_subida DESC,e.id_evidencia DESC LIMIT 100";
$st=$conn->prepare($sql);$st->bind_param($types,...$params);$st->execute();$work=$st->get_result()->fetch_all(MYSQLI_ASSOC);$st->close();
$history=[];
if($stats['historial_disponible']){
 $st=$conn->prepare("SELECT h.tipo_evento,h.creado_en,h.actor_nombre,ee.nombre estado,e.id_evidencia,e.titulo,i.abreviatura
   FROM historial_evaluaciones h JOIN evaluaciones_versionadas v ON v.id_evaluacion=h.id_evaluacion
   JOIN evidencias e ON e.id_evidencia=v.id_evidencia JOIN instrumentos i ON i.id_instrumento=v.id_instrumento
   LEFT JOIN estados_evaluacion ee ON ee.id_estado=h.id_estado_nuevo
   WHERE e.ocultar=0 AND EXISTS (SELECT 1 FROM evaluador_instrumento ei JOIN instrumento_tipo_evidencia ite ON ite.id_instrumento=ei.id_instrumento
      WHERE ei.id_evaluador=? AND ei.id_instrumento=v.id_instrumento AND ite.id_tipo_evidencia=e.id_tipo_evidencia)
   ORDER BY h.creado_en DESC,h.id_historial DESC LIMIT 8");
 $st->bind_param('i',$evaluadorId);$st->execute();$history=$st->get_result()->fetch_all(MYSQLI_ASSOC);$st->close();
}
?>
<section class="space-y-6">
  <header class="dashboard-hero"><div><p class="dashboard-eyebrow">Evaluación</p><h1 class="text-2xl font-semibold">Panel del evaluador</h1><p class="text-muted-foreground">Carga y actividad limitadas a tus instrumentos asignados.</p></div><a class="btn btn-primary" href="<?= BASE_URL ?>/views/evaluador/evaluacion.php">Abrir evaluación</a></header>
  <div class="dashboard-kpis">
    <?= tableroKpi('Revisiones pendientes',$stats['pendientes'],'Por evidencia e instrumento') ?>
    <?= tableroKpi('Revisiones realizadas',$stats['revisadas'],'Por evidencia e instrumento') ?>
    <?= tableroKpi('Revisiones observadas',$stats['observadas'],'Por evidencia e instrumento') ?>
    <?= tableroKpi('Instrumentos autorizados',count($stats['instrumentos'])) ?>
  </div>
  <section class="card"><div class="card-content"><h2 class="text-lg font-semibold">Mis asignaciones</h2>
    <?php if(!$stats['instrumentos']): ?><div class="dashboard-empty">No tienes instrumentos asignados actualmente.</div><?php else: ?><div class="dashboard-instrument-grid">
      <?php foreach($stats['instrumentos'] as $inst): ?><article class="card"><div class="card-content"><p class="dashboard-eyebrow"><?= tableroH($inst['abreviatura']) ?></p><h3 class="font-semibold"><?= tableroH($inst['nombre_completo']) ?></h3><div class="dashboard-status-row"><span>Pendientes</span><b><?= (int)$inst['pendientes'] ?></b></div><div class="dashboard-status-row"><span>Revisadas</span><b><?= (int)$inst['revisadas'] ?></b></div><div class="dashboard-status-row"><span>Observadas</span><b><?= (int)$inst['observadas'] ?></b></div></div></article><?php endforeach; ?>
    </div><?php endif; ?>
  </div></section>
  <section class="card"><div class="card-content"><div class="dashboard-section-heading"><div><h2 class="text-lg font-semibold">Bandeja de evidencias</h2><p class="text-muted-foreground">Busca dentro de las evidencias vinculadas a tus instrumentos.</p></div></div>
    <form class="dashboard-filters" method="get" action="<?= BASE_URL ?>/views/comunes/index.php">
      <label>Buscar<input class="input" name="q" value="<?= tableroH($q) ?>" placeholder="Título o tipo documental"></label>
      <label>Instrumento<select class="input" name="instrumento"><option value="0">Todos mis instrumentos</option><?php foreach($stats['instrumentos'] as $inst): ?><option value="<?= (int)$inst['id_instrumento'] ?>" <?= $instrumentoFiltro===(int)$inst['id_instrumento']?'selected':'' ?>><?= tableroH($inst['abreviatura']) ?></option><?php endforeach; ?></select></label>
      <label>Estado<select class="input" name="estado"><?php foreach(['todas'=>'Todos','pendiente'=>'Pendiente','revisada'=>'Revisada','observada'=>'Observada','aceptada'=>'Aceptada','rechazada'=>'Rechazada'] as $key=>$label): ?><option value="<?= tableroH($key) ?>" <?= $estadoFiltro===$key?'selected':'' ?>><?= tableroH($label) ?></option><?php endforeach; ?></select></label>
      <button class="btn btn-primary" type="submit">Filtrar</button><a class="btn" href="<?= BASE_URL ?>/views/comunes/index.php">Limpiar</a>
    </form>
    <?php if(!$work): ?><div class="dashboard-empty">No hay evidencias para esos filtros o aún no existen evidencias en tus instrumentos.</div><?php else: ?><div class="dashboard-table-wrap"><table class="table dashboard-table"><thead><tr><th>Evidencia</th><th>Tipo</th><th>Docente</th><th>Instrumento</th><th>Estado</th><th></th></tr></thead><tbody>
      <?php foreach($work as $item): ?><tr><td><?= tableroH($item['titulo']) ?></td><td><?= tableroH($item['nombre_tipo']??'—') ?></td><td><?= tableroH($item['docente']) ?></td><td><?= tableroH($item['abreviatura']) ?></td><td><span class="badge"><?= tableroH($item['estado_nombre']) ?></span></td><td><a class="btn" href="<?= BASE_URL ?>/views/evaluador/evaluacion.php">Abrir bandeja</a></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
  </div></section>
  <section class="card"><div class="card-content"><h2 class="text-lg font-semibold">Historial de revisiones</h2>
    <?php if(!$stats['historial_disponible']): ?><div class="dashboard-empty">El historial detallado aún no está instalado.</div><?php elseif(!$history): ?><div class="dashboard-empty">No hay revisiones registradas en tus asignaciones.</div><?php else: ?><div class="dashboard-table-wrap"><table class="table dashboard-table"><thead><tr><th>Fecha</th><th>Evidencia</th><th>Instrumento</th><th>Evento</th><th>Estado</th></tr></thead><tbody>
      <?php foreach($history as $entry): ?><tr><td><?= tableroH($entry['creado_en']) ?></td><td><?= tableroH($entry['titulo']) ?></td><td><?= tableroH($entry['abreviatura']) ?></td><td><?= tableroH($entry['tipo_evento']) ?></td><td><?= tableroH($entry['estado']??'—') ?></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
  </div></section>
</section>
