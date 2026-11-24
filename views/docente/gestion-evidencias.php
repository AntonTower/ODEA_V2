<?php
/**
 * Gestión de evidencias
 * - Requiere sesión (validacion.php) y permiso (validacion-permiso.php).
 * - Usa header/left-menu/footer.
 * - Carga tabla con AJAX desde vista-gestion-evidencias.php.
 * - Búsqueda en cliente + filtro por tipo de evidencia (servidor).
 * - Rol 4 (docentes): ven solo sus evidencias, pueden agregar y editar solo las suyas.
 */

// Definición de constantes de rutas si no existen
if (!defined('BASE_DIR')) {
    define('BASE_DIR', realpath(__DIR__ . '/../../'));
    define('CONFIG_DIR', BASE_DIR . '/config');
    define('COMPONENTS_DIR', BASE_DIR . '/components');
}

require_once __DIR__ . '/../../config/paths.php';

require_once CONFIG_DIR . '/validacion.php';
require_once CONFIG_DIR . '/conexion.php';
require_once CONFIG_DIR . '/validacion-permiso.php';
require_once CONFIG_DIR . '/evidencias.php';

$usuarioActual = evidenciaUsuarioActual($conn);
if ((int)$usuarioActual['id_usuario'] <= 0) {
  destruirSesionSegura();
  header('Location: ' . BASE_URL . '/public/login.php');
  exit;
}
$user_id = (int)$usuarioActual['id_usuario'];
$user_role = (int)$usuarioActual['rol'];

include COMPONENTS_DIR . '/layout/header.php';
include COMPONENTS_DIR . '/layout/left-menu.php';

// Tipos de evidencia (para filtro)
$tipos = [];
$qT = $user_role === 3
  ? "SELECT DISTINCT t.id_tipo_evidencia, t.nombre_tipo
     FROM tipos_de_evidencia t
     INNER JOIN instrumento_tipo_evidencia ite ON ite.id_tipo_evidencia = t.id_tipo_evidencia
     INNER JOIN evaluador_instrumento ei ON ei.id_instrumento = ite.id_instrumento AND ei.id_evaluador = {$user_id}
     INNER JOIN instrumentos i ON i.id_instrumento = ite.id_instrumento AND i.activo = 1
     ORDER BY t.nombre_tipo ASC"
  : "SELECT id_tipo_evidencia, nombre_tipo FROM tipos_de_evidencia ORDER BY nombre_tipo ASC";
if ($rsT = mysqli_query($conn, $qT)) {
  while ($r = mysqli_fetch_assoc($rsT)) $tipos[] = $r;
}
?>
<style>
  /* =========================================================================
     Gestión de evidencias
     - El layout responde al ancho del contenedor (container queries), por lo
       que se adapta solo cuando el menú lateral se abre o se colapsa.
     - La tabla la inyecta AJAX; sus estilos se anclan a #tablaEvidencias y
       cubren DataTables 2 (dt-*) y su extensión Responsive (dtr-*).
     ========================================================================= */

  .evidencias-page {
    --ev-gap: clamp(12px, 1.6vw, 24px);
    --ev-pad: clamp(14px, 1.5vw, 20px);
    --ev-line: var(--border, #e4e4e7);
    --ev-surface: var(--card, #ffffff);
    --ev-muted: var(--muted-foreground, #71717a);
    --ev-accent: var(--primary, #2563eb);
    --ev-radius: var(--radius, 12px);
    --ev-radius-sm: var(--radius-sm, 8px);
    --ev-shadow: 0 1px 2px rgba(0, 0, 0, .05);

    display: grid;
    align-content: start;
    gap: var(--ev-gap);
    width: 100%;
    max-width: 1680px;
    min-width: 0;
    margin-inline: auto;
    container-type: inline-size;
    container-name: evidencias;
  }
  .evidencias-page > * { min-width: 0; margin: 0; }

  /* ---------- Encabezado ---------- */
  .evidencias-page .dashboard-hero {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px 20px;
    padding: 4px 0 2px;
  }
  .evidencias-page .dashboard-hero > div { flex: 1 1 280px; min-width: 0; }
  .evidencias-page .dashboard-hero h1 {
    margin: 0 0 4px;
    font-size: clamp(1.35rem, 1.1rem + 1vw, 1.85rem);
    line-height: 1.2;
    letter-spacing: -.02em;
    text-wrap: balance;
  }
  .evidencias-page .dashboard-eyebrow {
    margin: 0 0 4px;
    font-size: .75rem;
    font-weight: 600;
    color: var(--ev-accent);
  }
  .evidencias-page .dashboard-hero .text-muted-foreground {
    max-width: 68ch;
    margin: 0;
    font-size: .9rem;
    line-height: 1.5;
    color: var(--ev-muted);
  }
  .evidencias-page .dashboard-hero > .btn {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
  }

  /* ---------- Tarjetas ---------- */
  .evidencias-page .card {
    display: flex;
    flex-direction: column;
    min-width: 0;
    border: 1px solid var(--ev-line);
    border-radius: var(--ev-radius);
    background: var(--ev-surface);
    box-shadow: var(--ev-shadow);
  }
  .evidencias-page .card-content {
    flex: 1 1 auto;
    min-width: 0;
    padding: var(--ev-pad);
  }

  /* ---------- Barra de herramientas ---------- */
  .evidencias-page .evidencias-toolbar .card-content {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-items: end;
    gap: 12px 16px;
  }
  .evidencias-field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
  .evidencias-field > label { font-size: .8rem; font-weight: 600; }
  .evidencias-search-row { display: flex; align-items: center; gap: 8px; }
  .evidencias-search-row .input { flex: 1 1 auto; min-width: 0; }
  .evidencias-search-row .btn { flex: none; }
  .evidencias-field .input,
  .evidencias-field .btn { min-height: 42px; }
  .evidencias-field select.input { width: 100%; }

  /* ---------- Tabla (contenido inyectado por AJAX) ---------- */
  #tablaEvidencias .card-content { overflow-x: auto; }
  #tablaEvidencias > .card-content > p { padding: 24px 8px; text-align: center; color: var(--ev-muted); }

  #tablaEvidencias table.dataTable { width: 100%; border-collapse: collapse; }
  #tablaEvidencias table.dataTable thead th {
    padding-block: 10px;
    padding-inline-start: 12px;
    border-bottom: 1px solid var(--ev-line);
    background: var(--muted, var(--ev-surface));
    font-size: .74rem;
    font-weight: 600;
    text-align: start;
    white-space: nowrap;
    color: var(--ev-muted);
  }
  #tablaEvidencias table.dataTable tbody td {
    padding: 10px 12px;
    border: 0;
    border-bottom: 1px solid var(--ev-line);
    box-shadow: none;
    font-size: .85rem;
    line-height: 1.4;
    vertical-align: middle;
    overflow-wrap: anywhere;
  }
  #tablaEvidencias table.dataTable tbody tr:last-child td { border-bottom: 0; }
  #tablaEvidencias table.dataTable tbody td.dt-empty {
    padding: 36px 12px;
    text-align: center;
    color: var(--ev-muted);
  }
  #tablaEvidencias table.dataTable tbody td.dtr-control { width: 40px; }
  #tablaEvidencias table.dataTable tbody td .btn {
    min-height: 32px;
    margin: 2px 4px 2px 0;
    padding: 4px 10px;
    font-size: .78rem;
    white-space: nowrap;
  }
  @media (hover: hover) {
    #tablaEvidencias table.dataTable tbody tr:hover > td {
      background: color-mix(in srgb, var(--ev-accent) 5%, transparent);
    }
  }

  /* Filas expandidas (Responsive): lista de etiqueta y valor */
  #tablaEvidencias table.dataTable tbody tr.child td.child { padding: 0 12px 8px 12px; }
  #tablaEvidencias ul.dtr-details { display: grid; margin: 0; padding: 0; list-style: none; }
  #tablaEvidencias ul.dtr-details > li {
    display: grid;
    grid-template-columns: minmax(90px, 34%) minmax(0, 1fr);
    gap: 4px 12px;
    padding: 9px 0;
    border-top: 1px solid var(--ev-line);
  }
  #tablaEvidencias ul.dtr-details > li:first-child { border-top: 0; }
  #tablaEvidencias ul.dtr-details .dtr-title {
    min-width: 0;
    font-size: .74rem;
    font-weight: 600;
    color: var(--ev-muted);
  }
  #tablaEvidencias ul.dtr-details .dtr-data { min-width: 0; font-size: .85rem; overflow-wrap: anywhere; }

  /* Mostrar, información y paginación */
  #tablaEvidencias .dt-layout-row:not(.dt-layout-table),
  #tablaEvidencias .dt-bottom {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px 16px;
    margin-top: 14px;
    font-size: .82rem;
    color: var(--ev-muted);
  }
  #tablaEvidencias .dt-length { display: flex; align-items: center; gap: 8px; }
  #tablaEvidencias .dt-length select {
    min-height: 36px;
    padding: 4px 8px;
    border: 1px solid var(--ev-line);
    border-radius: var(--ev-radius-sm);
    background-color: var(--ev-surface);
    color: inherit;
  }
  #tablaEvidencias .dt-paging nav { display: flex; flex-wrap: wrap; gap: 4px; justify-content: center; }
  #tablaEvidencias .dt-paging .dt-paging-button {
    min-width: 36px;
    min-height: 36px;
    margin: 0;
    padding: 4px 10px;
    border: 1px solid var(--ev-line);
    border-radius: var(--ev-radius-sm);
    background: var(--ev-surface);
    font-size: .8rem;
    color: inherit;
    transition: border-color .15s ease, background-color .15s ease;
  }
  #tablaEvidencias .dt-paging .dt-paging-button.current,
  #tablaEvidencias .dt-paging .dt-paging-button.current:hover {
    border-color: var(--ev-accent);
    background: color-mix(in srgb, var(--ev-accent) 12%, var(--ev-surface));
    color: var(--ev-accent);
    font-weight: 600;
  }
  #tablaEvidencias .dt-paging .dt-paging-button.disabled,
  #tablaEvidencias .dt-paging .dt-paging-button.disabled:hover {
    opacity: .45;
    cursor: not-allowed;
    background: var(--ev-surface);
  }
  @media (hover: hover) {
    #tablaEvidencias .dt-paging .dt-paging-button:not(.current):not(.disabled):hover { border-color: var(--ev-accent); }
  }

  /* ---------- Diálogo de ocultar ---------- */
  #dlgEliminarEvi .dialog-card { width: min(100%, 520px); max-height: calc(100dvh - 24px); overflow: auto; }
  #dlgEliminarEvi .dialog-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px; }
  @media (max-width: 479px) {
    #dlgEliminarEvi .dialog-actions .btn { flex: 1 1 100%; min-height: 44px; }
  }

  /* ---------- Accesibilidad ---------- */
  .evidencias-page :is(a, button, .btn, select, input, [tabindex]):focus-visible {
    outline: 2px solid var(--ring, var(--ev-accent));
    outline-offset: 2px;
  }
  @media (pointer: coarse) {
    .evidencias-page .btn,
    .evidencias-page .dt-paging .dt-paging-button { min-height: 44px; }
    #tablaEvidencias table.dataTable tbody td .btn { min-height: 40px; }
  }
  @media (prefers-reduced-motion: reduce) {
    .evidencias-page * { transition: none !important; }
  }

  /* =========================================================================
     Puntos de quiebre según el ancho del contenedor
     ========================================================================= */

  /* Móvil: botón principal a ancho completo, pie de tabla centrado y apilado */
  @container evidencias (max-width: 559px) {
    .evidencias-page .dashboard-hero > .btn { flex: 1 1 100%; }
    #tablaEvidencias .dt-layout-row:not(.dt-layout-table),
    #tablaEvidencias .dt-bottom { flex-direction: column; align-items: center; text-align: center; }
    #tablaEvidencias .dt-paging .dt-paging-button.first,
    #tablaEvidencias .dt-paging .dt-paging-button.last { display: none; }
  }

  /* Tableta: búsqueda y filtro en una misma fila */
  @container evidencias (min-width: 600px) {
    .evidencias-page .evidencias-toolbar .card-content { grid-template-columns: minmax(0, 1fr) minmax(220px, 300px); }
  }

  /* Escritorio: filtro más amplio */
  @container evidencias (min-width: 1000px) {
    .evidencias-page .evidencias-toolbar .card-content { grid-template-columns: minmax(0, 1fr) 340px; }
  }
</style>

<section class="evidencias-page" aria-labelledby="evidencias-titulo">
  <header class="dashboard-hero">
    <div>
      <p class="dashboard-eyebrow">ODEA · Evidencias</p>
      <h1 id="evidencias-titulo">Gestión de evidencias</h1>
      <p class="text-muted-foreground">
        <?php if ($user_role === 3): ?>Consulta las evidencias de los instrumentos que tienes asignados.
        <?php else: ?>Administra evidencias; los docentes solo pueden modificar las propias. Archivos PDF o imágenes (máx. 10 MB).<?php endif; ?>
      </p>
    </div>

    <?php if (in_array($user_role, [1,2,4], true)): ?>
      <button class="btn btn-primary" id="openModalAgregarEvidencia" type="button" title="Agregar nueva evidencia">
        + Agregar Evidencia
      </button>
    <?php endif; ?>
  </header>

  <!-- Barra de herramientas -->
  <div class="card evidencias-toolbar">
    <div class="card-content">
      <div class="evidencias-field">
        <label for="search">Buscar</label>
        <div class="evidencias-search-row">
          <input id="search" class="input" placeholder="Buscar por título, docente o tipo…" autocomplete="off" />
          <button id="clearSearch" class="btn" type="button" title="Limpiar búsqueda">Limpiar</button>
        </div>
      </div>

      <!-- Filtro por tipo de evidencia (servidor) -->
      <div class="evidencias-field">
        <label for="filterTipo">Tipo de evidencia</label>
        <select id="filterTipo" class="input select-white">
          <option value="">Tipo: Todos</option>
          <?php foreach ($tipos as $t): ?>
            <option value="<?= (int)$t['id_tipo_evidencia'] ?>">
              <?= htmlspecialchars($t['nombre_tipo']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div id="tablaEvidencias" class="card" aria-live="polite">
    <div class="card-content">
      <p class="text-muted-foreground">Cargando…</p>
    </div>
  </div>
</section>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- jQuery + DataTables -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link  href="https://cdn.datatables.net/v/dt/dt-2.1.4/r-3.0.2/datatables.min.css" rel="stylesheet"/>
<script src="https://cdn.datatables.net/v/dt/dt-2.1.4/r-3.0.2/datatables.min.js"></script>

<!-- Modal Eliminar -->
<div class="dialog-backdrop" id="dlgBackdropEvi"></div>
<div class="dialog" id="dlgEliminarEvi" role="dialog" aria-modal="true" aria-labelledby="dlgEliminarEviTitle" data-open="false">
  <div class="dialog-card" style="max-width:520px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgEliminarEviTitle">Eliminar evidencia</h3>
      <p class="dialog-desc">Se ocultará la evidencia (no se mostrará a usuarios).</p>
    </div>
    <div class="dialog-body">
      <p id="dlgEliminarEviText">¿Estás seguro de ocultar esta evidencia?</p>
    </div>
    <div class="dialog-actions">
      <button class="btn" type="button" onclick="closeEliminarEvi()">Cancelar</button>
      <button class="btn btn-primary" type="button" onclick="confirmEliminarEvi()">Ocultar</button>
    </div>
  </div>
</div>

<?php
// Modales agregar / editar centralizados
$modal_agregar_evi = COMPONENTS_DIR . '/modals/modal-agregar-evidencia.php';
$modal_editar_evi  = COMPONENTS_DIR . '/modals/modal-editar-evidencia.php';

if (file_exists($modal_agregar_evi)) include $modal_agregar_evi;
if (file_exists($modal_editar_evi)) include $modal_editar_evi;
?>

<script>
/* ==== Utilidades ==== */
window.lockPageScroll = function(lock){
  const html = document.documentElement;
  html.style.overflow = lock ? 'hidden' : '';
};
const USER_ROLE = <?= (int)$user_role ?>;

/* ==== DataTable / Carga ==== */
const SEARCH_KEY = 'gestionEvidenciasSearch';
let dtE = null;
let currentTipo = '';

loadTableE();

document.addEventListener('DOMContentLoaded', () => {
  const sel = document.getElementById('filterTipo');
  if (sel) {
    sel.addEventListener('change', function(){
      currentTipo = this.value || '';
      loadTableE();
    });
  }
});

function loadTableE(){
  const url = '<?= BASE_URL ?>/views/docente/vista-gestion-evidencias.php?tipo=' + encodeURIComponent(currentTipo) + '&r=' + Date.now();
  const xhr = new XMLHttpRequest();
  xhr.onreadystatechange = function(){
    if (xhr.readyState === 4) {
      const cont = document.getElementById('tablaEvidencias');
      cont.innerHTML = xhr.status === 200 ? xhr.responseText
                                          : '<div class="card-content"><p>Error al cargar la tabla.</p></div>';
      initDTE();
    }
  };
  xhr.open('GET', url, true);
  xhr.send();
}

function initDTE(){
  const $table = $('#tabla-evidencias');
  if (!$table.length) return;

  if (dtE) { dtE.destroy(); dtE = null; }

  dtE = $table.DataTable({
    responsive: { details: { type: 'column', target: 0 } },
    columnDefs: [{ className: 'control', orderable: false, targets: 0 }],
    paging: true,
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    stateSave: true,
    order: [[1, 'desc']], // por ID desc
    dom: "<'dt-top'>t<'dt-bottom' l i p>",
    language: {
      info: "Mostrando _START_ a _END_ de _TOTAL_ evidencias",
      infoFiltered: "(filtrado de _MAX_ evidencias)",
      emptyTable: "No hay información",
      zeroRecords: "No se encontró coincidencia",
      lengthMenu: "Mostrar _MENU_",
      paginate: { first:"Primero", last:"Último", next:"Siguiente", previous:"Anterior" }
    }
  });

  $table.off('click.evidenceActions', 'button[data-evidence-action]')
    .on('click.evidenceActions', 'button[data-evidence-action]', function(){
      const id = this.dataset.id;
      switch (this.dataset.evidenceAction) {
        case 'details': window.openVerDetalles(id); break;
        case 'edit': window.openEditarEvidencia(id); break;
        case 'delete': window.openEliminarEvi(id, this.dataset.title || ''); break;
        case 'processing': window.openProcesamientoEvidencia(id); break;
      }
    });

  const $search = $('#search');
  const $clear  = $('#clearSearch');

  if ($search.length) {
    const saved = dtE.state()?.search?.search || localStorage.getItem(SEARCH_KEY) || '';
    $search.val(saved);
    if (saved) dtE.search(saved).draw();
    $search.off('input.evidencias').on('input.evidencias', function(){
      const val = this.value;
      dtE.search(val).draw();
      localStorage.setItem(SEARCH_KEY, val);
    });
  }
  if ($clear.length) {
    $clear.off('click.evidencias').on('click.evidencias', function(){
      if ($search.length) {
        $search.val('');
        localStorage.setItem(SEARCH_KEY, '');
      }
      dtE.search('').draw();
    });
  }
}

/* ==== Handlers globales ==== */

// Ver detalles
window.openVerDetalles = function(id){
  window.location.href = '<?= BASE_URL ?>/views/docente/evidencia-detalle.php?id=' + encodeURIComponent(id);
};

window.openProcesamientoEvidencia = function(id){
  fetch('<?= BASE_URL ?>/controllers/docente/procesamiento-estado.php?id='+encodeURIComponent(id))
    .then(r=>r.json()).then(j=>{
      if(!j || j.status!=='ok') throw new Error(j?.message||'No se pudo consultar el procesamiento.');
      const escapeHtml=value=>{const node=document.createElement('div');node.textContent=String(value??'');return node.innerHTML;};
      const labels={PENDIENTE:'Pendiente',EN_PROCESO:'En proceso',PROCESADO:'Procesado',REQUIERE_REVISION:'Requiere revisión',ERROR:'Error',CANCELADO:'Cancelado'};
      const items=j.trabajos||[];
      const html=items.length?items.map(t=>`<section style="text-align:left;border-bottom:1px solid #ddd;padding:10px 0">
        <strong>${t.tipo_origen==='EVIDENCIA'?'Documento principal':'Documento adjunto'}</strong>
        <div>Estado: ${escapeHtml(labels[t.estado]||t.estado)}${t.procesado_en?' · '+escapeHtml(t.procesado_en):''}</div>
        ${t.mensaje_error?`<div role="status">${escapeHtml(t.mensaje_error)}</div>`:''}
        ${Number(t.resultado_disponible)?`<button type="button" class="btn processing-result" data-job="${Number(t.id_trabajo)}">Revisar texto y metadatos</button>`:''}
        ${Number(t.can_retry)&&['ERROR','CANCELADO'].includes(t.estado)?`<button type="button" class="btn btn-primary processing-retry" data-id="${Number(t.id_trabajo)}">Reintentar</button>`:''}
      </section>`).join(''):(!j.disponible?'<p>La infraestructura de procesamiento aún no está configurada. La carga del documento se conserva.</p>':'<p>El documento aún no se ha encolado. La carga se conserva y puedes volver a guardar el archivo para programarlo.</p>');
      Swal.fire({title:'Procesamiento documental',html,width:720,showConfirmButton:true,didOpen:()=>{
        document.querySelectorAll('.processing-result').forEach(button=>button.addEventListener('click',()=>{
          window.location.href='<?= BASE_URL ?>/views/docente/revision-metadatos.php?id_evidencia='+encodeURIComponent(id)+'&id_trabajo='+encodeURIComponent(button.dataset.job);
        }));
        document.querySelectorAll('.processing-retry').forEach(button=>button.addEventListener('click',()=>{
          const token=document.querySelector('meta[name="csrf-token"]')?.content||'';
          const body=new FormData();body.append('id_trabajo',button.dataset.id);
          fetch('<?= BASE_URL ?>/controllers/docente/procesamiento-reintentar.php',{method:'POST',headers:{'X-CSRF-Token':token},body})
            .then(r=>r.json()).then(result=>{if(!result||result.status!=='ok')throw new Error(result?.message||'No se pudo reintentar.');Swal.fire({icon:'success',title:'En cola',text:result.message}).then(()=>window.openProcesamientoEvidencia(id));})
            .catch(error=>Swal.fire({icon:'error',title:'Error',text:error.message}));
        }));
      }});
    }).catch(error=>Swal.fire({icon:'error',title:'Error',text:error.message}));
};

// Editar (solo docentes o si decides permitir admin)
window.openEditarEvidencia = function(id){
  if (typeof window.openEditarEvidenciaModal === 'function') {
    window.openEditarEvidenciaModal(id);
  } else {
    if (window.Swal) Swal.fire({icon:'info', title:'Editar', text:'Implementa el modal de edición para la evidencia '+id+'.'});
  }
};

// Eliminar (soft: ocultar=1)
let __deleteEviId = null;
let __deleteEviName = '';
window.openEliminarEvi = function(id, titulo){
  __deleteEviId = id;
  __deleteEviName = titulo || '';
  const dlg = document.getElementById('dlgEliminarEvi');
  const backdrop = document.getElementById('dlgBackdropEvi');
  const txt = document.getElementById('dlgEliminarEviText');

  if (txt) txt.textContent = '¿Ocultar la evidencia ' + (__deleteEviName || ('#'+id)) + '?';
  if (dlg && backdrop) {
    dlg.setAttribute('data-open','true');
    backdrop.setAttribute('data-open','true');
    lockPageScroll(true);
  }
};
window.closeEliminarEvi = function(){
  __deleteEviId = null;
  const dlg = document.getElementById('dlgEliminarEvi');
  const backdrop = document.getElementById('dlgBackdropEvi');
  if (dlg && backdrop) {
    dlg.setAttribute('data-open','false');
    backdrop.setAttribute('data-open','false');
    lockPageScroll(false);
  }
};
window.confirmEliminarEvi = function(){
  if (!__deleteEviId) return;
  fetch('<?= BASE_URL ?>/controllers/docente/evidencia-eliminar.php', {
    method: 'POST',
    headers: { 'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8' },
    body: new URLSearchParams({ id_evidencia: __deleteEviId })
  })
  .then(r=>r.json())
  .then(j=>{
    closeEliminarEvi();
    if (j && j.status === 'ok') {
      if (window.Swal) Swal.fire({icon:'success', title:'Ocultado', text:'La evidencia se ocultó correctamente.'});
      loadTableE();
    } else {
      const msg = (j && j.message) ? j.message : 'No se pudo ocultar.';
      if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
    }
  })
  .catch(()=>{
    closeEliminarEvi();
    if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'No se pudo ocultar la evidencia.'});
  });
};

// Cerrar modal eliminar tocando el backdrop
(function(){
  const backdrop = document.getElementById('dlgBackdropEvi');
  if (backdrop) backdrop.addEventListener('click', window.closeEliminarEvi);
})();
</script>

<?php include COMPONENTS_DIR . '/layout/footer.php'; ?>