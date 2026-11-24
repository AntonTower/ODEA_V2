<?php
$stats=tableroDatosAdministrador($conn);
$jobs=$stats['procesamiento']; $totalJobs=array_sum($jobs);
?>
<style>
  .dashboard-view-admin {
    display: grid;
    align-content: start;
    gap: clamp(16px, 1.8vw, 24px);
    min-width: 0;
  }
  .dashboard-view-admin .dashboard-hero {
    align-items: center;
    gap: 12px 20px;
    margin: 0;
    padding: 0 0 2px;
  }
  .dashboard-view-admin .dashboard-hero > div { min-width: min(100%, 320px); }
  .dashboard-view-admin .dashboard-hero h1 {
    margin: 0 0 5px;
    font-size: clamp(1.4rem, 1.8vw, 1.7rem);
    letter-spacing: -.02em;
  }
  .dashboard-view-admin .dashboard-hero .text-muted-foreground { margin: 0; font-size: .86rem; }
  .dashboard-view-admin .dashboard-hero .btn { flex: 0 0 auto; min-height: 38px; }
  .dashboard-view-admin .dashboard-kpis {
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 150px), 1fr));
    gap: 10px;
  }
  .dashboard-view-admin .dashboard-kpi {
    min-width: 0;
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
  }
  .dashboard-view-admin .dashboard-kpi .card-content {
    min-height: 94px;
    gap: 5px;
    padding: 14px 16px;
  }
  .dashboard-view-admin .dashboard-kpi-label { font-size: .76rem; line-height: 1.35; }
  .dashboard-view-admin .dashboard-kpi-value { font-size: clamp(1.25rem, 1.8vw, 1.65rem); }
  .dashboard-view-admin .dashboard-kpi-hint { font-size: .7rem; }
  .dashboard-view-admin .dashboard-note {
    margin: 0;
    padding: 10px 13px;
    border-left-width: 3px;
    border-radius: var(--radius-sm);
    font-size: .8rem;
    line-height: 1.5;
  }
  .dashboard-view-admin .dashboard-two-column {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    align-items: start;
    gap: 14px;
  }
  .dashboard-view-admin .card { min-width: 0; box-shadow: var(--shadow-sm); }
  .dashboard-view-admin .card-content { min-width: 0; padding: clamp(15px, 1.6vw, 20px); }
  .dashboard-view-admin .card-content > h2,
  .dashboard-view-admin .dashboard-section-heading h2 { margin: 0 0 5px; font-size: 1rem; }
  .dashboard-view-admin .card-content > .text-muted-foreground,
  .dashboard-view-admin .dashboard-section-heading .text-muted-foreground {
    margin: 0 0 12px;
    font-size: .8rem;
    line-height: 1.45;
  }
  .dashboard-view-admin .dashboard-state-list { width: 100%; max-width: 620px; }
  .dashboard-view-admin .dashboard-status-row { min-height: 38px; padding: 7px 2px; font-size: .84rem; }
  .dashboard-view-admin .dashboard-status-row strong { font-variant-numeric: tabular-nums; }
  .dashboard-view-admin .dashboard-empty { padding: 18px 14px; font-size: .84rem; line-height: 1.5; }
  .dashboard-view-admin .dashboard-table-wrap {
    width: 100%;
    min-width: 0;
    overflow-x: auto;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    overscroll-behavior-inline: contain;
    -webkit-overflow-scrolling: touch;
  }
  .dashboard-view-admin .dashboard-table { width: 100%; min-width: 480px; }
  .dashboard-view-admin .dashboard-table th,
  .dashboard-view-admin .dashboard-table td { padding: 9px 11px; font-size: .8rem; }
  .dashboard-view-admin .dashboard-table th { white-space: nowrap; }
  .dashboard-view-admin .dashboard-table td { overflow-wrap: anywhere; }
  @media (max-width: 1100px) {
    .dashboard-view-admin .dashboard-two-column { grid-template-columns: minmax(0, 1fr); }
  }
  @media (max-width: 767px) {
    .dashboard-view-admin .dashboard-hero { align-items: flex-start; }
    .dashboard-view-admin .dashboard-hero .btn { width: 100%; justify-content: center; }
    .dashboard-view-admin .dashboard-two-column { gap: 12px; }
  }
  @media (max-width: 430px) {
    .dashboard-view-admin .dashboard-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .dashboard-view-admin .dashboard-kpi .card-content { min-height: 88px; padding: 13px; }
    .dashboard-view-admin .dashboard-table { min-width: 440px; }
  }
  @media (max-width: 359px) {
    .dashboard-view-admin .dashboard-kpis { grid-template-columns: minmax(0, 1fr); }
  }
  @media (max-height: 520px) and (orientation: landscape) {
    .dashboard-view-admin { gap: 14px; }
    .dashboard-view-admin .dashboard-kpi .card-content { min-height: 82px; }
  }
</style>
<section class="space-y-6 dashboard-view-admin">
  <header class="dashboard-hero"><div><p class="dashboard-eyebrow">Administración</p><h1 class="text-2xl font-semibold">Resumen institucional</h1><p class="text-muted-foreground">Indicadores agregados de ODEA.</p></div>
    <a class="btn" href="<?= BASE_URL ?>/views/admin/gestion-usuarios.php">Gestionar usuarios</a></header>
  <div class="dashboard-kpis">
    <?= tableroKpi('Docentes activos',$stats['docentes']) ?>
    <?= tableroKpi('Evidencias visibles',$stats['evidencias']) ?>
    <?= tableroKpi('Trabajos documentales',$stats['procesamiento_disponible']?$totalJobs:null,'Cola local') ?>
    <?= tableroKpi('Errores de procesamiento',$stats['procesamiento_disponible']?(int)($jobs['ERROR']??0):null) ?>
    <?= tableroKpi('Convocatorias activas',$stats['convocatorias_activas']) ?>
  </div>
  <?php if(!$stats['procesamiento_disponible'] || !$stats['convocatorias_disponibles']): ?>
    <p class="dashboard-note">Algunos indicadores están disponibles después de instalar las migraciones de procesamiento documental y convocatorias. No se estiman valores a partir de datos incompletos.</p>
  <?php endif; ?>
  <div class="dashboard-two-column">
    <section class="card"><div class="card-content"><div class="dashboard-section-heading"><div><h2 class="text-lg font-semibold">Procesamiento documental</h2><p class="text-muted-foreground">Conteo de trabajos por estado.</p></div></div>
      <?php if(!$stats['procesamiento_disponible']): ?><div class="dashboard-empty">Cola no instalada.</div><?php elseif(!$jobs): ?><div class="dashboard-empty">No hay trabajos registrados.</div><?php else: ?>
        <div class="dashboard-state-list"><?php foreach(['PENDIENTE'=>'Pendientes','EN_PROCESO'=>'En proceso','PROCESADO'=>'Procesados','REQUIERE_REVISION'=>'Por validar','ERROR'=>'Errores','CANCELADO'=>'Cancelados'] as $code=>$label): ?><div class="dashboard-status-row"><span><?= tableroH($label) ?></span><strong><?= number_format((int)($jobs[$code]??0)) ?></strong></div><?php endforeach; ?></div>
      <?php endif; ?>
    </div></section>
    <section class="card"><div class="card-content"><h2 class="text-lg font-semibold">Actividad de evaluadores</h2><p class="text-muted-foreground">Eventos de historial registrados.</p>
      <?php if(!$stats['actividad_disponible']): ?><div class="dashboard-empty">El historial de evaluaciones aún no está instalado.</div>
      <?php elseif(!$stats['actividad_evaluadores']): ?><div class="dashboard-empty">No hay eventos registrados.</div>
      <?php else: ?><div class="dashboard-table-wrap"><table class="table dashboard-table"><thead><tr><th>Evaluador</th><th>Eventos</th><th>Último evento</th></tr></thead><tbody>
        <?php foreach($stats['actividad_evaluadores'] as $activity): ?><tr><td><?= tableroH($activity['actor_nombre']?:'Evaluador') ?></td><td><?= number_format((int)$activity['eventos']) ?></td><td><?= tableroH($activity['ultimo']) ?></td></tr><?php endforeach; ?>
      </tbody></table></div><?php endif; ?>
    </div></section>
  </div>
  <section class="card"><div class="card-content"><h2 class="text-lg font-semibold">Estadística documental</h2><p class="text-muted-foreground">Evidencias visibles por tipo documental.</p>
    <?php if(!$stats['estadistica_documental']): ?><div class="dashboard-empty">No hay tipos documentales registrados.</div><?php else: ?><div class="dashboard-table-wrap"><table class="table dashboard-table"><thead><tr><th>Tipo documental</th><th>Evidencias</th></tr></thead><tbody>
      <?php foreach($stats['estadistica_documental'] as $type): ?><tr><td><?= tableroH($type['nombre_tipo']) ?></td><td><?= number_format((int)$type['evidencias']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
  </div></section>
</section>
