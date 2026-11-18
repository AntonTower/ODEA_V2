<?php
$docenteId=(int)$usuarioActual['id_usuario'];
$docente=tableroObtenerDocente($conn,$docenteId);
$stats=tableroDatosDocente($conn,$docenteId);
$st=$conn->prepare('SELECT e.id_evidencia,e.titulo,e.fecha_subida,t.nombre_tipo FROM evidencias e LEFT JOIN tipos_de_evidencia t ON t.id_tipo_evidencia=e.id_tipo_evidencia WHERE e.id_docente=? AND e.ocultar=0 ORDER BY e.id_evidencia DESC LIMIT 6');
$st->bind_param('i',$docenteId); $st->execute(); $recent=$st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
?>
<style>
.dashboard-note{border-left:4px solid var(--primary);padding:12px 16px;background:var(--muted);border-radius:8px;color:var(--muted-foreground)}
.dashboard-instrument{scroll-margin-top:96px}
</style>
<section class="space-y-6">
  <header class="dashboard-hero">
    <div><p class="dashboard-eyebrow">Expediente académico</p><h1 class="text-2xl font-semibold">Hola, <?= tableroH($docente['nombre']??'Docente') ?></h1>
      <p class="text-muted-foreground">Resumen privado de tus evidencias y su estado documental.</p></div>
    <a class="btn btn-primary" href="<?= BASE_URL ?>/views/docente/expediente.php">Abrir expediente</a>
  </header>

  <div class="dashboard-kpis">
    <?= tableroKpi('Evidencias registradas',$stats['total_evidencias']) ?>
    <?= tableroKpi('Documentos procesados',$stats['documentos_procesados'],'Conteo por archivo') ?>
    <?= tableroKpi('Documentos por validar',$stats['documentos_pendientes_revision'],'Revisión humana') ?>
    <?= tableroKpi('Evidencias clasificadas',$stats['evidencias_clasificadas'],'Vínculos configurados a criterios') ?>
    <?= tableroKpi('Evidencias observadas',$stats['observadas']) ?>
    <?= tableroKpi('Evidencias aceptadas',$stats['aceptadas']) ?>
  </div>

  <?php if($stats['documentos_procesados']===null): ?>
    <p class="dashboard-note">El módulo de procesamiento todavía no está instalado. Las cargas siguen disponibles; estos indicadores aparecerán cuando se aplique la migración correspondiente.</p>
  <?php endif; ?>

  <section class="card" aria-labelledby="instrumentos-title">
    <div class="card-content">
      <div class="dashboard-section-heading"><div><h2 id="instrumentos-title" class="text-lg font-semibold">Resumen por instrumento</h2><p class="text-muted-foreground">Evidencias relacionadas con los instrumentos configurados.</p></div>
        <nav class="dashboard-anchor-nav" aria-label="Ir a instrumento">
          <?php foreach($stats['instrumentos'] as $item): ?><a class="btn" href="#inst-<?= (int)$item['id_instrumento'] ?>"><?= tableroH($item['abreviatura']) ?></a><?php endforeach; ?>
        </nav>
      </div>
      <?php if(!$stats['instrumentos']): ?><div class="dashboard-empty">No hay instrumentos activos.</div><?php else: ?>
        <div class="dashboard-instrument-grid">
          <?php foreach($stats['instrumentos'] as $item): $req=$item['requisitos']; ?>
            <article id="inst-<?= (int)$item['id_instrumento'] ?>" class="card dashboard-instrument">
              <div class="card-content">
                <p class="dashboard-eyebrow"><?= tableroH($item['abreviatura']) ?></p>
                <h3 class="font-semibold"><?= tableroH($item['nombre_completo']) ?></h3>
                <p class="dashboard-instrument-total"><?= number_format((int)$item['compatibles']) ?> <span>evidencias compatibles</span></p>
                <div class="dashboard-status-row"><span>Observadas</span><strong><?= $item['observadas']===null?'No disponible':number_format($item['observadas']) ?></strong></div>
                <div class="dashboard-status-row"><span>Aceptadas</span><strong><?= $item['aceptadas']===null?'No disponible':number_format($item['aceptadas']) ?></strong></div>
                <div class="dashboard-divider"></div>
                <h4 class="font-medium">Requisitos documentales</h4>
                <?php if($req===null || $req['total']===0): ?>
                  <p class="text-muted-foreground">Sin requisitos configurados para una convocatoria activa.</p>
                <?php else: ?>
                  <div class="dashboard-requirements">
                    <span>Documentados <b><?= (int)$req['documentados'] ?></b></span>
                    <span>Pendientes <b><?= (int)$req['pendientes'] ?></b></span>
                    <span>Por validar <b><?= (int)$req['por_validar'] ?></b></span>
                    <span>No aplicable <b><?= $req['no_aplicable']===null?'No definido':'0' ?></b></span>
                  </div>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="card">
    <div class="card-content">
      <div class="dashboard-section-heading"><div><h2 class="text-lg font-semibold">Evidencias recientes</h2><p class="text-muted-foreground">Solo se muestran tus registros visibles.</p></div><a class="btn" href="<?= BASE_URL ?>/views/docente/gestion-evidencias.php">Ver todas</a></div>
      <?php if(!$recent): ?><div class="dashboard-empty">Aún no has registrado evidencias.</div><?php else: ?>
        <div class="dashboard-table-wrap"><table class="table dashboard-table"><thead><tr><th>Evidencia</th><th>Tipo</th><th>Fecha</th><th></th></tr></thead><tbody>
          <?php foreach($recent as $e): ?><tr><td><?= tableroH($e['titulo']) ?></td><td><?= tableroH($e['nombre_tipo']??'—') ?></td><td><?= tableroH($e['fecha_subida']) ?></td><td><a class="btn" href="<?= BASE_URL ?>/views/docente/evidencia-detalle.php?id=<?= (int)$e['id_evidencia'] ?>">Abrir</a></td></tr><?php endforeach; ?>
        </tbody></table></div>
      <?php endif; ?>
    </div>
  </section>
  <p class="dashboard-note">Los indicadores describen documentación registrada y revisiones guardadas. No representan una probabilidad de obtener un reconocimiento.</p>
</section>
