<?php
/**
 * Ventana de Evaluación de Evidencias
 * - Muestra SOLO evidencias con 100% de atributos capturados.
 * - Filtros: Estado (Pendiente/Completa/Todas), Instrumento, Búsqueda.
 * - Abre modal para ver atributos y evaluar por instrumento.
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

require_once __DIR__ . '/permisos-instrumentos.php';
$alcance = alcanceInstrumentos(usuarioActualInstrumentos($conn));

// Instrumentos para filtro
$ins = [];
$qIns = "SELECT id_instrumento, abreviatura FROM instrumentos i WHERE activo = 1 AND ($alcance) ORDER BY id_instrumento";
if ($rs = $conn->query($qIns)) { while ($r = $rs->fetch_assoc()) $ins[] = $r; $rs->free(); }
?>
<style>
  /* =========================================================================
     Evaluación de evidencias
     - El layout responde al ancho del contenedor (container queries), por lo
       que se adapta solo cuando el menú lateral se abre o se colapsa.
     - La tabla la inyecta AJAX; sus estilos se anclan a #wrapTablaEval y
       cubren DataTables 2 (dt-*) y su extensión Responsive (dtr-*).
     ========================================================================= */

  .evaluacion-page {
    --ea-gap: clamp(12px, 1.6vw, 24px);
    --ea-pad: clamp(14px, 1.5vw, 20px);
    --ea-line: var(--border, #e4e4e7);
    --ea-surface: var(--card, #ffffff);
    --ea-muted: var(--muted-foreground, #71717a);
    --ea-accent: var(--primary, #2563eb);
    --ea-ok: var(--success, #16a34a);
    --ea-bad: var(--destructive, #dc2626);
    --ea-radius: var(--radius, 12px);
    --ea-radius-sm: var(--radius-sm, 8px);
    --ea-shadow: 0 1px 2px rgba(0, 0, 0, .05);

    display: grid;
    align-content: start;
    gap: var(--ea-gap);
    width: 100%;
    max-width: 1680px;
    min-width: 0;
    margin-inline: auto;
    container-type: inline-size;
    container-name: evaluacion;
  }
  .evaluacion-page > * { min-width: 0; margin: 0; }

  /* ---------- Encabezado ---------- */
  .evaluacion-page .dashboard-hero { padding: 4px 0 2px; }
  .evaluacion-page .dashboard-hero h1 {
    margin: 0 0 4px;
    font-size: clamp(1.35rem, 1.1rem + 1vw, 1.85rem);
    line-height: 1.2;
    letter-spacing: -.02em;
    text-wrap: balance;
  }
  .evaluacion-page .dashboard-eyebrow {
    margin: 0 0 4px;
    font-size: .75rem;
    font-weight: 600;
    color: var(--ea-accent);
  }
  .evaluacion-page .dashboard-hero .text-muted-foreground {
    max-width: 72ch;
    margin: 0;
    font-size: .9rem;
    line-height: 1.5;
    color: var(--ea-muted);
  }

  /* ---------- Tarjetas ---------- */
  .evaluacion-page .card {
    display: flex;
    flex-direction: column;
    min-width: 0;
    border: 1px solid var(--ea-line);
    border-radius: var(--ea-radius);
    background: var(--ea-surface);
    box-shadow: var(--ea-shadow);
  }
  .evaluacion-page .card-content {
    flex: 1 1 auto;
    min-width: 0;
    padding: var(--ea-pad);
  }

  /* ---------- Barra de herramientas ---------- */
  .evaluacion-page .evaluacion-toolbar .card-content {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-items: end;
    gap: 14px 16px;
  }
  .evaluacion-field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
  .evaluacion-field > label,
  .evaluacion-field > .evaluacion-label { font-size: .8rem; font-weight: 600; }
  .evaluacion-search-row { display: flex; align-items: center; gap: 8px; }
  .evaluacion-search-row .input { flex: 1 1 auto; min-width: 0; }
  .evaluacion-search-row .btn { flex: none; }
  .evaluacion-field .input,
  .evaluacion-search-row .btn { min-height: 42px; }
  .evaluacion-field select.input { width: 100%; }

  /* Estado: control segmentado (el botón activo lleva .btn-primary) */
  .evaluacion-page .btn-group {
    display: flex;
    width: 100%;
    padding: 3px;
    border: 1px solid var(--ea-line);
    border-radius: var(--ea-radius-sm);
    background: color-mix(in srgb, var(--ea-muted) 7%, var(--ea-surface));
  }
  .evaluacion-page .btn-group .btn {
    flex: 1 1 0;
    min-width: 0;
    min-height: 36px;
    padding: 4px 10px;
    border-color: transparent;
    background: transparent;
    box-shadow: none;
    font-size: .82rem;
    white-space: nowrap;
  }
  .evaluacion-page .btn-group .btn.btn-primary {
    border-color: var(--ea-line);
    background: var(--ea-surface);
    color: var(--ea-accent);
    font-weight: 650;
    box-shadow: var(--ea-shadow);
  }

  /* ---------- Insignias y progreso (usadas por la tabla) ---------- */
  .badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--primary, #2563eb) 12%, var(--card, #ffffff));
    color: var(--primary, #2563eb);
    font-size: 12px;
    font-weight: 600;
    line-height: 1.5;
  }
  .badge-ok {
    background: color-mix(in srgb, var(--success, #16a34a) 14%, var(--card, #ffffff));
    color: var(--success, #16a34a);
  }
  .badge-warn {
    background: color-mix(in srgb, var(--destructive, #dc2626) 12%, var(--card, #ffffff));
    color: var(--destructive, #dc2626);
  }
  .progress {
    display: inline-block;
    width: 120px;
    max-width: 100%;
    height: 10px;
    border-radius: 999px;
    background: var(--muted, #eee);
    overflow: hidden;
    vertical-align: middle;
  }
  .progress-bar { height: 100%; background: var(--success, #10b981); }

  /* ---------- Tabla (contenido inyectado por AJAX) ---------- */
  #wrapTablaEval .card-content { overflow-x: auto; }
  #wrapTablaEval > .card-content > p { padding: 24px 8px; text-align: center; color: var(--ea-muted); }

  #wrapTablaEval table.dataTable { width: 100%; border-collapse: collapse; }
  #wrapTablaEval table.dataTable thead th {
    padding-block: 10px;
    padding-inline-start: 12px;
    border-bottom: 1px solid var(--ea-line);
    background: var(--muted, var(--ea-surface));
    font-size: .74rem;
    font-weight: 600;
    text-align: start;
    white-space: nowrap;
    color: var(--ea-muted);
  }
  #wrapTablaEval table.dataTable tbody td {
    padding: 10px 12px;
    border: 0;
    border-bottom: 1px solid var(--ea-line);
    box-shadow: none;
    font-size: .85rem;
    line-height: 1.4;
    vertical-align: middle;
    overflow-wrap: anywhere;
  }
  #wrapTablaEval table.dataTable tbody tr:last-child td { border-bottom: 0; }
  #wrapTablaEval table.dataTable tbody td.dt-empty {
    padding: 36px 12px;
    text-align: center;
    color: var(--ea-muted);
  }
  #wrapTablaEval table.dataTable tbody td.dtr-control { width: 40px; }
  #wrapTablaEval table.dataTable tbody td .btn {
    min-height: 32px;
    margin: 2px 4px 2px 0;
    padding: 4px 10px;
    font-size: .78rem;
    white-space: nowrap;
  }
  @media (hover: hover) {
    #wrapTablaEval table.dataTable tbody tr:hover > td {
      background: color-mix(in srgb, var(--ea-accent) 5%, transparent);
    }
  }

  /* Filas expandidas (Responsive): lista de etiqueta y valor */
  #wrapTablaEval table.dataTable tbody tr.child td.child { padding: 0 12px 8px 12px; }
  #wrapTablaEval ul.dtr-details { display: grid; margin: 0; padding: 0; list-style: none; }
  #wrapTablaEval ul.dtr-details > li {
    display: grid;
    grid-template-columns: minmax(90px, 34%) minmax(0, 1fr);
    gap: 4px 12px;
    padding: 9px 0;
    border-top: 1px solid var(--ea-line);
  }
  #wrapTablaEval ul.dtr-details > li:first-child { border-top: 0; }
  #wrapTablaEval ul.dtr-details .dtr-title {
    min-width: 0;
    font-size: .74rem;
    font-weight: 600;
    color: var(--ea-muted);
  }
  #wrapTablaEval ul.dtr-details .dtr-data { min-width: 0; font-size: .85rem; overflow-wrap: anywhere; }

  /* Mostrar, información y paginación */
  #wrapTablaEval .dt-layout-row:not(.dt-layout-table),
  #wrapTablaEval .dt-bottom {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px 16px;
    margin-top: 14px;
    font-size: .82rem;
    color: var(--ea-muted);
  }
  #wrapTablaEval .dt-length { display: flex; align-items: center; gap: 8px; }
  #wrapTablaEval .dt-length select {
    min-height: 36px;
    padding: 4px 8px;
    border: 1px solid var(--ea-line);
    border-radius: var(--ea-radius-sm);
    background-color: var(--ea-surface);
    color: inherit;
  }
  #wrapTablaEval .dt-paging nav { display: flex; flex-wrap: wrap; gap: 4px; justify-content: center; }
  #wrapTablaEval .dt-paging .dt-paging-button {
    min-width: 36px;
    min-height: 36px;
    margin: 0;
    padding: 4px 10px;
    border: 1px solid var(--ea-line);
    border-radius: var(--ea-radius-sm);
    background: var(--ea-surface);
    font-size: .8rem;
    color: inherit;
    transition: border-color .15s ease, background-color .15s ease;
  }
  #wrapTablaEval .dt-paging .dt-paging-button.current,
  #wrapTablaEval .dt-paging .dt-paging-button.current:hover {
    border-color: var(--ea-accent);
    background: color-mix(in srgb, var(--ea-accent) 12%, var(--ea-surface));
    color: var(--ea-accent);
    font-weight: 600;
  }
  #wrapTablaEval .dt-paging .dt-paging-button.disabled,
  #wrapTablaEval .dt-paging .dt-paging-button.disabled:hover {
    opacity: .45;
    cursor: not-allowed;
    background: var(--ea-surface);
  }
  @media (hover: hover) {
    #wrapTablaEval .dt-paging .dt-paging-button:not(.current):not(.disabled):hover { border-color: var(--ea-accent); }
  }

  /* ---------- Accesibilidad ---------- */
  .evaluacion-page :is(a, button, .btn, select, input, [tabindex]):focus-visible {
    outline: 2px solid var(--ring, var(--ea-accent));
    outline-offset: 2px;
  }
  @media (pointer: coarse) {
    .evaluacion-page .btn,
    .evaluacion-page .dt-paging .dt-paging-button { min-height: 44px; }
    #wrapTablaEval table.dataTable tbody td .btn { min-height: 40px; }
  }
  @media (prefers-reduced-motion: reduce) {
    .evaluacion-page * { transition: none !important; }
  }

  /* =========================================================================
     Puntos de quiebre según el ancho del contenedor
     ========================================================================= */

  /* Móvil: pie de tabla centrado y apilado */
  @container evaluacion (max-width: 559px) {
    #wrapTablaEval .dt-layout-row:not(.dt-layout-table),
    #wrapTablaEval .dt-bottom { flex-direction: column; align-items: center; text-align: center; }
    #wrapTablaEval .dt-paging .dt-paging-button.first,
    #wrapTablaEval .dt-paging .dt-paging-button.last { display: none; }
  }

  /* Tableta: búsqueda arriba; estado e instrumento en una segunda fila */
  @container evaluacion (min-width: 600px) {
    .evaluacion-page .evaluacion-toolbar .card-content { grid-template-columns: minmax(280px, 380px) minmax(0, 1fr); }
    .evaluacion-page .evaluacion-toolbar .evaluacion-search { grid-column: 1 / -1; }
  }

  /* Escritorio: búsqueda, estado e instrumento en una sola fila */
  @container evaluacion (min-width: 960px) {
    .evaluacion-page .evaluacion-toolbar .card-content { grid-template-columns: minmax(240px, 1fr) minmax(280px, 340px) minmax(180px, 260px); }
    .evaluacion-page .evaluacion-toolbar .evaluacion-search { grid-column: auto; }
  }
</style>

<section class="evaluacion-page" aria-labelledby="evaluacion-titulo">
  <header class="dashboard-hero">
    <p class="dashboard-eyebrow">ODEA · Evaluación</p>
    <h1 id="evaluacion-titulo">Evaluación de evidencias</h1>
    <p class="text-muted-foreground">Solo aparecen evidencias con sus campos obligatorios completos.</p>
  </header>

  <!-- Barra de herramientas -->
  <div class="card evaluacion-toolbar">
    <div class="card-content">
      <!-- Búsqueda -->
      <div class="evaluacion-field evaluacion-search">
        <label for="search">Buscar</label>
        <div class="evaluacion-search-row">
          <input id="search" class="input" placeholder="Buscar por título, docente o tipo…" autocomplete="off" />
          <button id="clearSearch" class="btn" type="button" title="Limpiar búsqueda">Limpiar</button>
        </div>
      </div>

      <!-- Estado -->
      <div class="evaluacion-field">
        <span class="evaluacion-label" id="estadoEvalLabel">Estado</span>
        <div class="btn-group" id="estadoEval" role="group" aria-labelledby="estadoEvalLabel">
          <button class="btn" type="button" data-est="todas"     id="btnEstTodas">Todas</button>
          <button class="btn" type="button" data-est="pendiente" id="btnEstPend">Pendientes</button>
          <button class="btn" type="button" data-est="completa"  id="btnEstComp">Completas</button>
        </div>
      </div>

      <!-- Instrumento -->
      <div class="evaluacion-field">
        <label for="filterInstrumento">Instrumento</label>
        <select id="filterInstrumento" class="input">
          <option value="">Instrumento: Todos</option>
          <?php foreach ($ins as $i): ?>
            <option value="<?= (int)$i['id_instrumento'] ?>"><?= htmlspecialchars($i['abreviatura']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div id="wrapTablaEval" class="card" aria-live="polite">
    <div class="card-content">
      <p class="text-muted-foreground">Cargando…</p>
    </div>
  </div>
</section>

<!-- Modal Evaluar -->
<?php 
$modal_eval = COMPONENTS_DIR . '/modals/modal-evaluar-evidencia.php';
if (file_exists($modal_eval)) {
    include $modal_eval;
}
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- jQuery + DataTables + Responsive -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link  href="https://cdn.datatables.net/v/dt/dt-2.1.4/r-3.0.2/datatables.min.css" rel="stylesheet"/>
<script src="https://cdn.datatables.net/v/dt/dt-2.1.4/r-3.0.2/datatables.min.js"></script>

<script>
const EVAL_SEARCH_KEY = 'evalSearch';
const EVAL_STATE_KEY  = 'evalState';
const EVAL_INST_KEY   = 'evalInst';
let dtEVAL = null;

function loadTablaEval(){
  const estado = localStorage.getItem(EVAL_STATE_KEY) || 'todas';
  const inst   = localStorage.getItem(EVAL_INST_KEY) || '';

  const url = '<?= BASE_URL ?>/views/evaluador/vista-evaluacion.php?r=' + Date.now()
            + '&estado=' + encodeURIComponent(estado)
            + '&instrumento=' + encodeURIComponent(inst);

  const xhr = new XMLHttpRequest();
  xhr.onreadystatechange = function(){
    if (xhr.readyState === 4) {
      const cont = document.getElementById('wrapTablaEval');
      cont.innerHTML = xhr.status === 200 ? xhr.responseText
                                          : '<div class="card-content"><p>Error al cargar la tabla.</p></div>';
      initDTEVAL();
    }
  };
  xhr.open('GET', url, true); xhr.send();
}

function initDTEVAL(){
  const $t = $('#tabla-evaluacion');
  if (!$t.length) return;
  if (dtEVAL) { dtEVAL.destroy(); dtEVAL = null; }

  dtEVAL = $t.DataTable({
    responsive: { details: { type:'column', target: 0 } },
    columnDefs: [
      { className:'control', orderable:false, targets:0 },
      { orderable:false, targets: 9 } // Acciones
    ],
    paging:true, pageLength:10, lengthMenu:[10,25,50,100],
    stateSave:true, order:[[1,'desc']],
    dom: "<'dt-top'>t<'dt-bottom' l i p>",
    language:{
      info:"Mostrando _START_ a _END_ de _TOTAL_ evidencias",
      infoFiltered:"(filtrado de _MAX_ evidencias)",
      emptyTable:"No hay información",
      zeroRecords:"No se encontró coincidencia",
      lengthMenu:"Mostrar _MENU_",
      paginate:{ first:"Primero", last:"Último", next:"Siguiente", previous:"Anterior" }
    }
  });

  // Búsqueda externa
  const $s = $('#search'), $c = $('#clearSearch');
  if ($s.length) {
    const saved = dtEVAL.state()?.search?.search || localStorage.getItem(EVAL_SEARCH_KEY) || '';
    $s.val(saved); if (saved) dtEVAL.search(saved).draw();
    // off() evita acumular manejadores cada vez que se recarga la tabla
    $s.off('input.eval').on('input.eval', function(){
      const v = this.value || '';
      dtEVAL.search(v).draw(); localStorage.setItem(EVAL_SEARCH_KEY, v);
    });
  }
  if ($c.length) {
    $c.off('click.eval').on('click.eval', function(){
      if ($s.length) { $s.val(''); localStorage.setItem(EVAL_SEARCH_KEY,''); }
      dtEVAL.search('').draw();
    });
  }
}

// Filtros (persistencia)
(function(){
  // Estado
  const botonesEstado = document.querySelectorAll('#estadoEval .btn');
  function marcarEstado(activo){
    botonesEstado.forEach(x=>{
      const on = (x === activo);
      x.classList.toggle('btn-primary', on);
      x.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
  }

  const initState = localStorage.getItem(EVAL_STATE_KEY) || 'todas';
  marcarEstado(
    document.querySelector('#estadoEval .btn[data-est="' + initState + '"]')
    || document.getElementById('btnEstTodas')
  );

  botonesEstado.forEach(b=>{
    b.addEventListener('click', ()=>{
      marcarEstado(b);
      localStorage.setItem(EVAL_STATE_KEY, b.getAttribute('data-est') || 'todas');
      loadTablaEval();
    });
  });

  // Instrumento
  const sel = document.getElementById('filterInstrumento');
  if (sel) {
    const instSaved = localStorage.getItem(EVAL_INST_KEY) || '';
    sel.value = instSaved;
    sel.addEventListener('change', ()=>{
      localStorage.setItem(EVAL_INST_KEY, sel.value || '');
      loadTablaEval();
    });
  }
})();

// Abrir modal evaluar
window.openEvaluarEvidencia = function(id){
  if (typeof window.evalOpenModal === 'function') window.evalOpenModal(id);
};

// Cargar al entrar
loadTablaEval();
</script>

<?php include COMPONENTS_DIR . '/layout/footer.php'; ?>