<?php
/**
 * Gestión del menú (versión proyecto actual)
 * - Requiere sesión iniciada (incluye validacion.php).
 * - Usa header/left-menu/footer del proyecto.
 * - Carga la tabla con AJAX desde vista-gestion-menu.php.
 * - Búsqueda en cliente (tu input superior) + filtro por rol (servidor).
 */

// Definición de constantes de rutas si no existen
if (!defined('BASE_DIR')) {
    define('BASE_DIR', realpath(__DIR__ . '/../../'));
    define('CONFIG_DIR', BASE_DIR . '/config');
    define('COMPONENTS_DIR', BASE_DIR . '/components');
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/ODEA/gestor_evidencias');
}

require_once CONFIG_DIR . '/validacion.php'; // Verificar que el usuario ha iniciado sesión
require_once CONFIG_DIR . '/conexion.php';
require_once CONFIG_DIR . '/validacion-permiso.php'; // Verificar que el usuario está permitido de acceder a esta página

include COMPONENTS_DIR . '/layout/header.php';
include COMPONENTS_DIR . '/layout/left-menu.php';

// === Cargar roles para el filtro ===
$query_roles = "SELECT id_rol, nombre FROM roles ORDER BY id_rol";
$roles = [];
if ($rs = mysqli_query($conn, $query_roles)) {
  while ($r = mysqli_fetch_assoc($rs)) { $roles[] = $r; }
}
?>

<section class="space-y-6">
  <header class="flex items-center justify-between" style="display:flex; align-items:center; justify-content:space-between; gap:16px;">
    <div>
      <h1 class="text-2xl font-semibold">Gestión del menú</h1>
      <p class="text-muted-foreground">Administra las páginas del menú lateral y su visibilidad por rol.</p>
    </div>

    <!-- Habilitado y enlazado al modal -->
    <button class="btn btn-primary" id="openModalAgregar" type="button" title="Agregar nueva página">
      + Agregar Página
    </button>
  </header>

  <!-- Barra de herramientas -->
  <div class="card">
    <div class="card-content" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between;">
      <!-- Búsqueda externa -->
      <div style="max-width:520px; width:100%; display:flex; align-items:center; gap:8px;">
        <input id="search" class="input" placeholder="Buscar por página o archivo…" aria-label="Buscar" />
        <button id="clearSearch" class="btn" type="button" title="Limpiar búsqueda">Limpiar</button>
      </div>

      <!-- Filtro por Rol (servidor) -->
      <div>
        <select id="filterRole" class="input select-white" style="min-width:260px;">
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
  <div id="tablaGestionMenu" class="card">
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

<!-- ===== Modal Eliminar (se queda en esta página, fuera del HTML que recarga por AJAX) ===== -->
<div class="dialog-backdrop" id="dlgBackdrop"></div>
<div class="dialog" id="dlgEliminar" role="dialog" aria-modal="true" aria-labelledby="dlgEliminarTitle" data-open="false">
  <div class="dialog-card" style="max-width:520px;">
    <div class="dialog-header">
      <h3 class="dialog-title" id="dlgEliminarTitle">Eliminar página</h3>
      <p class="dialog-desc">Esta acción no se puede deshacer.</p>
    </div>
    <div class="dialog-body">
      <p id="dlgEliminarText">¿Estás seguro de eliminar esta página?</p>
    </div>
    <div class="dialog-actions">
      <button class="btn" type="button" onclick="closeEliminar()">Cancelar</button>
      <button class="btn btn-primary" type="button" onclick="confirmEliminar()">Eliminar</button>
    </div>
  </div>
</div>

<?php
// Modales de la gestión del menú
$modal_agregar_pagina = COMPONENTS_DIR . '/modals/modal-agregar-pagina.php';
$modal_editar_pagina  = COMPONENTS_DIR . '/modals/modal-editar-pagina.php';

if (file_exists($modal_agregar_pagina)) include $modal_agregar_pagina;
if (file_exists($modal_editar_pagina))  include $modal_editar_pagina;
?>
<script>
/* =================== Utilidades comunes =================== */
window.lockPageScroll = function(lock){
  const html = document.documentElement;
  html.style.overflow = lock ? 'hidden' : '';
};

/* =================== DataTable / Carga AJAX =================== */
const SEARCH_KEY = 'gestionMenuSearch';
let dt = null;
let currentRole = ''; // rol activo

// Carga inicial
loadTable();

// Cambio de rol -> recargar vista
document.addEventListener('DOMContentLoaded', () => {
  const sel = document.getElementById('filterRole');
  if (sel) {
    sel.addEventListener('change', function(){
      currentRole = this.value || '';
      loadTable();
    });
  }
});

// Expuesta para que otros la usen tras crear/editar/eliminar
function loadTable(){
  const url = '<?= BASE_URL ?>/views/admin/vista-gestion-menu.php?role=' + encodeURIComponent(currentRole) + '&r=' + Date.now();
  const xhr = new XMLHttpRequest();
  xhr.onreadystatechange = function(){
    if (xhr.readyState === 4) {
      const cont = document.getElementById('tablaGestionMenu');
      cont.innerHTML = xhr.status === 200 ? xhr.responseText
                                          : '<div class="card-content"><p>Error al cargar la tabla.</p></div>';
      initDT();
    }
  };
  xhr.open('GET', url, true);
  xhr.send();
}

function initDT(){
  const $table = $('#tabla-conf');
  if (!$table.length) return;

  if (dt) { dt.destroy(); dt = null; }

  dt = $table.DataTable({
    responsive: { details: { type: 'column', target: 0 } },
    columnDefs: [{ className: 'control', orderable: false, targets: 0 }],
    paging: true,
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    stateSave: true,
    order: [[1, 'asc']],
    dom: "<'dt-top'>t<'dt-bottom' l i p>", // length + info + paginación abajo
    language: {
      info: "Mostrando _START_ a _END_ de _TOTAL_ páginas",
      infoFiltered: "(filtrado de _MAX_ páginas)",
      emptyTable: "No hay información",
      zeroRecords: "No se encontró coincidencia",
      lengthMenu: "Mostrar _MENU_",
      paginate: { first:"Primero", last:"Último", next:"Siguiente", previous:"Anterior" }
    }
  });

  // Buscador externo
  const $search = $('#search');
  const $clear  = $('#clearSearch');

  if ($search.length) {
    const saved = dt.state()?.search?.search || localStorage.getItem(SEARCH_KEY) || '';
    $search.val(saved);
    if (saved) dt.search(saved).draw();

    $search.on('input', function(){
      const val = this.value;
      dt.search(val).draw();
      localStorage.setItem(SEARCH_KEY, val);
    });
  }
  if ($clear.length) {
    $clear.on('click', function(){
      if ($search.length) {
        $search.val('');
        localStorage.setItem(SEARCH_KEY, '');
      }
      dt.search('').draw();
    });
  }
}

/* =================== Handlers globales =================== */

// Mostrar/Ocultar
window.toggleEstado = function(id, to){
  fetch('<?= BASE_URL ?>/controllers/admin/menu-pagina-toggle.php', {
    method: 'POST',
    headers: { 'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8' },
    body: new URLSearchParams({ id_mp:id, ocultar:to })
  })
  .then(r=>r.json())
  .then(j=>{
    if (j && j.status === 'ok') {
      if (window.Swal) Swal.fire({icon:'success', title:'Actualizado', text:'El estado se cambió correctamente.'});
      loadTable();
    } else {
      const msg = (j && j.message) ? j.message : 'No fue posible actualizar.';
      if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg}); else alert(msg);
    }
  })
  .catch(()=>{
    if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'No se pudo actualizar el estado.'});
  });
};

// Editar
window.openEditar = function(id){
  if (typeof window.openEditarPagina === 'function') {
    window.openEditarPagina(id);
  } else {
    if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text:'No se encontró el modal de edición.'});
  }
};

// Eliminar
let __deleteId = null;
window.openEliminar = function(id, name){
  __deleteId = id;
  const dlg = document.getElementById('dlgEliminar');
  const backdrop = document.getElementById('dlgBackdrop');
  const txt = document.getElementById('dlgEliminarText');

  if (txt) txt.innerHTML = '¿Estás seguro de eliminar <strong>' + (name || 'esta página') + '</strong>?';
  if (dlg && backdrop) {
    dlg.setAttribute('data-open','true');
    backdrop.setAttribute('data-open','true');
    lockPageScroll(true);
  }
};
window.closeEliminar = function(){
  __deleteId = null;
  const dlg = document.getElementById('dlgEliminar');
  const backdrop = document.getElementById('dlgBackdrop');
  if (dlg && backdrop) {
    dlg.setAttribute('data-open','false');
    backdrop.setAttribute('data-open','false');
    lockPageScroll(false);
  }
};
window.confirmEliminar = function(){
  if (!__deleteId) return;
  fetch('<?= BASE_URL ?>/controllers/admin/menu-pagina-eliminar.php', {
    method: 'POST',
    headers: { 'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8' },
    body: new URLSearchParams({ id_mp: __deleteId })
  })
  .then(r=>r.json())
  .then(j=>{
    closeEliminar();
    if (j && j.status === 'ok') {
      if (window.Swal) Swal.fire({icon:'success', title:'Eliminado', text:'La página se eliminó correctamente.'});
      loadTable();
    } else {
      const msg = (j && j.message) ? j.message : 'No se pudo eliminar.';
      if (window.Swal) Swal.fire({icon:'warning', title:'Aviso', text: msg});
    }
  })
  .catch(()=>{
    closeEliminar();
    if (window.Swal) Swal.fire({icon:'error', title:'Error', text:'No se pudo eliminar la página.'});
  });
};

// Cerrar modal de eliminar tocando el backdrop
(function(){
  const backdrop = document.getElementById('dlgBackdrop');
  if (backdrop) backdrop.addEventListener('click', window.closeEliminar);
})();
</script>

<?php include COMPONENTS_DIR . '/layout/footer.php'; ?>