<?php
$docenteId=(int)$usuarioActual['id_usuario'];
$docente=tableroObtenerDocente($conn,$docenteId);
$stats=tableroDatosDocente($conn,$docenteId);
$st=$conn->prepare('SELECT e.id_evidencia,e.titulo,e.fecha_subida,t.nombre_tipo FROM evidencias e LEFT JOIN tipos_de_evidencia t ON t.id_tipo_evidencia=e.id_tipo_evidencia WHERE e.id_docente=? AND e.ocultar=0 ORDER BY e.id_evidencia DESC LIMIT 6');
$st->bind_param('i',$docenteId); $st->execute(); $recent=$st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
?>
<style>
  /* =========================================================================
     Tablero docente
     - Autocontenido: todos los display (flex/grid) se declaran aquí, así la
       vista no depende de reglas globales que pueden no cargarse.
     - El layout responde al ancho del contenedor (container queries), por lo
       que se adapta solo cuando el menú lateral se abre o se colapsa.
     - Base pensada para móvil; las columnas se agregan al haber espacio.
     ========================================================================= */

  .dashboard-view-teacher {
    --td-gap: clamp(12px, 1.6vw, 24px);
    --td-pad: clamp(14px, 1.5vw, 20px);
    --td-line: var(--border, #e4e4e7);
    --td-surface: var(--card, #ffffff);
    --td-muted: var(--muted-foreground, #71717a);
    --td-accent: var(--primary, #2563eb);
    --td-warn: var(--warning, #d97706);
    --td-ok: var(--success, #16a34a);
    --td-radius: var(--radius, 12px);
    --td-radius-sm: var(--radius-sm, 8px);
    --td-shadow: 0 1px 2px rgba(0, 0, 0, .05);

    display: grid;
    align-content: start;
    gap: var(--td-gap);
    width: 100%;
    max-width: 1680px;
    min-width: 0;
    margin-inline: auto;
    container-type: inline-size;
    container-name: teacher;
  }
  .dashboard-view-teacher > * { min-width: 0; margin: 0; }
  .dashboard-instrument { scroll-margin-top: 96px; }

  /* ---------- Encabezado ---------- */
  .dashboard-view-teacher .dashboard-hero {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px 20px;
    padding: 4px 0 6px;
  }
  .dashboard-view-teacher .dashboard-hero > div { flex: 1 1 280px; min-width: 0; }
  .dashboard-view-teacher .dashboard-hero h1 {
    margin: 0 0 4px;
    font-size: clamp(1.35rem, 1.1rem + 1vw, 1.85rem);
    line-height: 1.2;
    letter-spacing: -.02em;
    overflow-wrap: anywhere;
    text-wrap: balance;
  }
  .dashboard-view-teacher .dashboard-eyebrow {
    margin: 0 0 4px;
    font-size: .75rem;
    font-weight: 600;
    color: var(--td-accent);
  }
  .dashboard-view-teacher .dashboard-hero .text-muted-foreground {
    max-width: 64ch;
    margin: 0;
    font-size: .9rem;
    line-height: 1.5;
    color: var(--td-muted);
  }
  .dashboard-view-teacher .dashboard-hero > .btn {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
  }

  /* ---------- Indicadores (KPI) ---------- */
  .dashboard-view-teacher .dashboard-kpis {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: clamp(8px, 1vw, 14px);
  }
  .dashboard-view-teacher .dashboard-kpi {
    display: flex;
    flex-direction: column;
    min-width: 0;
    border: 1px solid var(--td-line);
    border-radius: var(--td-radius);
    background: var(--td-surface);
    box-shadow: var(--td-shadow), inset 3px 0 0 var(--td-accent);
  }
  /* Acentos con significado: por validar y observadas (aviso), aceptadas (éxito) */
  .dashboard-view-teacher .dashboard-kpi:nth-child(3),
  .dashboard-view-teacher .dashboard-kpi:nth-child(5) { box-shadow: var(--td-shadow), inset 3px 0 0 var(--td-warn); }
  .dashboard-view-teacher .dashboard-kpi:nth-child(6) { box-shadow: var(--td-shadow), inset 3px 0 0 var(--td-ok); }
  .dashboard-view-teacher .dashboard-kpi .card-content {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    justify-content: space-between;
    gap: 4px;
    min-height: 92px;
    padding: 14px 16px;
  }
  .dashboard-view-teacher .dashboard-kpi-label { font-size: .78rem; line-height: 1.35; color: var(--td-muted); }
  .dashboard-view-teacher .dashboard-kpi-value {
    font-size: clamp(1.4rem, 1.1rem + .9vw, 1.9rem);
    font-weight: 650;
    line-height: 1.1;
    font-variant-numeric: tabular-nums;
    overflow-wrap: anywhere;
  }
  .dashboard-view-teacher .dashboard-kpi-hint { font-size: .72rem; line-height: 1.35; color: var(--td-muted); }

  /* ---------- Avisos ---------- */
  .dashboard-view-teacher .dashboard-note {
    margin: 0;
    padding: 10px 14px;
    border: 1px solid var(--td-line);
    border-radius: var(--td-radius-sm);
    background: color-mix(in srgb, var(--td-accent) 6%, var(--td-surface));
    box-shadow: inset 3px 0 0 var(--td-accent);
    color: inherit;
    font-size: .82rem;
    line-height: 1.5;
  }

  /* ---------- Tarjetas y encabezados de sección ---------- */
  .dashboard-view-teacher .card {
    display: flex;
    flex-direction: column;
    min-width: 0;
    border: 1px solid var(--td-line);
    border-radius: var(--td-radius);
    background: var(--td-surface);
    box-shadow: var(--td-shadow);
  }
  .dashboard-view-teacher .card-content { flex: 1 1 auto; min-width: 0; padding: var(--td-pad); }
  .dashboard-view-teacher .dashboard-section-heading {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px 18px;
    margin-bottom: 14px;
  }
  .dashboard-view-teacher .dashboard-section-heading > div { flex: 1 1 240px; min-width: 0; }
  .dashboard-view-teacher .dashboard-section-heading h2 { margin: 0 0 2px; font-size: 1.05rem; font-weight: 650; line-height: 1.3; }
  .dashboard-view-teacher .dashboard-section-heading .text-muted-foreground {
    margin: 0;
    font-size: .8rem;
    line-height: 1.5;
    color: var(--td-muted);
  }
  .dashboard-view-teacher .dashboard-section-heading > .btn { flex: 0 0 auto; min-height: 38px; }

  /* ---------- Navegación entre instrumentos (chips) ---------- */
  .dashboard-view-teacher .dashboard-anchor-nav {
    display: flex;
    flex: 1 1 100%;
    gap: 8px;
    max-width: 100%;
    padding: 2px 0 6px;
    overflow-x: auto;
    overscroll-behavior-inline: contain;
    scroll-snap-type: x proximity;
    scrollbar-width: thin;
    -webkit-overflow-scrolling: touch;
  }
  .dashboard-view-teacher .dashboard-anchor-nav .btn {
    display: inline-flex;
    flex: none;
    align-items: center;
    min-height: 36px;
    padding: 6px 14px;
    border-radius: 999px;
    font-size: .82rem;
    white-space: nowrap;
    scroll-snap-align: start;
    text-decoration: none;
  }

  /* ---------- Tarjetas por instrumento ---------- */
  .dashboard-view-teacher .dashboard-instrument-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr));
    align-items: stretch;
    gap: clamp(10px, 1.2vw, 16px);
  }
  .dashboard-view-teacher .dashboard-instrument {
    background: color-mix(in srgb, var(--td-muted) 4%, var(--td-surface));
    box-shadow: none;
  }
  .dashboard-view-teacher .dashboard-instrument .card-content { display: flex; flex-direction: column; padding: clamp(14px, 1.4vw, 18px); }
  .dashboard-view-teacher .dashboard-instrument .dashboard-eyebrow { margin-bottom: 2px; }
  .dashboard-view-teacher .dashboard-instrument h3 {
    margin: 0 0 6px;
    font-size: .98rem;
    font-weight: 650;
    line-height: 1.3;
    overflow-wrap: anywhere;
  }
  .dashboard-view-teacher .dashboard-instrument-total {
    margin: 4px 0 10px;
    font-size: 1.6rem;
    font-weight: 650;
    line-height: 1.1;
    font-variant-numeric: tabular-nums;
  }
  .dashboard-view-teacher .dashboard-instrument-total span { font-size: .8rem; font-weight: 400; color: var(--td-muted); }
  .dashboard-view-teacher .dashboard-status-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    min-height: 40px;
    padding: 6px 2px;
    border-bottom: 1px solid var(--td-line);
    font-size: .85rem;
  }
  .dashboard-view-teacher .dashboard-status-row:last-of-type { border-bottom: 0; }
  .dashboard-view-teacher .dashboard-status-row > :first-child { min-width: 0; overflow-wrap: anywhere; }
  .dashboard-view-teacher .dashboard-status-row strong { flex: none; font-weight: 650; font-variant-numeric: tabular-nums; }
  .dashboard-view-teacher .dashboard-divider { height: 1px; margin: 12px 0; background: var(--td-line); }
  .dashboard-view-teacher .dashboard-instrument h4 { margin: 0 0 8px; font-size: .85rem; font-weight: 650; }
  .dashboard-view-teacher .dashboard-instrument .text-muted-foreground {
    margin: 0;
    font-size: .8rem;
    line-height: 1.5;
    color: var(--td-muted);
  }

  /* Requisitos documentales: cuadrícula 2 × 2 */
  .dashboard-view-teacher .dashboard-requirements {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
  }
  .dashboard-view-teacher .dashboard-requirements span {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    min-width: 0;
    padding: 8px 10px;
    border-radius: var(--td-radius-sm);
    background: color-mix(in srgb, var(--td-muted) 9%, var(--td-surface));
    font-size: .78rem;
    line-height: 1.35;
    overflow-wrap: anywhere;
  }
  .dashboard-view-teacher .dashboard-requirements span:nth-child(2),
  .dashboard-view-teacher .dashboard-requirements span:nth-child(3) {
    background: color-mix(in srgb, var(--td-warn) 13%, var(--td-surface));
    color: var(--td-warn);
  }
  .dashboard-view-teacher .dashboard-requirements b { flex: 0 0 auto; font-size: .9rem; font-variant-numeric: tabular-nums; }

  /* ---------- Estado vacío ---------- */
  .dashboard-view-teacher .dashboard-empty {
    padding: 28px 16px;
    border: 1px dashed var(--td-line);
    border-radius: var(--td-radius-sm);
    background: color-mix(in srgb, var(--td-muted) 5%, transparent);
    font-size: .85rem;
    line-height: 1.5;
    text-align: center;
    color: var(--td-muted);
  }

  /* ---------- Tabla de evidencias recientes ---------- */
  .dashboard-view-teacher .dashboard-table-wrap {
    width: 100%;
    min-width: 0;
    overflow-x: auto;
    border: 1px solid var(--td-line);
    border-radius: var(--td-radius-sm);
    overscroll-behavior-inline: contain;
    -webkit-overflow-scrolling: touch;
  }
  .dashboard-view-teacher .dashboard-table { width: 100%; min-width: 560px; border-collapse: collapse; }
  .dashboard-view-teacher .dashboard-table th,
  .dashboard-view-teacher .dashboard-table td {
    padding: 10px 12px;
    font-size: .8rem;
    line-height: 1.4;
    text-align: start;
    vertical-align: middle;
    overflow-wrap: anywhere;
  }
  .dashboard-view-teacher .dashboard-table thead th {
    border-bottom: 1px solid var(--td-line);
    background: var(--muted, var(--td-surface));
    font-size: .74rem;
    font-weight: 600;
    white-space: nowrap;
    overflow-wrap: normal;
    color: var(--td-muted);
  }
  .dashboard-view-teacher .dashboard-table tbody tr { border-bottom: 1px solid var(--td-line); transition: background-color .15s ease; }
  .dashboard-view-teacher .dashboard-table tbody tr:last-child { border-bottom: 0; }
  .dashboard-view-teacher .dashboard-table td:last-child { white-space: nowrap; }
  .dashboard-view-teacher .dashboard-table td .btn { min-height: 32px; padding: 4px 12px; font-size: .78rem; }
  .dashboard-sr {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0 0 0 0);
    white-space: nowrap;
  }
  @media (hover: hover) {
    .dashboard-view-teacher .dashboard-table tbody tr:hover { background: color-mix(in srgb, var(--td-accent) 5%, transparent); }
  }

  /* ---------- Accesibilidad ---------- */
  .dashboard-view-teacher :is(a, button, .btn, [tabindex]):focus-visible {
    outline: 2px solid var(--ring, var(--td-accent));
    outline-offset: 2px;
  }
  @media (pointer: coarse) {
    .dashboard-view-teacher .btn { min-height: 44px; }
  }
  @media (prefers-reduced-motion: reduce) {
    .dashboard-view-teacher * { transition: none !important; }
  }

  /* =========================================================================
     Puntos de quiebre según el ancho del contenedor
     ========================================================================= */

  /* Contenedor muy angosto: indicadores y requisitos en una columna */
  @container teacher (max-width: 339px) {
    .dashboard-view-teacher .dashboard-kpis,
    .dashboard-view-teacher .dashboard-requirements { grid-template-columns: minmax(0, 1fr); }
    .dashboard-view-teacher .dashboard-kpi .card-content { min-height: 80px; }
  }

  /* Móvil: botones a ancho completo */
  @container teacher (max-width: 559px) {
    .dashboard-view-teacher .dashboard-hero > .btn,
    .dashboard-view-teacher .dashboard-section-heading > .btn { flex: 1 1 100%; justify-content: center; }
  }

  /* Móvil y tableta chica: la tabla se vuelve una lista de tarjetas */
  @container teacher (max-width: 599px) {
    .dashboard-view-teacher .dashboard-table-wrap { overflow: visible; border: 0; }
    .dashboard-view-teacher .dashboard-table { display: block; min-width: 0; }
    .dashboard-view-teacher .dashboard-table thead {
      position: absolute;
      width: 1px;
      height: 1px;
      overflow: hidden;
      clip: rect(0 0 0 0);
    }
    .dashboard-view-teacher .dashboard-table tbody { display: grid; gap: 10px; }
    .dashboard-view-teacher .dashboard-table tbody tr,
    .dashboard-view-teacher .dashboard-table tbody tr:last-child {
      display: block;
      padding: 4px 12px;
      border: 1px solid var(--td-line);
      border-radius: var(--td-radius-sm);
      background: var(--td-surface);
    }
    .dashboard-view-teacher .dashboard-table td {
      display: grid;
      grid-template-columns: minmax(84px, 34%) minmax(0, 1fr);
      gap: 4px 12px;
      padding: 9px 0;
      border-top: 1px solid var(--td-line);
      font-size: .85rem;
      white-space: normal;
    }
    .dashboard-view-teacher .dashboard-table td:first-child { border-top: 0; }
    .dashboard-view-teacher .dashboard-table td::before {
      content: attr(data-label);
      font-size: .74rem;
      font-weight: 600;
      color: var(--td-muted);
    }
    .dashboard-view-teacher .dashboard-table td[data-label=""] { display: block; }
    .dashboard-view-teacher .dashboard-table td[data-label=""]::before { content: none; }
    .dashboard-view-teacher .dashboard-table td[data-label=""] .btn { width: 100%; justify-content: center; }
  }

  /* Tableta: indicadores en 3 columnas (2 filas de 3) */
  @container teacher (min-width: 600px) {
    .dashboard-view-teacher .dashboard-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  }

  /* Tableta ancha: la navegación entre instrumentos se acomoda junto al título */
  @container teacher (min-width: 760px) {
    .dashboard-view-teacher .dashboard-anchor-nav {
      flex: 0 1 auto;
      flex-wrap: wrap;
      justify-content: flex-end;
      max-width: 60%;
      overflow: visible;
    }
  }

  /* Escritorio: los 6 indicadores se reparten en una sola fila */
  @container teacher (min-width: 1100px) {
    .dashboard-view-teacher .dashboard-kpis { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }
  }

  /* Móviles en horizontal */
  @media (max-height: 520px) and (orientation: landscape) {
    .dashboard-view-teacher { --td-gap: 12px; }
    .dashboard-view-teacher .dashboard-kpi .card-content { min-height: 76px; }
  }
</style>
<section class="dashboard-view-teacher" aria-labelledby="docente-titulo">
  <header class="dashboard-hero">
    <div>
      <p class="dashboard-eyebrow">Expediente académico</p>
      <h1 id="docente-titulo">Hola, <?= tableroH($docente['nombre']??'Docente') ?></h1>
      <p class="text-muted-foreground">Resumen privado de tus evidencias y su estado documental.</p>
    </div>
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
      <div class="dashboard-section-heading">
        <div><h2 id="instrumentos-title">Resumen por instrumento</h2><p class="text-muted-foreground">Evidencias relacionadas con los instrumentos configurados.</p></div>
        <?php if($stats['instrumentos']): ?>
        <nav class="dashboard-anchor-nav" aria-label="Ir a instrumento">
          <?php foreach($stats['instrumentos'] as $item): ?><a class="btn" href="#inst-<?= (int)$item['id_instrumento'] ?>"><?= tableroH($item['abreviatura']) ?></a><?php endforeach; ?>
        </nav>
        <?php endif; ?>
      </div>
      <?php if(!$stats['instrumentos']): ?><div class="dashboard-empty">No hay instrumentos activos.</div><?php else: ?>
        <div class="dashboard-instrument-grid">
          <?php foreach($stats['instrumentos'] as $item): $req=$item['requisitos']; ?>
            <article id="inst-<?= (int)$item['id_instrumento'] ?>" class="card dashboard-instrument">
              <div class="card-content">
                <p class="dashboard-eyebrow"><?= tableroH($item['abreviatura']) ?></p>
                <h3><?= tableroH($item['nombre_completo']) ?></h3>
                <p class="dashboard-instrument-total"><?= number_format((int)$item['compatibles']) ?> <span>evidencias compatibles</span></p>
                <div class="dashboard-status-row"><span>Observadas</span><strong><?= $item['observadas']===null?'No disponible':number_format($item['observadas']) ?></strong></div>
                <div class="dashboard-status-row"><span>Aceptadas</span><strong><?= $item['aceptadas']===null?'No disponible':number_format($item['aceptadas']) ?></strong></div>
                <div class="dashboard-divider"></div>
                <h4>Requisitos documentales</h4>
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

  <section class="card" aria-labelledby="recientes-title">
    <div class="card-content">
      <div class="dashboard-section-heading">
        <div><h2 id="recientes-title">Evidencias recientes</h2><p class="text-muted-foreground">Solo se muestran tus registros visibles.</p></div>
        <a class="btn" href="<?= BASE_URL ?>/views/docente/gestion-evidencias.php">Ver todas</a>
      </div>
      <?php if(!$recent): ?><div class="dashboard-empty">Aún no has registrado evidencias.</div><?php else: ?>
        <div class="dashboard-table-wrap">
          <table class="table dashboard-table">
            <thead><tr><th scope="col">Evidencia</th><th scope="col">Tipo</th><th scope="col">Fecha</th><th scope="col"><span class="dashboard-sr">Acciones</span></th></tr></thead>
            <tbody>
              <?php foreach($recent as $e): ?>
                <tr>
                  <td data-label="Evidencia"><?= tableroH($e['titulo']) ?></td>
                  <td data-label="Tipo"><?= tableroH($e['nombre_tipo']??'—') ?></td>
                  <td data-label="Fecha"><?= tableroH($e['fecha_subida']) ?></td>
                  <td data-label=""><a class="btn" href="<?= BASE_URL ?>/views/docente/evidencia-detalle.php?id=<?= (int)$e['id_evidencia'] ?>">Abrir</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <p class="dashboard-note">Los indicadores describen documentación registrada y revisiones guardadas. No representan una probabilidad de obtener un reconocimiento.</p>
</section>