<?php
require_once __DIR__.'/../../config/paths.php';
require_once CONFIG_DIR.'/validacion.php';
require_once CONFIG_DIR.'/conexion.php';
require_once CONFIG_DIR.'/seguridad.php';
require_once CONFIG_DIR.'/tableros.php';
$usuarioActual=seguridadUsuarioActual($conn); $role=(int)$usuarioActual['rol']; $isAdmin=in_array($role,[1,2],true);
if(!$isAdmin && $role!==4){http_response_code(403);exit('No tienes permiso para consultar expedientes docentes.');}
$docenteId=tableroExpedienteDocenteId($conn,$usuarioActual,(int)($_GET['id_docente']??0))??0;
$docente=$docenteId>0?tableroObtenerDocente($conn,$docenteId):null;
$docentes=[];
if($isAdmin){$rs=$conn->query("SELECT id_usuario,nombre,apellidop,apellidom FROM usuarios WHERE rol=4 AND activo=1 ORDER BY apellidop,apellidom,nombre");while($r=$rs->fetch_assoc())$docentes[]=$r;$rs->free();}
$evidencias=[];$legacy=[];$history=[];
if($docente){
  $st=$conn->prepare("SELECT e.id_evidencia,e.titulo,e.archivo,e.fecha_subida,t.nombre_tipo,
      (SELECT GROUP_CONCAT(DISTINCT i.abreviatura ORDER BY i.id_instrumento SEPARATOR ', ')
         FROM calificacion_evidencia ce JOIN instrumentos i ON i.id_instrumento=ce.id_instrumento WHERE ce.id_evidencia=e.id_evidencia) instrumentos
    FROM evidencias e LEFT JOIN tipos_de_evidencia t ON t.id_tipo_evidencia=e.id_tipo_evidencia
    WHERE e.id_docente=? AND e.ocultar=0 ORDER BY e.fecha_subida DESC,e.id_evidencia DESC");
  $st->bind_param('i',$docenteId);$st->execute();$evidencias=$st->get_result()->fetch_all(MYSQLI_ASSOC);$st->close();
  $st=$conn->prepare("SELECT ce.id_evidencia,e.titulo,i.abreviatura,ce.resultado,ce.comentario,ce.calificado_en,ce.actualizado_en
    FROM calificacion_evidencia ce JOIN evidencias e ON e.id_evidencia=ce.id_evidencia JOIN instrumentos i ON i.id_instrumento=ce.id_instrumento
    WHERE e.id_docente=? AND e.ocultar=0 ORDER BY ce.actualizado_en DESC LIMIT 100");
  $st->bind_param('i',$docenteId);$st->execute();$legacy=$st->get_result()->fetch_all(MYSQLI_ASSOC);$st->close();
  if(tableroTieneTablas($conn,['historial_evaluaciones','evaluaciones_versionadas'])){
    $st=$conn->prepare("SELECT h.creado_en,h.tipo_evento,h.resultado_anterior,h.resultado_nuevo,h.comentario_anterior,h.comentario_nuevo,h.motivo,
      h.actor_nombre,e.id_evidencia,e.titulo,i.abreviatura,ea.nombre estado_anterior,en.nombre estado_nuevo
      FROM historial_evaluaciones h JOIN evaluaciones_versionadas v ON v.id_evaluacion=h.id_evaluacion
      JOIN evidencias e ON e.id_evidencia=v.id_evidencia JOIN instrumentos i ON i.id_instrumento=v.id_instrumento
      LEFT JOIN estados_evaluacion ea ON ea.id_estado=h.id_estado_anterior LEFT JOIN estados_evaluacion en ON en.id_estado=h.id_estado_nuevo
      WHERE e.id_docente=? AND e.ocultar=0 ORDER BY h.creado_en DESC,h.id_historial DESC LIMIT 100");
    $st->bind_param('i',$docenteId);$st->execute();$history=$st->get_result()->fetch_all(MYSQLI_ASSOC);$st->close();
  }
}
include COMPONENTS_DIR.'/layout/header.php';include COMPONENTS_DIR.'/layout/left-menu.php';
?>
<style>.expediente-anchor{scroll-margin-top:90px}.expediente-person{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;flex-wrap:wrap}.expediente-nav{display:flex;gap:8px;overflow:auto;padding:4px 0}.expediente-nav a{white-space:nowrap}</style>
<section class="space-y-6">
  <header class="dashboard-hero"><div><p class="dashboard-eyebrow">ODEA · Expediente</p><h1 class="text-2xl font-semibold">Expediente académico</h1><p class="text-muted-foreground">Vista unificada de información existente, sin duplicar registros.</p></div><?php if($role===4): ?><a class="btn btn-primary" href="<?= BASE_URL ?>/views/docente/gestion-evidencias.php">Gestionar evidencias</a><?php endif; ?></header>
  <?php if($isAdmin): ?><form class="dashboard-filters" method="get"><label>Docente<select class="input" name="id_docente" required><option value="">Selecciona un docente</option><?php foreach($docentes as $row): ?><option value="<?= (int)$row['id_usuario'] ?>" <?= $docenteId===(int)$row['id_usuario']?'selected':'' ?>><?= tableroH(trim($row['nombre'].' '.$row['apellidop'].' '.$row['apellidom'])) ?></option><?php endforeach; ?></select></label><button class="btn btn-primary" type="submit">Abrir expediente</button></form><?php endif; ?>
  <?php if(!$docente): ?><div class="card"><div class="card-content dashboard-empty"><?= $isAdmin?'Selecciona un docente activo para consultar su expediente.':'El expediente no está disponible.' ?></div></div><?php else: ?>
    <section class="card"><div class="card-content expediente-person"><div><p class="dashboard-eyebrow">Docente</p><h2 class="text-lg font-semibold"><?= tableroH($docente['nombre_completo']) ?></h2><p class="text-muted-foreground"><?= tableroH($docente['correo']) ?></p></div><div><span class="badge">Cuenta activa</span><div class="text-sm text-muted-foreground" style="margin-top:8px"><?= count($evidencias) ?> evidencias visibles</div></div></div></section>
    <nav class="expediente-nav" aria-label="Secciones del expediente"><a class="btn" href="#evidencias">Evidencias</a><a class="btn" href="#evaluaciones">Evaluaciones</a><a class="btn" href="#actividades">Actividades</a><a class="btn" href="#produccion">Producción científica</a><a class="btn" href="#tutorias">Tutorías y tesis</a><a class="btn" href="#participaciones">Participaciones</a><a class="btn" href="#reconocimientos">Reconocimientos</a></nav>
    <section id="evidencias" class="card expediente-anchor"><div class="card-content"><div class="dashboard-section-heading"><div><h2 class="text-lg font-semibold">Evidencias</h2><p class="text-muted-foreground">Registros existentes del docente.</p></div></div>
      <?php if(!$evidencias): ?><div class="dashboard-empty">No hay evidencias visibles registradas.</div><?php else: ?><div class="dashboard-table-wrap"><table class="table dashboard-table"><thead><tr><th>ID</th><th>Título</th><th>Tipo documental</th><th>Instrumentos evaluados</th><th>Fecha</th><th></th></tr></thead><tbody>
        <?php foreach($evidencias as $e): ?><tr><td><?= (int)$e['id_evidencia'] ?></td><td><?= tableroH($e['titulo']) ?></td><td><?= tableroH($e['nombre_tipo']??'—') ?></td><td><?= tableroH($e['instrumentos']??'—') ?></td><td><?= tableroH($e['fecha_subida']) ?></td><td><a class="btn" href="<?= BASE_URL ?>/views/docente/evidencia-detalle.php?id=<?= (int)$e['id_evidencia'] ?>">Ver detalle</a></td></tr><?php endforeach; ?>
      </tbody></table></div><?php endif; ?></div></section>
    <section id="evaluaciones" class="card expediente-anchor"><div class="card-content"><h2 class="text-lg font-semibold">Historial de evaluaciones</h2>
      <?php if(!tableroTieneTablas($conn,['historial_evaluaciones','evaluaciones_versionadas'])): ?><p class="dashboard-note">El historial detallado aún no está instalado. Se muestran las calificaciones vigentes existentes sin reconstruir eventos anteriores.</p><?php endif; ?>
      <?php if($history): ?><div class="dashboard-table-wrap"><table class="table dashboard-table"><thead><tr><th>Fecha</th><th>Evidencia</th><th>Instrumento</th><th>Evento</th><th>Estado</th><th>Comentario</th></tr></thead><tbody><?php foreach($history as $h): ?><tr><td><?= tableroH($h['creado_en']) ?></td><td><?= tableroH($h['titulo']) ?></td><td><?= tableroH($h['abreviatura']) ?></td><td><?= tableroH($h['tipo_evento']) ?></td><td><?= tableroH($h['estado_nuevo']??'—') ?></td><td><?= tableroH($h['comentario_nuevo']??$h['motivo']??'—') ?></td></tr><?php endforeach; ?></tbody></table></div>
      <?php elseif($legacy): ?><div class="dashboard-table-wrap"><table class="table dashboard-table"><thead><tr><th>Fecha</th><th>Evidencia</th><th>Instrumento</th><th>Resultado</th><th>Comentario</th></tr></thead><tbody><?php foreach($legacy as $r): ?><tr><td><?= tableroH($r['actualizado_en']??$r['calificado_en']) ?></td><td><?= tableroH($r['titulo']) ?></td><td><?= tableroH($r['abreviatura']) ?></td><td><?= tableroH($r['resultado']) ?></td><td><?= tableroH($r['comentario']??'—') ?></td></tr><?php endforeach; ?></tbody></table></div>
      <?php else: ?><div class="dashboard-empty">No hay evaluaciones registradas.</div><?php endif; ?></div></section>
    <?php foreach(['actividades'=>'Actividades académicas','produccion'=>'Producción científica','tutorias'=>'Tutorías y dirección de tesis','participaciones'=>'Participaciones','reconocimientos'=>'Reconocimientos'] as $anchor=>$title): ?>
      <section id="<?= tableroH($anchor) ?>" class="card expediente-anchor"><div class="card-content"><h2 class="text-lg font-semibold"><?= tableroH($title) ?></h2><div class="dashboard-empty">ODEA aún no tiene un módulo independiente para registrar este apartado. No se reclasifican atributos libres ni evidencias para completar esta sección.</div></div></section>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
<?php include COMPONENTS_DIR.'/layout/footer.php'; ?>
