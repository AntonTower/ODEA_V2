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
<section class="space-y-6">
  <header class="flex items-center justify-between" style="display:flex; align-items:center; justify-content:space-between; gap:16px;">
    <div>
      <h1 class="text-2xl font-semibold">Gestión de evidencias</h1>
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
  <div class="card">
    <div class="card-content" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between;">
      <div style="max-width:520px; width:100%; display:flex; align-items:center; gap:8px;">
        <input id="search" class="input" placeholder="Buscar por título, docente o tipo…" aria-label="Buscar" />
        <button id="clearSearch" class="btn" type="button" title="Limpiar búsqueda">Limpiar</button>
      </div>

      <!-- Filtro por tipo de evidencia (servidor) -->
      <div>
        <select id="filterTipo" class="input select-white" style="min-width:260px;">
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
  <div id="tablaEvidencias" class="card">
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
