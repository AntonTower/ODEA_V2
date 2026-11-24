<?php
require_once __DIR__ . '/../../config/paths.php';
require_once CONFIG_DIR . '/validacion.php';
require_once CONFIG_DIR . '/conexion.php';
require_once CONFIG_DIR . '/seguridad.php';
require_once CONFIG_DIR . '/validacion-permiso.php';

$usuario = seguridadUsuarioActual($conn);
if (!$usuario['id_usuario']) {
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}
$datosPerfil = [
    'Nombre' => trim($_SESSION['SESUSUARIO'] . ' ' . $_SESSION['APELLIDO']),
    'Correo' => $_SESSION['EMAIL'],
    'Rol' => $_SESSION['RNOMBRE'],
];
include COMPONENTS_DIR . '/layout/header.php';
include COMPONENTS_DIR . '/layout/left-menu.php';
?>
<section class="space-y-6 common-profile-page" style="width:min(100%,1040px);margin-inline:auto">
  <header>
    <h1 class="text-2xl font-semibold">Perfil</h1>
  </header>
  <dl class="card" style="display:grid;gap:12px;padding:20px">
    <?php foreach ($datosPerfil as $etiqueta => $valor): ?>
      <div>
        <dt><?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?></dt>
        <dd><?= htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') ?></dd>
      </div>
    <?php endforeach; ?>
  </dl>
</section>
<?php include COMPONENTS_DIR . '/layout/footer.php'; ?>
