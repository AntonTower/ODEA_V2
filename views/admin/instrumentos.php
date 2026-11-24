<?php
/**
 * Gestión de Instrumentos (Evaluación)
 * - Alta de instrumentos y edicion de la forma de calificar.
 * - Carga la tabla por AJAX desde vista-instrumentos.php
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
require_once CONFIG_DIR . '/csrf.php';
$csrfInstrumentos = csrf_token();

include COMPONENTS_DIR . '/layout/header.php';
include COMPONENTS_DIR . '/layout/left-menu.php';
?>
<style>
  /* =========================================================================
     Instrumentos
     - El layout responde al ancho del contenedor (container queries), por lo
       que se adapta solo cuando el menú lateral se abre o se colapsa.
     - La tabla la inyecta AJAX; sus estilos se anclan a #tablaInstrumentos y
       cubren DataTables 2 (dt-*) y su extensión Responsive (dtr-*).
     ========================================================================= */

  .instrumentos-page {
    --in-gap: clamp(12px, 1.6vw, 24px);
    --in-pad: clamp(14px, 1.5vw, 20px);
    --in-line: var(--border, #e4e4e7);
    --in-surface: var(--card, #ffffff);
    --in-muted: var(--muted-foreground, #71717a);
    --in-accent: var(--primary, #2563eb);
    --in-radius: var(--radius, 12px);
    --in-radius-sm: var(--radius-sm, 8px);
    --in-shadow: 0 1px 2px rgba(0, 0, 0, .05);

    display: grid;
    align-content: start;
    gap: var(--in-gap);
    width: 100%;
    max-width: 1680px;
    min-width: 0;
    margin-inline: auto;
    container-type: inline-size;
    container-name: instrumentos;
  }
  .instrumentos-page > * { min-width: 0; margin: 0; }

  /* ---------- Encabezado ---------- */
  .instrumentos-page .dashboard-hero { padding: 4px 0 2px; }
  .instrumentos-page .dashboard-hero h1 {
    margin: 0 0 4px;
    font-size: clamp(1.35rem, 1.1rem + 1vw, 1.85rem);
    line-height: 1.2;
    letter-spacing: -.02em;
    text-wrap: balance;
  }
  .instrumentos-page .dashboard-eyebrow {
    margin: 0 0 4px;
    font-size: .75rem;
    font-weight: 600;
    color: var(--in-accent);
  }
  .instrumentos-page .dashboard-hero .text-muted-foreground {
    max-width: 72ch;
    margin: 0;
    font-size: .9rem;
    line-height: 1.5;
    color: var(--in-muted);
  }

  /* ---------- Tarjetas ---------- */
  .instrumentos-page .card {
    display: flex;
    flex-direction: column;
    min-width: 0;
    border: 1px solid var(--in-line);
    border-radius: var(--in-radius);
    background: var(--in-surface);
    box-shadow: var(--in-shadow);
  }
  .instrumentos-page .card-content {
    flex: 1 1 auto;
    min-width: 0;
    padding: var(--in-pad);
  }

  /* ---------- Alta de instrumento (desplegable) ---------- */
  .instrumentos-page details.card { display: block; }
  .instrumentos-page details.card > summary.card-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    min-height: 52px;
    font-weight: 650;
    cursor: pointer;
    list-style: none;
    user-select: none;
  }
  .instrumentos-page details.card > summary::-webkit-details-marker { display: none; }
  .instrumentos-page details.card > summary::after {
    content: "";
    flex: none;
    width: 8px;
    height: 8px;
    border-right: 2px solid currentColor;
    border-bottom: 2px solid currentColor;
    transform: rotate(45deg);
    transition: transform .2s ease;
    color: var(--in-muted);
  }
  .instrumentos-page details.card[open] > summary::after { transform: rotate(-135deg); }
  .instrumentos-page details.card[open] > summary { border-bottom: 1px solid var(--in-line); }

  .instrumentos-page #formNuevoInstrumento {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 14px 16px;
    align-items: start;
  }
  .instr-field { display: flex; flex-direction: column; gap: 6px; min-width: 0; font-size: .8rem; font-weight: 600; }
  .instr-field .input { width: 100%; min-height: 42px; font-weight: 400; }
  .instr-hint { grid-column: 1 / -1; margin: 0; font-size: .8rem; line-height: 1.5; color: var(--in-muted); }
  .instr-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    grid-column: 1 / -1;
    gap: 10px 14px;
  }
  .instr-actions .btn { min-height: 42px; }
  #resultadoNuevoInstrumento { margin: 0; font-size: .85rem; line-height: 1.4; }
  #resultadoNuevoInstrumento:empty { display: none; }
  #resultadoNuevoInstrumento[data-state="ok"] { color: var(--success, #16a34a); }
  #resultadoNuevoInstrumento[data-state="error"] { color: var(--destructive, #dc2626); }

  /* ---------- Barra de herramientas ---------- */
  .instrumentos-page .instrumentos-toolbar .card-content {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-items: start;
    gap: 16px 24px;
  }
  .instrumentos-field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
  .instrumentos-field > label,
  .instrumentos-field > .instrumentos-label { font-size: .8rem; font-weight: 600; }
  .instrumentos-hint { margin: 0; font-size: .75rem; line-height: 1.4; color: var(--in-muted); }
  .instrumentos-search-row { display: flex; align-items: center; gap: 8px; }
  .instrumentos-search-row .input { flex: 1 1 auto; min-width: 0; }
  .instrumentos-search-row .btn { flex: none; }
  .instrumentos-field .input,
  .instrumentos-search-row .btn { min-height: 42px; }

  /* Filtros por tipo de calificación: chips marcables */
  .instrumentos-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 4px; }
  .instrumentos-chips label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 36px;
    padding: 6px 12px;
    border: 1px solid var(--in-line);
    border-radius: 999px;
    background: var(--in-surface);
    font-size: .82rem;
    cursor: pointer;
    transition: border-color .15s ease, background-color .15s ease, color .15s ease;
  }
  .instrumentos-chips input[type="checkbox"] { margin: 0; accent-color: var(--in-accent); }
  .instrumentos-chips label:has(input:checked) {
    border-color: var(--in-accent);
    background: color-mix(in srgb, var(--in-accent) 12%, var(--in-surface));
    color: var(--in-accent);
    font-weight: 600;
  }
  .instrumentos-chips label:has(input:focus-visible) {
    outline: 2px solid var(--ring, var(--in-accent));
    outline-offset: 2px;
  }
  @media (hover: hover) {
    .instrumentos-chips label:hover { border-color: var(--in-accent); }
  }

  /* ---------- Tabla (contenido inyectado por AJAX) ---------- */
  #tablaInstrumentos .card-content { overflow-x: auto; }
  #tablaInstrumentos > .card-content > p { padding: 24px 8px; text-align: center; color: var(--in-muted); }

  #tablaInstrumentos table.dataTable { width: 100%; border-collapse: collapse; }
  #tablaInstrumentos table.dataTable thead th {
    padding-block: 10px;
    padding-inline-start: 12px;
    border-bottom: 1px solid var(--in-line);
    background: var(--muted, var(--in-surface));
    font-size: .74rem;
    font-weight: 600;
    text-align: start;
    white-space: nowrap;
    color: var(--in-muted);
  }
  #tablaInstrumentos table.dataTable tbody td {
    padding: 10px 12px;
    border: 0;
    border-bottom: 1px solid var(--in-line);
    box-shadow: none;
    font-size: .85rem;
    line-height: 1.4;
    vertical-align: middle;
    overflow-wrap: anywhere;
  }
  #tablaInstrumentos table.dataTable tbody tr:last-child td { border-bottom: 0; }
  #tablaInstrumentos table.dataTable tbody td.dt-empty {
    padding: 36px 12px;
    text-align: center;
    color: var(--in-muted);
  }
  #tablaInstrumentos table.dataTable tbody td.dtr-control { width: 40px; }
  #tablaInstrumentos table.dataTable tbody td .btn {
    min-height: 32px;
    margin: 2px 4px 2px 0;
    padding: 4px 10px;
    font-size: .78rem;
    white-space: nowrap;
  }
  @media (hover: hover) {
    #tablaInstrumentos table.dataTable tbody tr:hover > td {
      background: color-mix(in srgb, var(--in-accent) 5%, transparent);
    }
  }

  /* Filas expandidas (Responsive): lista de etiqueta y valor */
  #tablaInstrumentos table.dataTable tbody tr.child td.child { padding: 0 12px 8px 12px; }
  #tablaInstrumentos ul.dtr-details { display: grid; margin: 0; padding: 0; list-style: none; }
  #tablaInstrumentos ul.dtr-details > li {
    display: grid;
    grid-template-columns: minmax(90px, 34%) minmax(0, 1fr);
    gap: 4px 12px;
    padding: 9px 0;
    border-top: 1px solid var(--in-line);
  }
  #tablaInstrumentos ul.dtr-details > li:first-child { border-top: 0; }
  #tablaInstrumentos ul.dtr-details .dtr-title {
    min-width: 0;
    font-size: .74rem;
    font-weight: 600;
    color: var(--in-muted);
  }
  #tablaInstrumentos ul.dtr-details .dtr-data { min-width: 0; font-size: .85rem; overflow-wrap: anywhere; }

  /* Mostrar, información y paginación */
  #tablaInstrumentos .dt-layout-row:not(.dt-layout-table),
  #tablaInstrumentos .dt-bottom {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px 16px;
    margin-top: 14px;
    font-size: .82rem;
    color: var(--in-muted);
  }
  #tablaInstrumentos .dt-length { display: flex; align-items: center; gap: 8px; }
  #tablaInstrumentos .dt-length select {
    min-height: 36px;
    padding: 4px 8px;
    border: 1px solid var(--in-line);
    border-radius: var(--in-radius-sm);
    background-color: var(--in-surface);
    color: inherit;
  }
  #tablaInstrumentos .dt-paging nav { display: flex; flex-wrap: wrap; gap: 4px; justify-content: center; }
  #tablaInstrumentos .dt-paging .dt-paging-button {
    min-width: 36px;
    min-height: 36px;
    margin: 0;
    padding: 4px 10px;
    border: 1px solid var(--in-line);
    border-radius: var(--in-radius-sm);
    background: var(--in-surface);
    font-size: .8rem;
    color: inherit;
    transition: border-color .15s ease, background-color .15s ease;
  }
  #tablaInstrumentos .dt-paging .dt-paging-button.current,
  #tablaInstrumentos .dt-paging .dt-paging-button.current:hover {
    border-color: var(--in-accent);
    background: color-mix(in srgb, var(--in-accent) 12%, var(--in-surface));
    color: var(--in-accent);
    font-weight: 600;
  }
  #tablaInstrumentos .dt-paging .dt-paging-button.disabled,
  #tablaInstrumentos .dt-paging .dt-paging-button.disabled:hover {
    opacity: .45;
    cursor: not-allowed;
    background: var(--in-surface);
  }
  @media (hover: hover) {
    #tablaInstrumentos .dt-paging .dt-paging-button:not(.current):not(.disabled):hover { border-color: var(--in-accent); }
  }

  /* ---------- Accesibilidad ---------- */
  .instrumentos-page :is(a, button, .btn, select, input, summary, [tabindex]):focus-visible {
    outline: 2px solid var(--ring, var(--in-accent));
    outline-offset: 2px;
  }
  @media (pointer: coarse) {
    .instrumentos-page .btn,
    .instrumentos-page .dt-paging .dt-paging-button,
    .instrumentos-chips label { min-height: 44px; }
    #tablaInstrumentos table.dataTable tbody td .btn { min-height: 40px; }
  }
  @media (prefers-reduced-motion: reduce) {
    .instrumentos-page * { transition: none !important; }
  }

  /* =========================================================================
     Puntos de quiebre según el ancho del contenedor
     ========================================================================= */

  /* Móvil: botón de alta a ancho completo, pie de tabla centrado y apilado */
  @container instrumentos (max-width: 559px) {
    .instr-actions .btn { flex: 1 1 100%; }
    #tablaInstrumentos .dt-layout-row:not(.dt-layout-table),
    #tablaInstrumentos .dt-bottom { flex-direction: column; align-items: center; text-align: center; }
    #tablaInstrumentos .dt-paging .dt-paging-button.first,
    #tablaInstrumentos .dt-paging .dt-paging-button.last { display: none; }
  }

  /* Tableta: abreviatura y nombre en la misma fila */
  @container instrumentos (min-width: 680px) {
    .instrumentos-page #formNuevoInstrumento { grid-template-columns: minmax(160px, 240px) minmax(0, 1fr); }
  }

  /* Escritorio: búsqueda a la izquierda y filtros a la derecha */
  @container instrumentos (min-width: 900px) {
    .instrumentos-page .instrumentos-toolbar .card-content { grid-template-columns: minmax(280px, 420px) minmax(0, 1fr); }
  }
</style>

<section class="instrumentos-page" aria-labelledby="instrumentos-titulo">
  <header class="dashboard-hero">
    <p class="dashboard-eyebrow">ODEA · Administración</p>
    <h1 id="instrumentos-titulo">Evaluación → Instrumentos</h1>
    <p class="text-muted-foreground">Desactivar impide nuevas evaluaciones y conserva el historial. Eliminar por completo solo se permite cuando no hay registros académicos relacionados.</p>
  </header>

  <details class="card">
    <summary class="card-content">Agregar instrumento</summary>
    <form id="formNuevoInstrumento" class="card-content">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfInstrumentos, ENT_QUOTES, 'UTF-8') ?>">
      <label class="instr-field">Abreviatura <input class="input" name="abreviatura" maxlength="20" required autocomplete="off"></label>
      <label class="instr-field">Nombre completo <input class="input" name="nombre_completo" maxlength="150" required autocomplete="off"></label>
      <p class="instr-hint">Se crea con calificacion por aprobacion. Despues puedes editar el rango y asignarlo a tipos de evidencia y evaluadores.</p>
      <div class="instr-actions">
        <button class="btn btn-primary" type="submit">Crear instrumento</button>
        <p id="resultadoNuevoInstrumento" role="status"></p>
      </div>
    </form>
  </details>

  <!-- Barra de herramientas -->
  <div class="card instrumentos-toolbar">
    <div class="card-content">
      <div class="instrumentos-field">
        <label for="ins_search">Buscar</label>
        <div class="instrumentos-search-row">
          <input id="ins_search" class="input" placeholder="Buscar por abreviatura o nombre…" autocomplete="off" />
          <button id="ins_clearSearch" class="btn" type="button" title="Limpiar búsqueda">Limpiar</button>
        </div>
      </div>

      <!-- Filtro rápido por tipo -->
      <div class="instrumentos-field">
        <span class="instrumentos-label" id="insFiltersLabel">Tipo de calificación</span>
        <p class="instrumentos-hint">Sin marcar, o con ambos marcados, se muestran todos los instrumentos.</p>
        <div class="instrumentos-chips" role="group" aria-labelledby="insFiltersLabel">
          <label><input type="checkbox" id="flt_aprobacion"> <span>Aprobación (0/1)</span></label>
          <label><input type="checkbox" id="flt_numerica"> <span>Numérica</span></label>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div id="tablaInstrumentos" class="card" aria-live="polite">
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

<?php
// Modal edición
$modal_editar_inst = COMPONENTS_DIR . '/modals/modal-editar-instrumento.php';
if (file_exists($modal_editar_inst)) {
    include $modal_editar_inst;
}
?>

<script>
document.getElementById('formNuevoInstrumento').addEventListener('submit', async function(event) {
  event.preventDefault();
  const boton = this.querySelector('button');
  const resultado = document.getElementById('resultadoNuevoInstrumento');
  boton.disabled = true;
  try {
    const respuesta = await fetch('<?= BASE_URL ?>/controllers/admin/instrumento-insert.php', {method:'POST', body:new FormData(this)});
    const datos = await respuesta.json();
    resultado.textContent = datos.message;
    resultado.dataset.state = datos.status === 'ok' ? 'ok' : 'error';
    if (datos.status === 'ok') { this.reset(); loadTableINS(); }
  } catch (error) {
    resultado.textContent = 'No se pudo crear el instrumento.';
    resultado.dataset.state = 'error';
  } finally { boton.disabled = false; }
});
/* ========= Utilidades ========= */
window.lockPageScroll = function(lock){
  document.documentElement.style.overflow = lock ? 'hidden' : '';
};

/* ========= DataTable / Carga ========= */
const INS_SEARCH_KEY  = 'gestionInstrumentosSearch';
const INS_FILTERS_KEY = 'gestionInstrumentosFilters';

let dtINS = null;

// Filtro DataTables (instalado una sola vez)
if (!window.__insFilterInstalled) {
  $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
    if (!dtINS || settings.nTable !== dtINS.table().node()) return true;

    const node = dtINS.row(dataIndex).node();
    if (!node) return true;

    const wantAp = $('#flt_aprobacion').prop('checked');
    const wantNum = $('#flt_numerica').prop('checked');

    const tipo = node.getAttribute('data-tipo') || ''; // 'APROBACION' | 'NUMERICA'

    // Con ambos marcados (o ninguno) pasa todo; con uno solo, se filtra por ese tipo.
    if (wantAp && !wantNum && tipo !== 'APROBACION') return false;
    if (wantNum && !wantAp && tipo !== 'NUMERICA') return false;
    return true;
  });
  window.__insFilterInstalled = true;
}

// Carga inicial
loadTableINS();

function loadTableINS(){
  const url = '<?= BASE_URL ?>/views/admin/vista-instrumentos.php?r=' + Date.now();
  const xhr = new XMLHttpRequest();
  xhr.onreadystatechange = function(){
    if (xhr.readyState === 4) {
      const cont = document.getElementById('tablaInstrumentos');
      cont.innerHTML = xhr.status === 200 ? xhr.responseText
                                          : '<div class="card-content"><p>Error al cargar la tabla.</p></div>';
      initDTINS();
    }
  };
  xhr.open('GET', url, true);
  xhr.send();
}

function initDTINS(){
  const $table = $('#tabla-instrumentos');
  if (!$table.length) return;

  if (dtINS) { dtINS.destroy(); dtINS = null; }

  dtINS = $table.DataTable({
    responsive: { details: { type: 'column', target: 0 } },
    columnDefs: [
      { className: 'control', orderable: false, targets: 0 },
      { orderable: false, targets: 7 } // Acciones
    ],
    paging: true,
    pageLength: 10,
    lengthMenu: [10, 25, 50],
    stateSave: true,
    order: [[1, 'asc']],
    dom: "<'dt-top'>t<'dt-bottom' l i p>",
    language: {
      info: "Mostrando _START_ a _END_ de _TOTAL_ instrumentos",
      infoFiltered: "(filtrado de _MAX_ instrumentos)",
      emptyTable: "No hay información",
      zeroRecords: "No se encontró coincidencia",
      lengthMenu: "Mostrar _MENU_",
      paginate: { first:"Primero", last:"Último", next:"Siguiente", previous:"Anterior" }
    }
  });

  // Búsqueda externa
  const $search = $('#ins_search');
  const $clear  = $('#ins_clearSearch');

  if ($search.length) {
    const saved = dtINS.state()?.search?.search || localStorage.getItem(INS_SEARCH_KEY) || '';
    $search.val(saved);
    if (saved) dtINS.search(saved).draw();
    // off() evita acumular manejadores cada vez que se recarga la tabla
    $search.off('input.ins').on('input.ins', function(){
      const val = this.value;
      dtINS.search(val).draw();
      localStorage.setItem(INS_SEARCH_KEY, val);
    });
  }
  if ($clear.length) {
    $clear.off('click.ins').on('click.ins', function(){
      if ($search.length) { $search.val(''); localStorage.setItem(INS_SEARCH_KEY, ''); }
      dtINS.search('').draw();
    });
  }

  // Filtros por tipo
  restoreInsFilters();
  ['#flt_aprobacion','#flt_numerica'].forEach(sel=>{
    const el = document.querySelector(sel);
    if (el) el.onchange = function(){
      saveInsFilters();
      dtINS.draw();
    };
  });
  dtINS.draw();
}

function restoreInsFilters(){
  try{
    const raw = localStorage.getItem(INS_FILTERS_KEY);
    if (!raw) return;
    const st = JSON.parse(raw);
    const ap  = document.getElementById('flt_aprobacion');
    const num = document.getElementById('flt_numerica');
    if (ap && typeof st.aprobacion === 'boolean') ap.checked = st.aprobacion;
    if (num && typeof st.numerica === 'boolean')  num.checked = st.numerica;
  } catch(e){}
}
function saveInsFilters(){
  const st = {
    aprobacion: !!document.getElementById('flt_aprobacion')?.checked,
    numerica:   !!document.getElementById('flt_numerica')?.checked,
  };
  try{ localStorage.setItem(INS_FILTERS_KEY, JSON.stringify(st)); }catch(e){}
}

/* ========= Handlers ========= */
window.openEditarInstrumento = function(id){
  if (typeof window.openEditarInstrumentoModal === 'function') {
    window.openEditarInstrumentoModal(id);
  } else {
    if (window.Swal) Swal.fire({icon:'info', title:'Editar', text:'Implementa el modal de edición para el instrumento '+id+'.'});
  }
};

window.cambiarEstadoInstrumento = async function(id, isActive){
  const action = isActive ? 'desactivar' : 'activar';
  const verb = isActive ? 'dar de baja' : 'restaurar';
  let confirmed = false;
  if (window.Swal) {
    const result = await Swal.fire({
      icon: isActive ? 'warning' : 'question',
      title: isActive ? 'Eliminar instrumento' : 'Restaurar instrumento',
      text: isActive
        ? 'Se desactivará para nuevas evaluaciones. Sus relaciones y evaluaciones históricas se conservarán.'
        : 'Volverá a estar disponible y conservará sus relaciones existentes.',
      showCancelButton: true,
      confirmButtonText: isActive ? 'Dar de baja' : 'Restaurar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: isActive ? '#dc2626' : '#2563eb'
    });
    confirmed = result.isConfirmed;
  } else {
    confirmed = window.confirm(`¿Deseas ${verb} este instrumento?`);
  }
  if (!confirmed) return;

  const button = document.querySelector(`.instrument-state-button[onclick^="cambiarEstadoInstrumento(${Number(id)},"]`);
  if (button) button.disabled = true;
  try {
    const token = document.querySelector('#formNuevoInstrumento input[name="csrf_token"]')?.value || '';
    const response = await fetch('<?= BASE_URL ?>/controllers/admin/instrumento-eliminar.php', {
      method: 'POST',
      headers: { 'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8', 'X-CSRF-Token':token },
      body: new URLSearchParams({ id_instrumento:String(id), accion:action })
    });
    const data = await response.json();
    if (!response.ok || data.status !== 'ok') throw new Error(data.message || 'No se pudo cambiar el estado del instrumento.');
    if (window.Swal) await Swal.fire({icon:'success', title:isActive?'Instrumento dado de baja':'Instrumento restaurado', text:data.message});
    loadTableINS();
  } catch (error) {
    if (window.Swal) Swal.fire({icon:'error', title:'No se pudo completar la acción', text:error.message || 'Recarga la página e inténtalo nuevamente.'});
    else window.alert(error.message || 'No se pudo cambiar el estado del instrumento.');
  } finally {
    if (button) button.disabled = false;
  }
};

window.eliminarInstrumentoCompleto = async function(id){
  let confirmed = false;
  if (window.Swal) {
    const result = await Swal.fire({
      icon: 'warning',
      title: 'Eliminar por completo',
      text: 'Esta acción es irreversible. Solo se permitirá si el instrumento no tiene evaluaciones, convocatorias ni otros registros académicos relacionados.',
      showCancelButton: true,
      confirmButtonText: 'Eliminar definitivamente',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc2626'
    });
    confirmed = result.isConfirmed;
  } else {
    confirmed = window.confirm('¿Eliminar por completo este instrumento? La operación solo se permite si no tiene historial académico relacionado.');
  }
  if (!confirmed) return;

  const button = document.querySelector(`.instrument-hard-delete-button[onclick^="eliminarInstrumentoCompleto(${Number(id)}"]`);
  if (button) button.disabled = true;
  try {
    const token = document.querySelector('#formNuevoInstrumento input[name="csrf_token"]')?.value || '';
    const response = await fetch('<?= BASE_URL ?>/controllers/admin/instrumento-eliminar-definitivo.php', {
      method: 'POST',
      headers: { 'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8', 'X-CSRF-Token':token },
      body: new URLSearchParams({ id_instrumento:String(id) })
    });
    const data = await response.json();
    if (!response.ok || data.status !== 'ok') throw new Error(data.message || 'No se pudo eliminar el instrumento.');
    if (window.Swal) await Swal.fire({icon:'success', title:'Instrumento eliminado', text:data.message});
    loadTableINS();
  } catch (error) {
    if (window.Swal) Swal.fire({icon:'warning', title:'No se pudo eliminar por completo', text:error.message || 'Verifica las relaciones del instrumento e inténtalo nuevamente.'});
    else window.alert(error.message || 'No se pudo eliminar el instrumento.');
  } finally {
    if (button) button.disabled = false;
  }
};
</script>

<?php include __DIR__ . '/admin-ui-styles.php'; ?>
<?php include COMPONENTS_DIR . '/layout/footer.php'; ?>
