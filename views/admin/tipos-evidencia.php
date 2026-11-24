<?php
/**
 * Gestión de Tipos de Evidencia
 * - Requiere sesión y permisos.
 * - Usa header/left-menu/footer.
 * - Carga tabla por AJAX desde vista-tipos-evidencia.php
 * - Búsqueda en cliente (input externo persistente).
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

include COMPONENTS_DIR . '/layout/header.php';
include COMPONENTS_DIR . '/layout/left-menu.php';

// Instrumentos para los filtros
$instrumentos = [];
if ($rsI = $conn->query('SELECT id_instrumento, abreviatura FROM instrumentos ORDER BY id_instrumento')) {
  while ($fila = $rsI->fetch_assoc()) { $instrumentos[] = $fila; }
}
?>
<style>
  /* =========================================================================
     Tipos de evidencia
     - El layout responde al ancho del contenedor (container queries), por lo
       que se adapta solo cuando el menú lateral se abre o se colapsa.
     - La tabla la inyecta AJAX; sus estilos se anclan a #tablaGestionTipos y
       cubren DataTables 2 (dt-*) y su extensión Responsive (dtr-*).
     ========================================================================= */

  .tipos-page {
    --te-gap: clamp(12px, 1.6vw, 24px);
    --te-pad: clamp(14px, 1.5vw, 20px);
    --te-line: var(--border, #e4e4e7);
    --te-surface: var(--card, #ffffff);
    --te-muted: var(--muted-foreground, #71717a);
    --te-accent: var(--primary, #2563eb);
    --te-radius: var(--radius, 12px);
    --te-radius-sm: var(--radius-sm, 8px);
    --te-shadow: 0 1px 2px rgba(0, 0, 0, .05);

    display: grid;
    align-content: start;
    gap: var(--te-gap);
    width: 100%;
    max-width: 1680px;
    min-width: 0;
    margin-inline: auto;
    container-type: inline-size;
    container-name: tipos;
  }
  .tipos-page > * { min-width: 0; margin: 0; }

  /* ---------- Encabezado ---------- */
  .tipos-page .dashboard-hero {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px 20px;
    padding: 4px 0 2px;
  }
  .tipos-page .dashboard-hero > div { flex: 1 1 280px; min-width: 0; }
  .tipos-page .dashboard-hero h1 {
    margin: 0 0 4px;
    font-size: clamp(1.35rem, 1.1rem + 1vw, 1.85rem);
    line-height: 1.2;
    letter-spacing: -.02em;
    text-wrap: balance;
  }
  .tipos-page .dashboard-eyebrow {
    margin: 0 0 4px;
    font-size: .75rem;
    font-weight: 600;
    color: var(--te-accent);
  }
  .tipos-page .dashboard-hero .text-muted-foreground {
    max-width: 68ch;
    margin: 0;
    font-size: .9rem;
    line-height: 1.5;
    color: var(--te-muted);
  }
  .tipos-page .dashboard-hero > .btn {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
  }

  /* ---------- Tarjetas ---------- */
  .tipos-page .card {
    display: flex;
    flex-direction: column;
    min-width: 0;
    border: 1px solid var(--te-line);
    border-radius: var(--te-radius);
    background: var(--te-surface);
    box-shadow: var(--te-shadow);
  }
  .tipos-page .card-content {
    flex: 1 1 auto;
    min-width: 0;
    padding: var(--te-pad);
  }

  /* ---------- Barra de herramientas ---------- */
  .tipos-page .tipos-toolbar .card-content {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-items: start;
    gap: 16px 24px;
  }
  .tipos-field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
  .tipos-field > label,
  .tipos-field > .tipos-label { font-size: .8rem; font-weight: 600; }
  .tipos-hint { margin: 0; font-size: .75rem; line-height: 1.4; color: var(--te-muted); }
  .tipos-search-row { display: flex; align-items: center; gap: 8px; }
  .tipos-search-row .input { flex: 1 1 auto; min-width: 0; }
  .tipos-search-row .btn { flex: none; }
  .tipos-field .input,
  .tipos-search-row .btn { min-height: 42px; }

  /* Filtros por instrumento: chips marcables */
  .tipos-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 4px; }
  .tipos-chips label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 36px;
    padding: 6px 12px;
    border: 1px solid var(--te-line);
    border-radius: 999px;
    background: var(--te-surface);
    font-size: .82rem;
    cursor: pointer;
    transition: border-color .15s ease, background-color .15s ease, color .15s ease;
  }
  .tipos-chips input[type="checkbox"] { margin: 0; accent-color: var(--te-accent); }
  .tipos-chips label:has(input:checked) {
    border-color: var(--te-accent);
    background: color-mix(in srgb, var(--te-accent) 12%, var(--te-surface));
    color: var(--te-accent);
    font-weight: 600;
  }
  .tipos-chips label:has(input:focus-visible) {
    outline: 2px solid var(--ring, var(--te-accent));
    outline-offset: 2px;
  }
  @media (hover: hover) {
    .tipos-chips label:hover { border-color: var(--te-accent); }
  }

  /* ---------- Tabla (contenido inyectado por AJAX) ---------- */
  #tablaGestionTipos .card-content { overflow-x: auto; }
  #tablaGestionTipos > .card-content > p { padding: 24px 8px; text-align: center; color: var(--te-muted); }

  #tablaGestionTipos table.dataTable { width: 100%; border-collapse: collapse; }
  #tablaGestionTipos table.dataTable thead th {
    padding-block: 10px;
    padding-inline-start: 12px;
    border-bottom: 1px solid var(--te-line);
    background: var(--muted, var(--te-surface));
    font-size: .74rem;
    font-weight: 600;
    text-align: start;
    white-space: nowrap;
    color: var(--te-muted);
  }
  #tablaGestionTipos table.dataTable tbody td {
    padding: 10px 12px;
    border: 0;
    border-bottom: 1px solid var(--te-line);
    box-shadow: none;
    font-size: .85rem;
    line-height: 1.4;
    vertical-align: middle;
    overflow-wrap: anywhere;
  }
  #tablaGestionTipos table.dataTable tbody tr:last-child td { border-bottom: 0; }
  #tablaGestionTipos table.dataTable tbody td.dt-empty {
    padding: 36px 12px;
    text-align: center;
    color: var(--te-muted);
  }
  #tablaGestionTipos table.dataTable tbody td.dtr-control { width: 40px; }
  #tablaGestionTipos table.dataTable tbody td .btn {
    min-height: 32px;
    margin: 2px 4px 2px 0;
    padding: 4px 10px;
    font-size: .78rem;
    white-space: nowrap;
  }
  @media (hover: hover) {
    #tablaGestionTipos table.dataTable tbody tr:hover > td {
      background: color-mix(in srgb, var(--te-accent) 5%, transparent);
    }
  }

  /* Filas expandidas (Responsive): lista de etiqueta y valor */
  #tablaGestionTipos table.dataTable tbody tr.child td.child { padding: 0 12px 8px 12px; }
  #tablaGestionTipos ul.dtr-details { display: grid; margin: 0; padding: 0; list-style: none; }
  #tablaGestionTipos ul.dtr-details > li {
    display: grid;
    grid-template-columns: minmax(90px, 34%) minmax(0, 1fr);
    gap: 4px 12px;
    padding: 9px 0;
    border-top: 1px solid var(--te-line);
  }
  #tablaGestionTipos ul.dtr-details > li:first-child { border-top: 0; }
  #tablaGestionTipos ul.dtr-details .dtr-title {
    min-width: 0;
    font-size: .74rem;
    font-weight: 600;
    color: var(--te-muted);
  }
  #tablaGestionTipos ul.dtr-details .dtr-data { min-width: 0; font-size: .85rem; overflow-wrap: anywhere; }

  /* Mostrar, información y paginación */
  #tablaGestionTipos .dt-layout-row:not(.dt-layout-table),
  #tablaGestionTipos .dt-bottom {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px 16px;
    margin-top: 14px;
    font-size: .82rem;
    color: var(--te-muted);
  }
  #tablaGestionTipos .dt-length { display: flex; align-items: center; gap: 8px; }
  #tablaGestionTipos .dt-length select {
    min-height: 36px;
    padding: 4px 8px;
    border: 1px solid var(--te-line);
    border-radius: var(--te-radius-sm);
    background-color: var(--te-surface);
    color: inherit;
  }
  #tablaGestionTipos .dt-paging nav { display: flex; flex-wrap: wrap; gap: 4px; justify-content: center; }
  #tablaGestionTipos .dt-paging .dt-paging-button {
    min-width: 36px;
    min-height: 36px;
    margin: 0;
    padding: 4px 10px;
    border: 1px solid var(--te-line);
    border-radius: var(--te-radius-sm);
    background: var(--te-surface);
    font-size: .8rem;
    color: inherit;
    transition: border-color .15s ease, background-color .15s ease;
  }
  #tablaGestionTipos .dt-paging .dt-paging-button.current,
  #tablaGestionTipos .dt-paging .dt-paging-button.current:hover {
    border-color: var(--te-accent);
    background: color-mix(in srgb, var(--te-accent) 12%, var(--te-surface));
    color: var(--te-accent);
    font-weight: 600;
  }
  #tablaGestionTipos .dt-paging .dt-paging-button.disabled,
  #tablaGestionTipos .dt-paging .dt-paging-button.disabled:hover {
    opacity: .45;
    cursor: not-allowed;
    background: var(--te-surface);
  }
  @media (hover: hover) {
    #tablaGestionTipos .dt-paging .dt-paging-button:not(.current):not(.disabled):hover { border-color: var(--te-accent); }
  }

  /* ---------- Diálogo de eliminar ---------- */
  #dlgEliminarTE .dialog-card { width: min(100%, 520px); max-height: calc(100dvh - 24px); overflow: auto; }
  #dlgEliminarTE .dialog-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px; }
  /* La acción es irreversible: el botón de confirmar se marca como destructivo */
  #dlgEliminarTE .dialog-actions .btn-primary {
    border-color: var(--destructive, #dc2626);
    background: var(--destructive, #dc2626);
    color: var(--destructive-foreground, #ffffff);
  }
  @media (max-width: 479px) {
    #dlgEliminarTE .dialog-actions .btn { flex: 1 1 100%; min-height: 44px; }
  }

  /* ---------- Accesibilidad ---------- */
  .tipos-page :is(a, button, .btn, select, input, [tabindex]):focus-visible {
    outline: 2px solid var(--ring, var(--te-accent));
    outline-offset: 2px;
  }
  @media (pointer: coarse) {
    .tipos-page .btn,
    .tipos-page .dt-paging .dt-paging-button,
    .tipos-chips label { min-height: 44px; }
    #tablaGestionTipos table.dataTable tbody td .btn { min-height: 40px; }
  }
  @media (prefers-reduced-motion: reduce) {
    .tipos-page * { transition: none !important; }
  }

  /* =========================================================================
     Puntos de quiebre según el ancho del contenedor
     ========================================================================= */

  /* Móvil: botón principal a ancho completo, pie de tabla centrado y apilado */
  @container tipos (max-width: 559px) {
    .tipos-page .dashboard-hero > .btn { flex: 1 1 100%; }
    #tablaGestionTipos .dt-layout-row:not(.dt-layout-table),
    #tablaGestionTipos .dt-bottom { flex-direction: column; align-items: center; text-align: center; }
    #tablaGestionTipos .dt-paging .dt-paging-button.first,
    #tablaGestionTipos .dt-paging .dt-paging-button.last { display: none; }
  }

  /* Escritorio: búsqueda a la izquierda y filtros a la derecha */
  @container tipos (min-width: 900px) {
    .tipos-page .tipos-toolbar .card-content { grid-template-columns: minmax(280px, 420px) minmax(0, 1fr); }
  }
</style>

<section class="tipos-page" aria-labelledby="tipos-titulo">
  <header class="dashboard-hero">
    <div>
      <p class="dashboard-eyebrow">ODEA · Administración</p>
      <h1 id="tipos-titulo">Tipos de evidencia</h1>
      <p class="text-muted-foreground">Administra los tipos y sus descripciones.</p>
    </div>

    <button class="btn btn-primary" id="openModalAgregarTipo" type="button" title="Agregar nuevo tipo">
      + Agregar Tipo
    </button>
  </header>

  <!-- Barra de herramientas -->
  <div class="card tipos-toolbar">
    <div class="card-content">
      <!-- Búsqueda externa -->
      <div class="tipos-field">
        <label for="search">Buscar</label>
        <div class="tipos-search-row">
          <input id="search" class="input" placeholder="Buscar por nombre o descripción…" autocomplete="off" />
          <button id="clearSearch" class="btn" type="button" title="Limpiar búsqueda">Limpiar</button>
        </div>
      </div>

      <!-- Filtros por instrumento -->
      <?php if ($instrumentos): ?>
      <div class="tipos-field">
        <span class="tipos-label" id="instFiltersLabel">Instrumentos</span>
        <p class="tipos-hint">Muestra solo los tipos que incluyen todos los instrumentos marcados.</p>
        <div id="instFilters" class="tipos-chips" role="group" aria-labelledby="instFiltersLabel">
          <?php foreach ($instrumentos as $filtroInstrumento): ?>
            <label><input type="checkbox" class="filter-instrumento" value="<?= (int)$filtroInstrumento['id_instrumento'] ?>"> <?= htmlspecialchars($filtroInstrumento['abreviatura']) ?></label>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Tabla -->
  <div id="tablaGestionTipos" class="card" aria-live="polite">
    <div class="card-content">
      <p class="text-muted-foreground">Cargando…</p>
    </div>
  </div>
</section>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- jQuery + DataTables + Responsive -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link  href="https://cdn.datatables.net/v/dt/dt-2.1.4/r-3.0.2/datatables.min.css" rel="stylesheet"/>
<script src="https://cdn.datatables.net/v/dt/dt-2.1.4/r-3.0.2/datatables.min.js"></script>

<!-- Modal Eliminar -->
<div class="dialog-backdrop" id="dlgBackdropTE"></div>
<div class="dialog" id="dlgEliminarTE" role="dialog" aria-modal="true" aria-labelledby="dlgEliminarTETitle" data-open="false">
  <div class="dialog-card" style="max-width:520px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgEliminarTETitle">Eliminar tipo de evidencia</h3>
      <p class="dialog-desc">Esta acción no se puede deshacer.</p>
    </div>
    <div class="dialog-body">
      <p id="dlgEliminarTEText">¿Estás seguro de eliminar este tipo?</p>
    </div>
    <div class="dialog-actions">
      <button class="btn" type="button" onclick="closeEliminarTE()">Cancelar</button>
      <button class="btn btn-primary" type="button" onclick="confirmEliminarTE()">Eliminar</button>
    </div>
  </div>
</div>

<?php
// Modales
$modal_agregar_tipo = COMPONENTS_DIR . '/modals/modal-agregar-tipo-evidencia.php';
$modal_editar_tipo  = COMPONENTS_DIR . '/modals/modal-editar-tipo-evidencia.php';

if (file_exists($modal_agregar_tipo)) include $modal_agregar_tipo;
if (file_exists($modal_editar_tipo)) include $modal_editar_tipo;
?>
<script>
/* ========= Utilidades ========= */
window.lockPageScroll = function(lock){
  const html = document.documentElement;
  html.style.overflow = lock ? 'hidden' : '';
};

/* ========= DataTable / Carga ========= */
const SEARCH_KEY = 'gestionTiposEvidenciaSearch';
const FILTERS_KEY = 'gestionTiposEvidenciaFilters'; // NUEVO (persistir filtros)
let dtTE = null;

// Filtro DataTables (instalado una sola vez)
if (!window.__teFilterInstalled) {
  $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
    // Aplica SOLO a esta tabla
    if (!dtTE || settings.nTable !== dtTE.table().node()) return true;

    const node = dtTE.row(dataIndex).node();
    if (!node) return true;

    const ids = (node.dataset.instrumentos || '').split('|');
    if (![...document.querySelectorAll('.filter-instrumento:checked')].every(el => ids.includes(el.value))) return false;
    return true;
  });
  window.__teFilterInstalled = true;
}

// Carga inicial
loadTableTE();

function loadTableTE(){
  const url = '<?= BASE_URL ?>/views/admin/vista-tipos-evidencia.php?r=' + Date.now();
  const xhr = new XMLHttpRequest();
  xhr.onreadystatechange = function(){
    if (xhr.readyState === 4) {
      const cont = document.getElementById('tablaGestionTipos');
      cont.innerHTML = xhr.status === 200 ? xhr.responseText
                                          : '<div class="card-content"><p>Error al cargar la tabla.</p></div>';
      initDTTE(); // inicializa/rehace DataTable
    }
  };
  xhr.open('GET', url, true);
  xhr.send();
}

function initDTTE(){
  const $table = $('#tabla-tipos-evidencia');
  if (!$table.length) return;

  if (dtTE) { dtTE.destroy(); dtTE = null; }

  dtTE = $table.DataTable({
    responsive: { details: { type: 'column', target: 0 } },
    columnDefs: [
      { className: 'control', orderable: false, targets: 0 },
      { orderable: false, targets: 5 } // Acciones (última)
    ],
    paging: true,
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    stateSave: true,
    order: [[1, 'asc']],
    dom: "<'dt-top'>t<'dt-bottom' l i p>",
    language: {
      info: "Mostrando _START_ a _END_ de _TOTAL_ tipos",
      infoFiltered: "(filtrado de _MAX_ tipos)",
      emptyTable: "No hay información",
      zeroRecords: "No se encontró coincidencia",
      lengthMenu: "Mostrar _MENU_",
      paginate: { first:"Primero", last:"Último", next:"Siguiente", previous:"Anterior" }
    }
  });

  // === Búsqueda externa (ya existente) ===
  const $search = $('#search');
  const $clear  = $('#clearSearch');

  if ($search.length) {
    const saved = dtTE.state()?.search?.search || localStorage.getItem(SEARCH_KEY) || '';
    $search.val(saved);
    if (saved) dtTE.search(saved).draw();
    $search.off('input.te').on('input.te', function(){
      const val = this.value;
      dtTE.search(val).draw();
      localStorage.setItem(SEARCH_KEY, val);
    });
  }
  if ($clear.length) {
    $clear.off('click.te').on('click.te', function(){
      if ($search.length) {
        $search.val('');
        localStorage.setItem(SEARCH_KEY, '');
      }
      dtTE.search('').draw();
    });
  }

  $table.off('click.teActions', 'button[data-te-action]').on('click.teActions', 'button[data-te-action]', function(){
    const id = this.dataset.id;
    switch (this.dataset.teAction) {
      case 'attributes': window.openAtributosTipo(id); break;
      case 'edit': window.openEditarTipo(id); break;
      case 'delete': window.openEliminarTE(id, this.dataset.name || ''); break;
    }
  });

  // === Filtros por instrumento (NUEVO) ===
  restoreInstrumentFilters();    // setea checkboxes según localStorage
  bindInstrumentFiltersEvents(); // escucha cambios y redibuja
  dtTE.draw(); // aplica filtros actuales
}

// ----- NUEVO: persistencia de filtros -----
function getFiltersState(){
  return [...document.querySelectorAll('.filter-instrumento:checked')].map(el => el.value);
}
function setFiltersState(st){
  document.querySelectorAll('.filter-instrumento').forEach(el => el.checked = Array.isArray(st) && st.includes(el.value));
}
function restoreInstrumentFilters(){
  try {
    const raw = localStorage.getItem(FILTERS_KEY);
    if (raw) setFiltersState(JSON.parse(raw));
  } catch(e){}
}
function saveInstrumentFilters(){
  try { localStorage.setItem(FILTERS_KEY, JSON.stringify(getFiltersState())); } catch(e){}
}
function bindInstrumentFiltersEvents(){
  document.querySelectorAll('.filter-instrumento').forEach(el=>{
    if (el) {
      el.onchange = function(){
        saveInstrumentFilters();
        if (dtTE) dtTE.draw();
      };
    }
  });
}

/* ========= Handlers ========= */

// Ir a atributos/lista del tipo
window.openAtributosTipo = function(id){
  window.location.href = '<?= BASE_URL ?>/views/admin/atributos-tipo.php?id_tipo=' + encodeURIComponent(id);
};

// Editar
window.openEditarTipo = function(id){
  if (typeof window.openEditarTipoModal === 'function') {
    window.openEditarTipoModal(id);
  } else {
    if (window.Swal) Swal.fire({icon:'info', title:'Editar', text:'Implementa el modal de edición para el tipo '+id+'.'});
  }
};

// Eliminar
let __deleteTipoId = null;
window.openEliminarTE = function(id, name){
  __deleteTipoId = id;
  const dlg = document.getElementById('dlgEliminarTE');
  const backdrop = document.getElementById('dlgBackdropTE');
  const txt = document.getElementById('dlgEliminarTEText');

  if (txt) {
    // Se arma con nodos de texto (no innerHTML) para que un nombre con HTML no se ejecute
    txt.textContent = '¿Estás seguro de eliminar ';
    const strong = document.createElement('strong');
    strong.textContent = name || ('tipo #' + id);
    txt.appendChild(strong);
    txt.appendChild(document.createTextNode('?'));
  }
  if (dlg && backdrop) {
    dlg.setAttribute('data-open','true');
    backdrop.setAttribute('data-open','true');
    lockPageScroll(true);
  }
};
window.closeEliminarTE = function(){
  __deleteTipoId = null;
  const dlg = document.getElementById('dlgEliminarTE');
  const backdrop = document.getElementById('dlgBackdropTE');
  if (dlg && backdrop) {
    dlg.setAttribute('data-open','false');
    backdrop.setAttribute('data-open','false');
    lockPageScroll(false);
  }
};
window.confirmEliminarTE = function(){
  if (!__deleteTipoId) return;
  fetch('<?= BASE_URL ?>/controllers/admin/tipo-evidencia-eliminar.php', {
    method: 'POST',
    headers: { 'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8' },
    body: new URLSearchParams({ id_tipo_evidencia: __deleteTipoId })
  })
  .then(r=>r.json())
  .then(j=>{
    closeEliminarTE();
    if (j && j.status === 'ok') {
      if (window.Swal) Swal.fire({icon:'success', title:'Eliminado', text:'El tipo se eliminó correctamente.'});
      loadTableTE();
    } else {
      const msg = (j && j.message) ? j.message : 'No se pudo eliminar.';
      if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
    }
  })
  .catch(()=>{
    closeEliminarTE();
    if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'No se pudo eliminar el tipo.'});
  });
};

// Cerrar modal eliminar tocando backdrop
(function(){
  const backdrop = document.getElementById('dlgBackdropTE');
  if (backdrop) backdrop.addEventListener('click', window.closeEliminarTE);
})();
</script>


<?php include __DIR__ . '/admin-ui-styles.php'; ?>
<?php include COMPONENTS_DIR . '/layout/footer.php'; ?>