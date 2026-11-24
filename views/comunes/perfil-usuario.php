<?php
/**
 * Perfil de usuario
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

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$perfil = null;
if ($id) {
    $stmt = $conn->prepare(
        'SELECT u.nombre, u.apellidop, u.apellidom, u.correo, u.activo, r.nombre AS rol
         FROM usuarios u JOIN roles r ON r.id_rol = u.rol
         WHERE u.id_usuario = ?'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $perfil = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
if (!$perfil) {
    http_response_code(404);
}

include COMPONENTS_DIR . '/layout/header.php';
include COMPONENTS_DIR . '/layout/left-menu.php';
?>
<style>
  .common-profile-page { width: min(100%, 1040px); margin-inline: auto; }
  .common-profile-page > header {
    align-items: flex-start;
    padding: 4px 0 18px;
    border-bottom: 1px solid var(--border);
  }
  .common-profile-page h1 { margin: 0 0 6px; font-size: clamp(1.45rem, 2vw, 1.8rem); }
  .common-profile-page .text-muted-foreground { margin: 0; font-size: .9rem; }
</style>

<section class="space-y-6 common-profile-page">
  <header class="flex items-center justify-between" style="display:flex; align-items:center; justify-content:space-between; gap:16px;">
    <div>
      <h1 class="text-2xl font-semibold">Perfil</h1>
      <p class="text-muted-foreground">Consulta los datos de la cuenta seleccionada.</p>
    </div>
  </header>

  <?php if ($perfil): ?>
    <dl class="card" style="display:grid;gap:12px;padding:20px;">
      <div>
        <dt>Nombre</dt>
        <dd><?= htmlspecialchars(trim($perfil['nombre'] . ' ' . $perfil['apellidop'] . ' ' . $perfil['apellidom']), ENT_QUOTES, 'UTF-8') ?></dd>
      </div>
      <div>
        <dt>Correo</dt>
        <dd><?= htmlspecialchars($perfil['correo'], ENT_QUOTES, 'UTF-8') ?></dd>
      </div>
      <div>
        <dt>Rol</dt>
        <dd><?= htmlspecialchars($perfil['rol'], ENT_QUOTES, 'UTF-8') ?></dd>
      </div>
      <div>
        <dt>Estado</dt>
        <dd><?= (int)$perfil['activo'] === 1 ? 'Activo' : 'Inactivo' ?></dd>
      </div>
    </dl>
  <?php else: ?>
    <p role="status">Usuario no encontrado.</p>
  <?php endif; ?>
</section>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- jQuery + DataTables + Responsive -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.datatables.net/v/dt/dt-2.1.4/r-3.0.2/datatables.min.css" rel="stylesheet"/>
<script src="https://cdn.datatables.net/v/dt/dt-2.1.4/r-3.0.2/datatables.min.js"></script>

<?php
include COMPONENTS_DIR . '/layout/footer.php';
?>
