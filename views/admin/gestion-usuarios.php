<?php
/**
 * Gestión de usuarios (versión análoga a gestion-menu.php)
 * - Requiere sesión y permisos.
 * - Usa header/left-menu/footer.
 * - Carga la tabla con AJAX.
 */

// Definición de constantes de rutas si no existen
if (!defined('BASE_DIR')) {
    define('BASE_DIR', realpath(__DIR__ . '/../../'));
    define('COMPONENTS_DIR', BASE_DIR . '/components');
    define('CONFIG_DIR', BASE_DIR . '/config');
    define('CONTROLLERS_DIR', BASE_DIR . '/controllers');
}

require_once __DIR__ . '/../../config/paths.php';

require_once CONFIG_DIR . '/validacion.php';
require_once CONFIG_DIR . '/conexion.php';
require_once CONFIG_DIR . '/validacion-permiso.php';
require_once CONFIG_DIR . '/csrf.php';
$csrfUsuarios = csrf_token();

include COMPONENTS_DIR . '/layout/header.php';
include COMPONENTS_DIR . '/layout/left-menu.php';

// === Cargar roles para el filtro ===
$query_roles = "SELECT id_rol, nombre FROM roles ORDER BY id_rol";
$roles = [];
if ($rs = mysqli_query($conn, $query_roles)) {
  while ($r = mysqli_fetch_assoc($rs)) { $roles[] = $r; }
}
?>
<style>
  /* =========================================================================
     Gestión de usuarios
     - El layout responde al ancho del contenedor (container queries), por lo
       que se adapta solo cuando el menú lateral se abre o se colapsa.
     - La tabla la inyecta AJAX; sus estilos se anclan a #tablaGestionUsuarios
       y cubren DataTables 2 (dt-*) y su extensión Responsive (dtr-*).
     ========================================================================= */

  .usuarios-page {
    --us-gap: clamp(12px, 1.6vw, 24px);
    --us-pad: clamp(14px, 1.5vw, 20px);
    --us-line: var(--border, #e4e4e7);
    --us-surface: var(--card, #ffffff);
    --us-muted: var(--muted-foreground, #71717a);
    --us-accent: var(--primary, #2563eb);
    --us-radius: var(--radius, 12px);
    --us-radius-sm: var(--radius-sm, 8px);
    --us-shadow: 0 1px 2px rgba(0, 0, 0, .05);

    display: grid;
    align-content: start;
    gap: var(--us-gap);
    width: 100%;
    max-width: 1680px;
    min-width: 0;
    margin-inline: auto;
    container-type: inline-size;
    container-name: usuarios;
  }
  .usuarios-page > * { min-width: 0; margin: 0; }

  /* ---------- Encabezado ---------- */
  .usuarios-page .dashboard-hero {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px 20px;
    padding: 4px 0 2px;
  }
  .usuarios-page .dashboard-hero > div { flex: 1 1 280px; min-width: 0; }
  .usuarios-page .dashboard-hero h1 {
    margin: 0 0 4px;
    font-size: clamp(1.35rem, 1.1rem + 1vw, 1.85rem);
    line-height: 1.2;
    letter-spacing: -.02em;
    text-wrap: balance;
  }
  .usuarios-page .dashboard-eyebrow {
    margin: 0 0 4px;
    font-size: .75rem;
    font-weight: 600;
    color: var(--us-accent);
  }
  .usuarios-page .dashboard-hero .text-muted-foreground {
    max-width: 68ch;
    margin: 0;
    font-size: .9rem;
    line-height: 1.5;
    color: var(--us-muted);
  }
  .usuarios-page .dashboard-hero > .btn {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
  }

  /* ---------- Tarjetas ---------- */
  .usuarios-page .card {
    display: flex;
    flex-direction: column;
    min-width: 0;
    border: 1px solid var(--us-line);
    border-radius: var(--us-radius);
    background: var(--us-surface);
    box-shadow: var(--us-shadow);
  }
  .usuarios-page .card-content {
    flex: 1 1 auto;
    min-width: 0;
    padding: var(--us-pad);
  }

  /* ---------- Barra de herramientas ---------- */
  .usuarios-page .usuarios-toolbar .card-content {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-items: end;
    gap: 12px 16px;
  }
  .usuarios-field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
  .usuarios-field > label { font-size: .8rem; font-weight: 600; }
  .usuarios-search-row { display: flex; align-items: center; gap: 8px; }
  .usuarios-search-row .input { flex: 1 1 auto; min-width: 0; }
  .usuarios-search-row .btn { flex: none; }
  .usuarios-field .input,
  .usuarios-field .btn { min-height: 42px; }
  .usuarios-field select.input { width: 100%; }

  /* ---------- Tabla (contenido inyectado por AJAX) ---------- */
  #tablaGestionUsuarios .card-content { overflow-x: auto; }
  #tablaGestionUsuarios > .card-content > p { padding: 24px 8px; text-align: center; color: var(--us-muted); }

  #tablaGestionUsuarios table.dataTable { width: 100%; border-collapse: collapse; }
  #tablaGestionUsuarios table.dataTable thead th {
    padding-block: 10px;
    padding-inline-start: 12px;
    border-bottom: 1px solid var(--us-line);
    background: var(--muted, var(--us-surface));
    font-size: .74rem;
    font-weight: 600;
    text-align: start;
    white-space: nowrap;
    color: var(--us-muted);
  }
  #tablaGestionUsuarios table.dataTable tbody td {
    padding: 10px 12px;
    border: 0;
    border-bottom: 1px solid var(--us-line);
    box-shadow: none;
    font-size: .85rem;
    line-height: 1.4;
    vertical-align: middle;
    overflow-wrap: anywhere;
  }
  #tablaGestionUsuarios table.dataTable tbody tr:last-child td { border-bottom: 0; }
  #tablaGestionUsuarios table.dataTable tbody td.dt-empty {
    padding: 36px 12px;
    text-align: center;
    color: var(--us-muted);
  }
  #tablaGestionUsuarios table.dataTable tbody td.dtr-control { width: 40px; }
  #tablaGestionUsuarios table.dataTable tbody td .btn {
    min-height: 32px;
    margin: 2px 4px 2px 0;
    padding: 4px 10px;
    font-size: .78rem;
    white-space: nowrap;
  }
  @media (hover: hover) {
    #tablaGestionUsuarios table.dataTable tbody tr:hover > td {
      background: color-mix(in srgb, var(--us-accent) 5%, transparent);
    }
  }

  /* Filas expandidas (Responsive): lista de etiqueta y valor */
  #tablaGestionUsuarios table.dataTable tbody tr.child td.child { padding: 0 12px 8px 12px; }
  #tablaGestionUsuarios ul.dtr-details { display: grid; margin: 0; padding: 0; list-style: none; }
  #tablaGestionUsuarios ul.dtr-details > li {
    display: grid;
    grid-template-columns: minmax(90px, 34%) minmax(0, 1fr);
    gap: 4px 12px;
    padding: 9px 0;
    border-top: 1px solid var(--us-line);
  }
  #tablaGestionUsuarios ul.dtr-details > li:first-child { border-top: 0; }
  #tablaGestionUsuarios ul.dtr-details .dtr-title {
    min-width: 0;
    font-size: .74rem;
    font-weight: 600;
    color: var(--us-muted);
  }
  #tablaGestionUsuarios ul.dtr-details .dtr-data { min-width: 0; font-size: .85rem; overflow-wrap: anywhere; }

  /* Mostrar, información y paginación */
  #tablaGestionUsuarios .dt-layout-row:not(.dt-layout-table),
  #tablaGestionUsuarios .dt-bottom {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px 16px;
    margin-top: 14px;
    font-size: .82rem;
    color: var(--us-muted);
  }
  #tablaGestionUsuarios .dt-length { display: flex; align-items: center; gap: 8px; }
  #tablaGestionUsuarios .dt-length select {
    min-height: 36px;
    padding: 4px 8px;
    border: 1px solid var(--us-line);
    border-radius: var(--us-radius-sm);
    background-color: var(--us-surface);
    color: inherit;
  }
  #tablaGestionUsuarios .dt-paging nav { display: flex; flex-wrap: wrap; gap: 4px; justify-content: center; }
  #tablaGestionUsuarios .dt-paging .dt-paging-button {
    min-width: 36px;
    min-height: 36px;
    margin: 0;
    padding: 4px 10px;
    border: 1px solid var(--us-line);
    border-radius: var(--us-radius-sm);
    background: var(--us-surface);
    font-size: .8rem;
    color: inherit;
    transition: border-color .15s ease, background-color .15s ease;
  }
  #tablaGestionUsuarios .dt-paging .dt-paging-button.current,
  #tablaGestionUsuarios .dt-paging .dt-paging-button.current:hover {
    border-color: var(--us-accent);
    background: color-mix(in srgb, var(--us-accent) 12%, var(--us-surface));
    color: var(--us-accent);
    font-weight: 600;
  }
  #tablaGestionUsuarios .dt-paging .dt-paging-button.disabled,
  #tablaGestionUsuarios .dt-paging .dt-paging-button.disabled:hover {
    opacity: .45;
    cursor: not-allowed;
    background: var(--us-surface);
  }
  @media (hover: hover) {
    #tablaGestionUsuarios .dt-paging .dt-paging-button:not(.current):not(.disabled):hover { border-color: var(--us-accent); }
  }

  /* ---------- Diálogo de eliminar ---------- */
  #dlgEliminarU .dialog-card { width: min(100%, 520px); max-height: calc(100dvh - 24px); overflow: auto; }
  #dlgEliminarU .dialog-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px; }
  /* La acción es irreversible: el botón de confirmar se marca como destructivo */
  #dlgEliminarU .dialog-actions .btn-primary {
    border-color: var(--destructive, #dc2626);
    background: var(--destructive, #dc2626);
    color: var(--destructive-foreground, #ffffff);
  }
  @media (max-width: 479px) {
    #dlgEliminarU .dialog-actions .btn { flex: 1 1 100%; min-height: 44px; }
  }

  /* ---------- Accesibilidad ---------- */
  .usuarios-page :is(a, button, .btn, select, input, [tabindex]):focus-visible {
    outline: 2px solid var(--ring, var(--us-accent));
    outline-offset: 2px;
  }
  @media (pointer: coarse) {
    .usuarios-page .btn,
    .usuarios-page .dt-paging .dt-paging-button { min-height: 44px; }
    #tablaGestionUsuarios table.dataTable tbody td .btn { min-height: 40px; }
  }
  @media (prefers-reduced-motion: reduce) {
    .usuarios-page * { transition: none !important; }
  }

  /* =========================================================================
     Puntos de quiebre según el ancho del contenedor
     ========================================================================= */

  /* Móvil: botón principal a ancho completo, pie de tabla centrado y apilado */
  @container usuarios (max-width: 559px) {
    .usuarios-page .dashboard-hero > .btn { flex: 1 1 100%; }
    #tablaGestionUsuarios .dt-layout-row:not(.dt-layout-table),
    #tablaGestionUsuarios .dt-bottom { flex-direction: column; align-items: center; text-align: center; }
    #tablaGestionUsuarios .dt-paging .dt-paging-button.first,
    #tablaGestionUsuarios .dt-paging .dt-paging-button.last { display: none; }
  }

  /* Tableta: búsqueda y filtro en una misma fila */
  @container usuarios (min-width: 600px) {
    .usuarios-page .usuarios-toolbar .card-content { grid-template-columns: minmax(0, 1fr) minmax(220px, 300px); }
  }

  /* Escritorio: filtro más amplio */
  @container usuarios (min-width: 1000px) {
    .usuarios-page .usuarios-toolbar .card-content { grid-template-columns: minmax(0, 1fr) 340px; }
  }
</style>

<section class="usuarios-page" aria-labelledby="usuarios-titulo">
  <header class="dashboard-hero">
    <div>
      <p class="dashboard-eyebrow">ODEA · Administración</p>
      <h1 id="usuarios-titulo">Gestión de usuarios</h1>
      <p class="text-muted-foreground">Administra las cuentas de usuario, su rol y estado.</p>
    </div>

    <!-- Botón para abrir modal -->
    <button class="btn btn-primary" id="openModalAgregarUsuario" type="button" title="Agregar nuevo usuario">
      + Agregar Usuario
    </button>
  </header>

  <!-- Barra de herramientas -->
  <div class="card usuarios-toolbar">
    <div class="card-content">
      <!-- Búsqueda externa -->
      <div class="usuarios-field">
        <label for="search">Buscar</label>
        <div class="usuarios-search-row">
          <input id="search" class="input" placeholder="Buscar por nombre, correo…" autocomplete="off" />
          <button id="clearSearch" class="btn" type="button" title="Limpiar búsqueda">Limpiar</button>
        </div>
      </div>

      <!-- Filtro por Rol (servidor) -->
      <div class="usuarios-field">
        <label for="filterRole">Rol</label>
        <select id="filterRole" class="input select-white">
          <option value="">Rol: Todos</option>
          <?php foreach ($roles as $rol): ?>
            <option value="<?= (int)$rol['id_rol'] ?>">
              <?= htmlspecialchars($rol['id_rol'].' - '.$rol['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div id="tablaGestionUsuarios" class="card" aria-live="polite">
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

<!-- ===== Modal Eliminar ===== -->
<div class="dialog-backdrop" id="dlgBackdropU"></div>
<div class="dialog" id="dlgEliminarU" role="dialog" aria-modal="true" aria-labelledby="dlgEliminarUTitle" data-open="false">
  <div class="dialog-card" style="max-width:520px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgEliminarUTitle">Cambiar estado de cuenta</h3>
      <p class="dialog-desc" id="dlgEliminarUHelp">Las evidencias y evaluaciones históricas se conservan. Al reactivar un evaluador, administración deberá asignarle instrumentos otra vez.</p>
    </div>
    <div class="dialog-body">
      <p id="dlgEliminarUText">¿Deseas cambiar el estado de esta cuenta?</p>
    </div>
    <div class="dialog-actions">
      <button class="btn" type="button" onclick="closeEliminarU()">Cancelar</button>
      <button class="btn btn-primary" id="btnConfirmarEstadoUsuario" type="button" onclick="confirmEliminarU()">Confirmar</button>
    </div>
  </div>
</div>

<?php
// Modales desde components/modals
$modal_agregar = COMPONENTS_DIR . '/modals/modal-agregar-usuario.php';
$modal_editar  = COMPONENTS_DIR . '/modals/modal-editar-usuario.php';

if (file_exists($modal_agregar)) include $modal_agregar;
if (file_exists($modal_editar))  include $modal_editar;
?>

<script>
const csrfUsuarios = <?= json_encode($csrfUsuarios, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.lockPageScroll = function(lock){
  const html = document.documentElement;
  html.style.overflow = lock ? 'hidden' : '';
};

const SEARCH_KEY = 'gestionUsuariosSearch';
let dtU = null;
let currentRoleU = '';

loadTableU();

document.addEventListener('DOMContentLoaded', () => {
  const sel = document.getElementById('filterRole');
  if (sel) {
    sel.addEventListener('change', function(){
      currentRoleU = this.value || '';
      loadTableU();
    });
  }
});

function loadTableU(){
  const url = '<?= BASE_URL ?>/views/admin/vista-gestion-usuarios.php?role=' + encodeURIComponent(currentRoleU) + '&r=' + Date.now();
  const xhr = new XMLHttpRequest();
  xhr.onreadystatechange = function(){
    if (xhr.readyState === 4) {
      const cont = document.getElementById('tablaGestionUsuarios');
      cont.innerHTML = xhr.status === 200 ? xhr.responseText
                                          : '<div class="card-content"><p>Error al cargar la tabla.</p></div>';
      initDTU();
    }
  };
  xhr.open('GET', url, true);
  xhr.send();
}

function initDTU(){
  const $table = $('#tabla-usuarios');
  if (!$table.length) return;

  if (dtU) { dtU.destroy(); dtU = null; }

  dtU = $table.DataTable({
    responsive: { details: { type: 'column', target: 0 } },
    columnDefs: [{ className: 'control', orderable: false, targets: 0 }],
    paging: true,
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    stateSave: true,
    order: [[1, 'asc']],
    dom: "<'dt-top'>t<'dt-bottom' l i p>",
    language: {
      info: "Mostrando _START_ a _END_ de _TOTAL_ usuarios",
      infoFiltered: "(filtrado de _MAX_ usuarios)",
      emptyTable: "No hay información",
      zeroRecords: "No se encontró coincidencia",
      lengthMenu: "Mostrar _MENU_",
      paginate: { first:"Primero", last:"Último", next:"Siguiente", previous:"Anterior" }
    }
  });

  const $search = $('#search');
  const $clear  = $('#clearSearch');

  if ($search.length) {
    const saved = dtU.state()?.search?.search || localStorage.getItem(SEARCH_KEY) || '';
    $search.val(saved);
    if (saved) dtU.search(saved).draw();

    // off() evita acumular manejadores cada vez que se recarga la tabla
    $search.off('input.usuarios').on('input.usuarios', function(){
      const val = this.value;
      dtU.search(val).draw();
      localStorage.setItem(SEARCH_KEY, val);
    });
  }
  if ($clear.length) {
    $clear.off('click.usuarios').on('click.usuarios', function(){
      if ($search.length) {
        $search.val('');
        localStorage.setItem(SEARCH_KEY, '');
      }
      dtU.search('').draw();
    });
  }
}

// Redirección al perfil
window.openVerPerfil = function(id){
  window.location.href = '<?= BASE_URL ?>/views/comunes/perfil-usuario.php?id=' + encodeURIComponent(id);
};

window.openEditarUsuario = function(id){
  if (typeof window.openEditarUsuarioModal === 'function') {
    window.openEditarUsuarioModal(id);
  } else {
    if (window.Swal) Swal.fire({icon:'info', title:'Editar', text:'Implementa el modal o redirección para editar el usuario '+id+'.'});
  }
};

let __deleteUserId = null;
let __targetUserActive = null;
window.openEliminarU = function(id, name, isActive){
  __deleteUserId = id;
  __targetUserActive = Boolean(isActive);
  const dlg = document.getElementById('dlgEliminarU');
  const backdrop = document.getElementById('dlgBackdropU');
  const txt = document.getElementById('dlgEliminarUText');
  const title = document.getElementById('dlgEliminarUTitle');
  const help = document.getElementById('dlgEliminarUHelp');
  const confirm = document.getElementById('btnConfirmarEstadoUsuario');
  const action = __targetUserActive ? 'desactivar' : 'reactivar';

  if (title) title.textContent = __targetUserActive ? 'Desactivar usuario' : 'Reactivar usuario';
  if (help) help.textContent = __targetUserActive
    ? 'La cuenta perderá acceso. Sus evidencias y evaluaciones se conservarán.'
    : 'La cuenta podrá iniciar sesión nuevamente. Las asignaciones de evaluador deberán configurarse otra vez.';
  if (confirm) {
    confirm.textContent = __targetUserActive ? 'Desactivar cuenta' : 'Reactivar cuenta';
    confirm.classList.toggle('btn-primary', __targetUserActive);
    confirm.dataset.action = action;
  }

  if (txt) {
    // Se arma con nodos de texto (no innerHTML) para que un nombre con HTML no se ejecute
    txt.textContent = `¿Deseas ${action} a `;
    const strong = document.createElement('strong');
    strong.textContent = name || ('usuario #' + id);
    txt.appendChild(strong);
    txt.appendChild(document.createTextNode('?'));
  }
  if (dlg && backdrop) {
    dlg.setAttribute('data-open','true');
    backdrop.setAttribute('data-open','true');
    lockPageScroll(true);
  }
};

window.closeEliminarU = function(){
  __deleteUserId = null;
  __targetUserActive = null;
  const dlg = document.getElementById('dlgEliminarU');
  const backdrop = document.getElementById('dlgBackdropU');
  if (dlg && backdrop) {
    dlg.setAttribute('data-open','false');
    backdrop.setAttribute('data-open','false');
    lockPageScroll(false);
  }
};

window.confirmEliminarU = function(){
  if (!__deleteUserId) return;
  const deleteId = __deleteUserId;
  const action = __targetUserActive ? 'desactivar' : 'activar';
  const button = document.getElementById('btnConfirmarEstadoUsuario');
  if (button) button.disabled = true;
  fetch('<?= BASE_URL ?>/controllers/admin/usuario-eliminar.php', {
    method: 'POST',
    headers: { 'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8', 'X-CSRF-Token':csrfUsuarios },
    body: new URLSearchParams({ id_usuario: __deleteUserId, accion: action })
  })
  .then(async response=>({response, body:await response.json()}))
  .then(({response, body})=>{
    closeEliminarU();
    if (response.ok && body && body.status === 'ok') {
      if (window.Swal) Swal.fire({icon:'success', title:body.action === 'activated' ? 'Cuenta reactivada' : 'Cuenta desactivada', text:body.message || 'El estado de la cuenta se actualizó.'});
      loadTableU();
    } else {
      const msg = (body && body.message) ? body.message : 'No se pudo desactivar la cuenta.';
      if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
    }
  })
  .catch(()=>{
    closeEliminarU();
    if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'No se pudo completar la solicitud. Si la sesión venció, recarga la página.'});
  })
  .finally(()=>{
    if (button) button.disabled = false;
    if (__deleteUserId === deleteId) __deleteUserId = null;
  });
};

window.openEliminarDefinitivoU = async function(id, name){
  if (!window.Swal) return;
  const first = await Swal.fire({
    icon: 'warning',
    title: 'Eliminar usuario por completo',
    html: '<p>Se eliminará la cuenta <strong></strong>, sus evidencias y los archivos asociados que ya no use otra evidencia.</p><p>Esta acción es irreversible. Si existen evaluaciones o revisiones históricas protegidas, el servidor rechazará la eliminación.</p>',
    showCancelButton: true,
    confirmButtonText: 'Continuar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#dc2626',
    didOpen: popup => { popup.querySelector('strong').textContent = name || ('usuario #' + id); }
  });
  if (!first.isConfirmed) return;
  const second = await Swal.fire({
    icon: 'error',
    title: 'Confirma la eliminación',
    text: 'Se borrarán también las evidencias cargadas por esta cuenta. No se puede deshacer.',
    input: 'text',
    inputPlaceholder: 'Escribe ELIMINAR',
    showCancelButton: true,
    confirmButtonText: 'Eliminar por completo',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#b91c1c',
    preConfirm: value => value === 'ELIMINAR' ? true : (Swal.showValidationMessage('Escribe ELIMINAR para confirmar.'), false)
  });
  if (!second.isConfirmed) return;
  Swal.fire({title:'Eliminando…', allowOutsideClick:false, didOpen:()=>Swal.showLoading()});
  try {
    const response = await fetch('<?= BASE_URL ?>/controllers/admin/usuario-eliminar-definitivo.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8','X-CSRF-Token':csrfUsuarios},
      body:new URLSearchParams({id_usuario:String(id)})
    });
    const body = await response.json();
    if (!response.ok || body.status !== 'ok') {
      await Swal.fire({icon:'warning',title:'No se pudo eliminar',text:body.message || 'La cuenta conserva datos relacionados.'});
      return;
    }
    await Swal.fire({icon:'success',title:'Usuario eliminado',text:body.message});
    loadTableU();
  } catch (_) {
    await Swal.fire({icon:'error',title:'Error',text:'No se pudo completar la solicitud. Si la sesión venció, recarga la página.'});
  }
};

(function(){
  const backdrop = document.getElementById('dlgBackdropU');
  if (backdrop) backdrop.addEventListener('click', window.closeEliminarU);
})();
</script>

<?php include __DIR__ . '/admin-ui-styles.php'; ?>
<?php include COMPONENTS_DIR . '/layout/footer.php'; ?>
