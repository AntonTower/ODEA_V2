<?php
/**
 * Detalle de Evidencia
 * - URL: evidencia-detalle.php?id=ID_EVIDENCIA
 * - Roles:
 *   - 4 (docente): debe ser dueño de la evidencia para ver/editar; si no, redirigir a index.php
 *   - 1,2,3: pueden ver. Solo 4 edita.
 * - Carga contenido por AJAX desde vista-evidencia-detalle.php
 * - Modal de edición (sólo si puede editar)
 */

require_once __DIR__ . '/../../config/paths.php';
require_once CONFIG_DIR . '/validacion.php';
require_once CONFIG_DIR . '/conexion.php';
require_once CONFIG_DIR . '/evidencias.php';

$usuarioActual = evidenciaUsuarioActual($conn);
$user_id = (int)$usuarioActual['id_usuario'];
$user_role = (int)$usuarioActual['rol'];
if ($user_id <= 0) {
  destruirSesionSegura();
  header('Location: ' . BASE_URL . '/public/login.php');
  exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  header('Location: ' . BASE_URL . '/views/docente/gestion-evidencias.php');
  exit;
}

// Traer la evidencia + dueño + tipo
$sql = "SELECT e.id_evidencia, e.titulo, e.archivo, e.fecha_subida,
               e.id_docente, u.nombre, u.apellidop, u.apellidom,
               t.id_tipo_evidencia, t.nombre_tipo, t.descripcion AS desc_tipo
        FROM evidencias e
        LEFT JOIN usuarios u ON u.id_usuario = e.id_docente
        LEFT JOIN tipos_de_evidencia t ON t.id_tipo_evidencia = e.id_tipo_evidencia
        WHERE e.id_evidencia = ? AND e.ocultar = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$evi = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$evi) {
  header('Location: ' . BASE_URL . '/views/docente/gestion-evidencias.php');
  exit;
}

// Si es docente, debe ser dueño
if (!evidenciaPuedeVer($conn, $usuarioActual, $evi)) {
  header('Location: ' . BASE_URL . '/views/comunes/index.php');
  exit;
}

$canEdit = evidenciaPuedeEditar($usuarioActual, $evi);

// ==== Datos para vista previa del archivo ====
$archivoRaw   = (string)($evi['archivo'] ?? '');
$archivoBase  = basename($archivoRaw); // evita traversal
$uploadFsDir  = BASE_DIR . '/uploads/files/';

$hasFile   = ($archivoBase !== '');
$fileUrl   = $hasFile ? (BASE_URL . '/controllers/docente/evidencia-archivo.php?id=' . $id) : '';
$fileContentUrl = $hasFile ? $fileUrl . '&contenido=1' : '';
$fileDownloadUrl = $hasFile ? $fileUrl . '&descargar=1' : '';
$fileFs    = $hasFile ? ($uploadFsDir . $archivoBase) : '';
$fileOk    = $hasFile && is_file($fileFs) && is_readable($fileFs);
$ext       = $fileOk ? strtolower(pathinfo($archivoBase, PATHINFO_EXTENSION)) : '';
$isPdf     = $fileOk && ($ext === 'pdf');
$isImg     = $fileOk && in_array($ext, ['png','jpg','jpeg','webp'], true);

$LEFT_ACTIVE_OVERRIDE = 'gestion-evidencias.php';
include COMPONENTS_DIR . '/layout/header.php';
include COMPONENTS_DIR . '/layout/left-menu.php';
?>
<section class="space-y-6">
  <header class="flex items-center justify-between" style="display:flex; align-items:center; justify-content:space-between; gap:16px;">
    <div>
      <h1 class="text-2xl font-semibold">Detalle de evidencia</h1>
      <p class="text-muted-foreground" style="max-width:900px;">
        Tipo: <strong><?= htmlspecialchars($evi['nombre_tipo'] ?? 'Tipo no disponible', ENT_QUOTES, 'UTF-8') ?></strong>
        — <?= htmlspecialchars($evi['desc_tipo'] ?? '', ENT_QUOTES, 'UTF-8') ?>
      </p>
    </div>

    <div style="display:flex; gap:8px;">
      <a class="btn" href="gestion-evidencias.php" title="Regresar">
        <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
          <path d="M15 19l-7-7 7-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span style="margin-left:6px;">Regresar</span>
      </a>
      <?php if ($canEdit): ?>
        <button class="btn btn-primary" id="btnOpenEdit" type="button" title="Editar atributos">
          Editar atributos
        </button>
      <?php endif; ?>
    </div>
  </header>

  <!-- Tarjeta con info general -->
  <div class="card">
    <div class="card-content" style="display:flex; flex-wrap:wrap; gap:16px; align-items:center; justify-content:space-between;">
      <div>
        <div class="text-sm text-muted-foreground">Título</div>
        <div class="text-lg font-medium"><?= htmlspecialchars($evi['titulo'] ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <div>
        <div class="text-sm text-muted-foreground">Docente</div>
        <div><?= htmlspecialchars(trim(($evi['nombre'] ?? '').' '.($evi['apellidop'] ?? '').' '.($evi['apellidom'] ?? '')) ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <div>
        <div class="text-sm text-muted-foreground">Archivo</div>
        <?php if ($fileOk): ?>
          <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a class="btn" href="<?= htmlspecialchars($fileDownloadUrl, ENT_QUOTES, 'UTF-8') ?>">Descargar archivo</a>
            <a class="btn" href="<?= htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Abrir en pestaña</a>
          </div>
        <?php else: ?>
          <span class="badge secondary">Sin archivo</span>
        <?php endif; ?>
      </div>
      <div>
        <div class="text-sm text-muted-foreground">Subida</div>
        <div><span class="badge"><?= !empty($evi['fecha_subida']) && strtotime($evi['fecha_subida']) !== false ? date('Y-m-d H:i', strtotime($evi['fecha_subida'])) : '—' ?></span></div>
      </div>
    </div>
  </div>

  <!-- Vista dinámica de atributos -->
  <div id="vistaDetalle" class="card">
    <div class="card-content"><p class="text-muted-foreground">Cargando detalles…</p></div>
  </div>

  <!-- ===== Vista previa del archivo (debajo de atributos) ===== -->
  <?php if ($fileOk): ?>
    <div class="card">
      <div class="card-content">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:10px;">
          <h2 class="text-xl font-semibold">Vista previa del archivo</h2>
          <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a class="btn" href="<?= htmlspecialchars($fileDownloadUrl, ENT_QUOTES, 'UTF-8') ?>">Descargar</a>
            <a class="btn" href="<?= htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Abrir en pestaña</a>
          </div>
        </div>

        <?php if ($isPdf): ?>
          <!-- PDF embebido -->
          <object
            data="<?= htmlspecialchars($fileContentUrl, ENT_QUOTES, 'UTF-8') ?>#toolbar=1&navpanes=0&scrollbar=1"
            type="application/pdf"
            width="100%"
            height="720"
            style="border:1px solid var(--border); border-radius:12px; box-shadow:0 4px 16px rgba(0,0,0,.06);">
              <p>
                No fue posible mostrar el PDF embebido.
                <a class="link" href="<?= htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Ábrelo en una pestaña</a>.
              </p>
          </object>
        <?php elseif ($isImg): ?>
          <!-- Imagen responsiva -->
          <div style="width:100%; overflow:auto;">
            <img
              src="<?= htmlspecialchars($fileContentUrl, ENT_QUOTES, 'UTF-8') ?>"
              alt="Vista previa de la evidencia"
              style="max-width:100%; height:auto; display:block; border-radius:12px; border:1px solid var(--border); box-shadow:0 4px 16px rgba(0,0,0,.06);" />
          </div>
        <?php else: ?>
          <div class="alert">
            Este formato no puede previsualizarse aquí. Puedes
            <a class="link" href="<?= htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">abrirlo</a> o <a class="link" href="<?= htmlspecialchars($fileDownloadUrl, ENT_QUOTES, 'UTF-8') ?>">descargarlo</a>.
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
  <?php include __DIR__.'/aplicaciones-academicas.php'; ?>
</section>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<?php if ($canEdit) include COMPONENTS_DIR . '/modals/modal-editar-evidencia-detalle.php'; ?>

<script>
const EVID_ID = <?= (int)$evi['id_evidencia'] ?>;
const CAN_EDIT = <?= $canEdit ? 'true' : 'false' ?>;

function loadDetalle(){
  const url = '<?= BASE_URL ?>/views/docente/vista-evidencia-detalle.php?id=' + EVID_ID + '&r=' + Date.now();
  const xhr = new XMLHttpRequest();
  xhr.onreadystatechange = function(){
    if (xhr.readyState === 4) {
      const cont = document.getElementById('vistaDetalle');
      cont.innerHTML = xhr.status === 200 ? xhr.responseText
                                          : '<div class="card-content"><p>Error al cargar los detalles.</p></div>';
    }
  };
  xhr.open('GET', url, true);
  xhr.send();
}

document.addEventListener('DOMContentLoaded', loadDetalle);

<?php if ($canEdit): ?>
document.getElementById('btnOpenEdit')?.addEventListener('click', function(){
  if (typeof window.openEditAtributosModal === 'function') window.openEditAtributosModal(EVID_ID);
});
<?php endif; ?>
</script>

<?php include COMPONENTS_DIR . '/layout/footer.php'; ?>
