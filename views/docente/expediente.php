<?php
require_once __DIR__.'/../../config/paths.php';
require_once CONFIG_DIR.'/validacion.php';
require_once CONFIG_DIR.'/conexion.php';
require_once CONFIG_DIR.'/seguridad.php';
require_once CONFIG_DIR.'/tableros.php';
require_once CONFIG_DIR.'/reportes.php';
$usuarioActual=seguridadUsuarioActual($conn); $role=(int)$usuarioActual['rol']; $isAdmin=in_array($role,[1,2],true);
if(!$isAdmin && $role!==4){http_response_code(403);exit('No tienes permiso para consultar expedientes docentes.');}
$docenteId=tableroExpedienteDocenteId($conn,$usuarioActual,(int)($_GET['id_docente']??0))??0;
$docente=$docenteId>0?tableroObtenerDocente($conn,$docenteId):null;
$docentes=[];
if($isAdmin){$rs=$conn->query("SELECT id_usuario,nombre,apellidop,apellidom FROM usuarios WHERE rol=4 AND activo=1 ORDER BY apellidop,apellidom,nombre");while($r=$rs->fetch_assoc())$docentes[]=$r;$rs->free();}
$evidencias=[];$legacy=[];$history=[];
if($docente){
  $st=$conn->prepare("SELECT e.id_evidencia,e.titulo,e.archivo,e.fecha_subida,t.nombre_tipo
    FROM evidencias e LEFT JOIN tipos_de_evidencia t ON t.id_tipo_evidencia=e.id_tipo_evidencia
    WHERE e.id_docente=? AND e.ocultar=0 ORDER BY e.fecha_subida DESC,e.id_evidencia DESC");
  $st->bind_param('i',$docenteId);$st->execute();$evidencias=$st->get_result()->fetch_all(MYSQLI_ASSOC);$st->close();
  $dictamenes=reportesConsultarDictamenes($conn,$usuarioActual,0,$docenteId);
  $legacy=array_slice($dictamenes,0,100);
  $instrumentosPorEvidencia=[];
  foreach($dictamenes as $dictamen){
    $instrumentosPorEvidencia[(int)$dictamen['id_evidencia']][$dictamen['abreviatura']]=true;
  }
  foreach($evidencias as &$evidencia){
    $evidencia['instrumentos']=implode(', ',array_keys($instrumentosPorEvidencia[(int)$evidencia['id_evidencia']]??[]));
  }
  unset($evidencia);
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

/* Datos de presentación (no alteran las consultas) */
$iniciales='';
if($docente){
  foreach(array_slice(preg_split('/\s+/',trim((string)$docente['nombre_completo'])),0,2) as $parte){
    $iniciales.=function_exists('mb_substr')?mb_strtoupper(mb_substr($parte,0,1)):strtoupper(substr($parte,0,1));
  }
}
$totalEvaluaciones=count($dictamenes ?? []);
$subtituloEvaluaciones=$history
  ?'Cambios de estado y calificaciones, del más reciente al más antiguo (máx. 100).'
  :($legacy?'Calificaciones vigentes, de la más reciente a la más antigua (máx. 100).':'Calificaciones y cambios de estado.');
$seccionesPendientes=['actividades'=>'Actividades académicas','produccion'=>'Producción científica','tutorias'=>'Tutorías y dirección de tesis','participaciones'=>'Participaciones','reconocimientos'=>'Reconocimientos'];

include COMPONENTS_DIR.'/layout/header.php';include COMPONENTS_DIR.'/layout/left-menu.php';
?>
<style>
  /* =========================================================================
     Expediente académico
     - Responde al ancho del contenedor (container queries), no al viewport,
       por lo que se ajusta solo cuando el menú lateral se abre o colapsa.
     - Base pensada para móvil; las columnas se agregan al haber espacio.
     ========================================================================= */

  .expediente-page {
    --ex-gap: clamp(12px, 1.6vw, 24px);
    --ex-pad: clamp(14px, 1.5vw, 20px);
    --ex-line: var(--border, #e4e4e7);
    --ex-surface: var(--card, #ffffff);
    --ex-muted: var(--muted-foreground, #71717a);
    --ex-accent: var(--primary, #2563eb);
    --ex-radius: var(--radius, 12px);
    --ex-radius-sm: var(--radius-sm, 8px);
    --ex-shadow: 0 1px 2px rgba(0, 0, 0, .05);

    display: grid;
    align-content: start;
    gap: var(--ex-gap);
    width: 100%;
    max-width: 1400px;
    min-width: 0;
    margin-inline: auto;
    container-type: inline-size;
    container-name: expediente;
  }
  .expediente-page > * { min-width: 0; margin: 0; }

  /* ---------- Encabezado ---------- */
  .expediente-page .dashboard-hero {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px 20px;
    padding: 4px 0 2px;
  }
  .expediente-page .dashboard-hero > div { flex: 1 1 280px; min-width: 0; }
  .expediente-page .dashboard-hero h1 {
    margin: 0 0 4px;
    font-size: clamp(1.35rem, 1.1rem + 1vw, 1.85rem);
    line-height: 1.2;
    letter-spacing: -.02em;
    text-wrap: balance;
  }
  .expediente-page .dashboard-eyebrow {
    margin: 0 0 4px;
    font-size: .75rem;
    font-weight: 600;
    color: var(--ex-accent);
  }
  .expediente-page .dashboard-hero .text-muted-foreground {
    max-width: 64ch;
    margin: 0;
    font-size: .9rem;
    line-height: 1.5;
    color: var(--ex-muted);
  }
  .expediente-page .dashboard-hero > .btn { flex: 0 0 auto; }

  /* ---------- Selector de docente (administración) ---------- */
  .expediente-page .dashboard-filters {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-items: end;
    gap: 12px;
    padding: var(--ex-pad);
    border: 1px solid var(--ex-line);
    border-radius: var(--ex-radius);
    background: var(--ex-surface);
    box-shadow: var(--ex-shadow);
  }
  .expediente-page .dashboard-filters label {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
    font-size: .8rem;
    font-weight: 600;
  }
  .expediente-page .dashboard-filters .input { width: 100%; min-height: 42px; }
  .expediente-page .dashboard-filters .btn { width: 100%; min-height: 42px; }

  /* ---------- Tarjetas ---------- */
  .expediente-page .card {
    display: flex;
    flex-direction: column;
    min-width: 0;
    border: 1px solid var(--ex-line);
    border-radius: var(--ex-radius);
    background: var(--ex-surface);
    box-shadow: var(--ex-shadow);
  }
  .expediente-page .card-content {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    min-width: 0;
    padding: var(--ex-pad);
  }
  .expediente-anchor { scroll-margin-top: 90px; }

  /* ---------- Ficha del docente ---------- */
  .expediente-page .expediente-person {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    align-items: center;
    gap: 14px 16px;
  }
  .expediente-avatar {
    display: grid;
    place-items: center;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: color-mix(in srgb, var(--ex-accent) 12%, var(--ex-surface));
    color: var(--ex-accent);
    font-size: 1.05rem;
    font-weight: 650;
    user-select: none;
  }
  .expediente-identity { min-width: 0; }
  .expediente-identity .dashboard-eyebrow { margin-bottom: 2px; }
  .expediente-identity h2 {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 650;
    line-height: 1.3;
    overflow-wrap: anywhere;
  }
  .expediente-identity p {
    margin: 2px 0 0;
    font-size: .85rem;
    color: var(--ex-muted);
    overflow-wrap: anywhere;
  }
  .expediente-person-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    grid-column: 1 / -1;
    gap: 8px 12px;
    padding-top: 12px;
    border-top: 1px solid var(--ex-line);
    font-size: .82rem;
    color: var(--ex-muted);
  }

  /* ---------- Navegación entre secciones ---------- */
  .expediente-nav {
    display: flex;
    gap: 8px;
    padding: 2px 0 6px;
    overflow-x: auto;
    scroll-snap-type: x proximity;
    scrollbar-width: thin;
    -webkit-overflow-scrolling: touch;
  }
  .expediente-nav a {
    display: inline-flex;
    flex: none;
    align-items: center;
    min-height: 38px;
    padding: 6px 14px;
    border: 1px solid var(--ex-line);
    border-radius: 999px;
    background: var(--ex-surface);
    font-size: .82rem;
    white-space: nowrap;
    scroll-snap-align: start;
    text-decoration: none;
    transition: border-color .15s ease, color .15s ease;
  }
  @media (hover: hover) {
    .expediente-nav a:hover { border-color: var(--ex-accent); color: var(--ex-accent); }
  }

  /* ---------- Encabezado de sección ---------- */
  .expediente-heading {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 6px 12px;
    margin-bottom: 14px;
  }
  .expediente-heading > div { flex: 1 1 220px; min-width: 0; }
  .expediente-heading h2 { margin: 0; font-size: 1.05rem; font-weight: 650; line-height: 1.3; }
  .expediente-heading p { margin: 2px 0 0; font-size: .8rem; line-height: 1.5; color: var(--ex-muted); }
  .expediente-heading .badge { flex: none; }

  /* ---------- Aviso ---------- */
  .expediente-page .dashboard-note {
    margin: 0 0 14px;
    padding: 10px 14px;
    border: 1px solid var(--ex-line);
    border-radius: var(--ex-radius-sm);
    background: color-mix(in srgb, var(--ex-accent) 6%, var(--ex-surface));
    box-shadow: inset 3px 0 0 var(--ex-accent);
    font-size: .82rem;
    line-height: 1.5;
  }
  .expediente-page > .dashboard-note { margin: 0; }

  /* ---------- Estados vacíos ---------- */
  .expediente-page .dashboard-empty {
    padding: 24px 16px;
    border: 1px dashed var(--ex-line);
    border-radius: var(--ex-radius-sm);
    background: color-mix(in srgb, var(--ex-muted) 5%, transparent);
    font-size: .85rem;
    line-height: 1.5;
    text-align: center;
    color: var(--ex-muted);
  }
  .expediente-page .card-content.dashboard-empty {
    padding: clamp(28px, 6vw, 56px) 20px;
    border: 0;
    background: none;
  }

  /* ---------- Chips (instrumentos, estados) ---------- */
  .expediente-chips { display: flex; flex-wrap: wrap; gap: 4px; }

  /* ---------- Tablas ---------- */
  .expediente-page .dashboard-table-wrap {
    width: 100%;
    min-width: 0;
    max-height: min(70vh, 520px);
    overflow: auto;
    border: 1px solid var(--ex-line);
    border-radius: var(--ex-radius-sm);
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
    background:
      linear-gradient(to right, var(--ex-surface) 30%, transparent) left center / 24px 100% no-repeat local,
      linear-gradient(to left, var(--ex-surface) 30%, transparent) right center / 24px 100% no-repeat local,
      radial-gradient(farthest-side at 0 50%, rgba(0, 0, 0, .16), transparent) left center / 10px 100% no-repeat scroll,
      radial-gradient(farthest-side at 100% 50%, rgba(0, 0, 0, .16), transparent) right center / 10px 100% no-repeat scroll;
    background-color: var(--ex-surface);
  }
  .expediente-page .dashboard-table {
    width: 100%;
    min-width: 560px;
    border-collapse: collapse;
  }
  .expediente-page .dashboard-table th,
  .expediente-page .dashboard-table td {
    padding: 10px 12px;
    font-size: .8rem;
    line-height: 1.4;
    text-align: start;
    vertical-align: middle;
    overflow-wrap: anywhere;
  }
  .expediente-page .dashboard-table thead th {
    position: sticky;
    top: 0;
    z-index: 1;
    border-bottom: 1px solid var(--ex-line);
    background: var(--muted, var(--ex-surface));
    font-size: .74rem;
    font-weight: 600;
    white-space: nowrap;
    overflow-wrap: normal;
    color: var(--ex-muted);
  }
  .expediente-page .dashboard-table tbody tr { border-bottom: 1px solid var(--ex-line); transition: background-color .15s ease; }
  .expediente-page .dashboard-table tbody tr:last-child { border-bottom: 0; }
  .expediente-page .dashboard-table td .btn { white-space: nowrap; }
  @media (hover: hover) {
    .expediente-page .dashboard-table tbody tr:hover { background: color-mix(in srgb, var(--ex-accent) 5%, transparent); }
  }
  .expediente-sr {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0 0 0 0);
    white-space: nowrap;
  }

  /* ---------- Secciones sin módulo ---------- */
  .expediente-pending {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: var(--ex-gap);
  }
  .expediente-pending .card-content { gap: 12px; }
  .expediente-pending h2 { margin: 0; font-size: 1rem; font-weight: 650; line-height: 1.3; }
  .expediente-pending .dashboard-empty { padding: 18px 12px; }

  /* ---------- Accesibilidad ---------- */
  .expediente-page :is(a, button, .btn, select, input, [tabindex]):focus-visible {
    outline: 2px solid var(--ring, var(--ex-accent));
    outline-offset: 2px;
  }
  @media (pointer: coarse) {
    .expediente-page .btn { min-height: 44px; }
  }
  @media (prefers-reduced-motion: reduce) {
    .expediente-page * { transition: none !important; }
  }

  /* =========================================================================
     Puntos de quiebre según el ancho del contenedor
     ========================================================================= */

  /* Móvil: botón principal a ancho completo y tablas convertidas en tarjetas */
  @container expediente (max-width: 559px) {
    .expediente-page .dashboard-hero > .btn { flex: 1 1 100%; }
  }
  @container expediente (max-width: 679px) {
    .expediente-page .dashboard-table-wrap {
      max-height: none;
      overflow: visible;
      border: 0;
      background: none;
    }
    .expediente-page .dashboard-table { display: block; min-width: 0; }
    .expediente-page .dashboard-table thead {
      position: absolute;
      width: 1px;
      height: 1px;
      overflow: hidden;
      clip: rect(0 0 0 0);
    }
    .expediente-page .dashboard-table tbody { display: grid; gap: 10px; }
    .expediente-page .dashboard-table tbody tr,
    .expediente-page .dashboard-table tbody tr:last-child {
      display: block;
      padding: 4px 12px;
      border: 1px solid var(--ex-line);
      border-radius: var(--ex-radius-sm);
      background: var(--ex-surface);
    }
    .expediente-page .dashboard-table td {
      display: grid;
      grid-template-columns: minmax(84px, 34%) minmax(0, 1fr);
      gap: 4px 12px;
      padding: 9px 0;
      border-top: 1px solid var(--ex-line);
      font-size: .85rem;
    }
    .expediente-page .dashboard-table td:first-child { border-top: 0; }
    .expediente-page .dashboard-table td::before {
      content: attr(data-label);
      font-size: .74rem;
      font-weight: 600;
      color: var(--ex-muted);
    }
    .expediente-page .dashboard-table td[data-label=""] { display: block; }
    .expediente-page .dashboard-table td[data-label=""]::before { content: none; }
    .expediente-page .dashboard-table td[data-label=""] .btn { width: 100%; justify-content: center; }
  }

  /* Tableta: selector en una fila y fichas con datos a la derecha */
  @container expediente (min-width: 560px) {
    .expediente-page .dashboard-filters { grid-template-columns: minmax(0, 1fr) auto; }
    .expediente-page .dashboard-filters .btn { width: auto; }
  }
  @container expediente (min-width: 680px) {
    .expediente-page .expediente-person { grid-template-columns: auto minmax(0, 1fr) auto; }
    .expediente-person-meta {
      flex-direction: column;
      align-items: flex-end;
      grid-column: auto;
      padding-top: 0;
      border-top: 0;
    }
    .expediente-pending { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .expediente-pending > *:last-child:nth-child(odd) { grid-column: 1 / -1; }
  }

  /* Escritorio: navegación visible completa y 3 columnas de secciones pendientes */
  @container expediente (min-width: 900px) {
    .expediente-nav { flex-wrap: wrap; overflow: visible; }
  }
  @container expediente (min-width: 1100px) {
    .expediente-pending { grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .expediente-pending > * { grid-column: span 2; }
    .expediente-pending > *:nth-child(n+4) { grid-column: span 3; }
  }

  /* Móviles en horizontal */
  @media (max-height: 520px) and (orientation: landscape) {
    .expediente-page { --ex-gap: 12px; }
    .expediente-page .dashboard-table-wrap { max-height: 70vh; }
  }
</style>
<section class="expediente-page" aria-labelledby="expediente-titulo">
  <header class="dashboard-hero">
    <div>
      <p class="dashboard-eyebrow">ODEA · Expediente</p>
      <h1 id="expediente-titulo">Expediente académico</h1>
      <p class="text-muted-foreground">Vista unificada de información existente, sin duplicar registros.</p>
    </div>
    <?php if($role===4): ?><a class="btn btn-primary" href="<?= BASE_URL ?>/views/docente/gestion-evidencias.php">Gestionar evidencias</a><?php endif; ?>
  </header>

  <?php if($isAdmin): ?>
  <form class="dashboard-filters" method="get">
    <label>Docente
      <select class="input" name="id_docente" required>
        <option value="">Selecciona un docente</option>
        <?php foreach($docentes as $row): ?><option value="<?= (int)$row['id_usuario'] ?>" <?= $docenteId===(int)$row['id_usuario']?'selected':'' ?>><?= tableroH(trim($row['nombre'].' '.$row['apellidop'].' '.$row['apellidom'])) ?></option><?php endforeach; ?>
      </select>
    </label>
    <button class="btn btn-primary" type="submit">Abrir expediente</button>
  </form>
  <?php endif; ?>

  <?php if(!$docente): ?>
    <div class="card"><div class="card-content dashboard-empty"><?= $isAdmin?'Selecciona un docente activo para consultar su expediente.':'El expediente no está disponible.' ?></div></div>
  <?php else: ?>

    <section class="card" aria-label="Datos del docente">
      <div class="card-content expediente-person">
        <div class="expediente-avatar" aria-hidden="true"><?= tableroH($iniciales) ?></div>
        <div class="expediente-identity">
          <p class="dashboard-eyebrow">Docente</p>
          <h2><?= tableroH($docente['nombre_completo']) ?></h2>
          <p><?= tableroH($docente['correo']) ?></p>
        </div>
        <div class="expediente-person-meta">
          <span class="badge">Cuenta activa</span>
          <span><?= count($evidencias) ?> evidencias visibles</span>
        </div>
      </div>
    </section>

    <nav class="expediente-nav" aria-label="Secciones del expediente">
      <a class="btn" href="#evidencias">Evidencias</a>
      <a class="btn" href="#evaluaciones">Evaluaciones</a>
      <a class="btn" href="#actividades">Actividades</a>
      <a class="btn" href="#produccion">Producción científica</a>
      <a class="btn" href="#tutorias">Tutorías y tesis</a>
      <a class="btn" href="#participaciones">Participaciones</a>
      <a class="btn" href="#reconocimientos">Reconocimientos</a>
    </nav>

    <section id="evidencias" class="card expediente-anchor">
      <div class="card-content">
        <div class="expediente-heading dashboard-section-heading">
          <div><h2>Evidencias</h2><p>Registros existentes del docente.</p></div>
          <span class="badge"><?= count($evidencias) ?> registros</span>
        </div>
        <?php if(!$evidencias): ?>
          <div class="dashboard-empty">No hay evidencias visibles registradas.</div>
        <?php else: ?>
          <div class="dashboard-table-wrap">
            <table class="table dashboard-table">
              <thead><tr><th scope="col">ID</th><th scope="col">Título</th><th scope="col">Tipo documental</th><th scope="col">Instrumentos evaluados</th><th scope="col">Fecha</th><th scope="col"><span class="expediente-sr">Acciones</span></th></tr></thead>
              <tbody>
              <?php foreach($evidencias as $e):
                $instrumentos=array_filter(array_map('trim',explode(',',(string)($e['instrumentos']??''))),'strlen'); ?>
                <tr>
                  <td data-label="ID"><?= (int)$e['id_evidencia'] ?></td>
                  <td data-label="Título"><?= tableroH($e['titulo']) ?></td>
                  <td data-label="Tipo documental"><?= tableroH($e['nombre_tipo']??'—') ?></td>
                  <td data-label="Instrumentos"><?php if($instrumentos): ?><span class="expediente-chips"><?php foreach($instrumentos as $abreviatura): ?><span class="badge"><?= tableroH($abreviatura) ?></span><?php endforeach; ?></span><?php else: ?>—<?php endif; ?></td>
                  <td data-label="Fecha"><?= tableroH($e['fecha_subida']) ?></td>
                  <td data-label=""><a class="btn" href="<?= BASE_URL ?>/views/docente/evidencia-detalle.php?id=<?= (int)$e['id_evidencia'] ?>">Ver detalle</a></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <section id="evaluaciones" class="card expediente-anchor">
      <div class="card-content">
        <div class="expediente-heading dashboard-section-heading">
          <div><h2>Historial de evaluaciones</h2><p><?= tableroH($subtituloEvaluaciones) ?></p></div>
          <?php if($totalEvaluaciones>0): ?><span class="badge"><?= (int)$totalEvaluaciones ?> registros</span><?php endif; ?>
        </div>
        <?php if(!tableroTieneTablas($conn,['historial_evaluaciones','evaluaciones_versionadas'])): ?>
          <p class="dashboard-note">El historial detallado aún no está instalado. Se muestran las calificaciones vigentes existentes sin reconstruir eventos anteriores.</p>
        <?php endif; ?>
        <?php if($history): ?>
          <div class="dashboard-table-wrap">
            <table class="table dashboard-table">
              <thead><tr><th scope="col">Fecha</th><th scope="col">Evidencia</th><th scope="col">Instrumento</th><th scope="col">Evento</th><th scope="col">Estado</th><th scope="col">Comentario</th></tr></thead>
              <tbody>
              <?php foreach($history as $h): ?>
                <tr>
                  <td data-label="Fecha"><?= tableroH($h['creado_en']) ?></td>
                  <td data-label="Evidencia"><?= tableroH($h['titulo']) ?></td>
                  <td data-label="Instrumento"><span class="badge"><?= tableroH($h['abreviatura']) ?></span></td>
                  <td data-label="Evento"><?= tableroH($h['tipo_evento']) ?></td>
                  <td data-label="Estado"><?php if(!empty($h['estado_nuevo'])): ?><span class="badge"><?= tableroH($h['estado_nuevo']) ?></span><?php else: ?>—<?php endif; ?></td>
                  <td data-label="Comentario"><?= tableroH($h['comentario_nuevo']??$h['motivo']??'—') ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
        <?php if($legacy): ?>
          <div class="dashboard-table-wrap">
            <table class="table dashboard-table">
              <thead><tr><th scope="col">Fecha</th><th scope="col">Evidencia</th><th scope="col">Instrumento</th><th scope="col">Resultado</th><th scope="col">Comentario</th></tr></thead>
              <tbody>
              <?php foreach($legacy as $r): ?>
                <tr>
                  <td data-label="Fecha"><?= tableroH($r['actualizado_en']??$r['calificado_en']) ?></td>
                  <td data-label="Evidencia"><?= tableroH($r['titulo']) ?></td>
                  <td data-label="Instrumento"><span class="badge"><?= tableroH($r['abreviatura']) ?></span></td>
                  <td data-label="Resultado"><?= tableroH($r['estado'].' · '.($r['resultado']??'Sin resultado')) ?></td>
                  <td data-label="Comentario"><?= tableroH($r['comentario']??'—') ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="dashboard-empty">No hay evaluaciones registradas.</div>
        <?php endif; ?>
      </div>
    </section>

    <p class="dashboard-note">Los siguientes apartados aún no tienen un módulo independiente en ODEA. No se reclasifican atributos libres ni evidencias para completarlos.</p>
    <div class="expediente-pending">
      <?php foreach($seccionesPendientes as $anchor=>$title): ?>
        <section id="<?= tableroH($anchor) ?>" class="card expediente-anchor">
          <div class="card-content">
            <h2><?= tableroH($title) ?></h2>
            <div class="dashboard-empty">Sin módulo disponible.</div>
          </div>
        </section>
      <?php endforeach; ?>
    </div>

  <?php endif; ?>
</section>
<?php include COMPONENTS_DIR.'/layout/footer.php'; ?>