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
?>

<section class="space-y-6">
  <header class="flex items-center justify-between" style="display:flex; align-items:center; justify-content:space-between; gap:16px;">
    <div>
      <h1 class="text-2xl font-semibold">Perfil</h1>
      <p class="text-muted-foreground">Administra los datos de tu perfil</p>
    </div>
  </header>

<!-- SweetAlert2 (opcional para feedback bonito) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- jQuery + DataTables + Responsive -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link  href="https://cdn.datatables.net/v/dt/dt-2.1.4/r-3.0.2/datatables.min.css" rel="stylesheet"/>
<script src="https://cdn.datatables.net/v/dt/dt-2.1.4/r-3.0.2/datatables.min.js"></script>

<script>
</script>

<?php include COMPONENTS_DIR . '/layout/footer.php'; ?>