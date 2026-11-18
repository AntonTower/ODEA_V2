<?php
// Definición de constantes de rutas si no han sido cargadas previamente
if (!defined('COMPONENTS_DIR')) {
    define('BASE_DIR', realpath(__DIR__ . '/..'));
    define('COMPONENTS_DIR', BASE_DIR . '/components');
    define('CONFIG_DIR', BASE_DIR . '/config');
}

require_once __DIR__ . '/../config/paths.php';

// No requiere sesión, mostramos el header público
include COMPONENTS_DIR . '/layout/header.php';
?>

<div class="card shadow-xl" style="max-width: 480px; margin: 40px auto;">
  <div class="card-header">
    <div class="brand-box" style="margin: 0 auto 12px; width: 48px; height: 48px;">
      <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M22 10L12 5 2 10l10 5 10-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
        <path d="M6 12v4c2 1.5 4 2 6 2s4-.5 6-2v-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
    <h2 class="card-title">Registro de Docente</h2>
    <p class="card-description">Crea tu cuenta para gestionar tus clases</p>
  </div>

  <div class="card-content">
    <!-- Manejo de alertas -->
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'pending'): ?>
      <div class="badge default" role="status" style="display:block; background:#166534; color:white; text-align:center; margin-bottom:12px; padding:8px;">
        Tu solicitud quedó pendiente de activación por un administrador. Podrás iniciar sesión cuando tu cuenta sea activada.
      </div>
    <?php elseif (isset($_GET['err'])): ?>
      <div class="badge default" style="display:block; background: red; color: white; text-align:center; margin-bottom:12px; padding: 8px;">
        <?php
          $mensajes = [
            'empty' => 'Por favor completa todos los campos.',
            'invalid' => 'Revisa los datos ingresados.',
            'password' => 'Las contraseñas deben coincidir y tener entre 12 y 72 bytes.',
            'exists' => 'Este correo ya está registrado.',
            'configuration' => 'No se pudo completar el registro. Contacta al administrador.',
            'database' => 'No se pudo completar el registro. Intenta más tarde.',
            'csrf' => 'El formulario venció o no es válido. Recarga la página e inténtalo nuevamente.',
          ];
          echo htmlspecialchars($mensajes[$_GET['err']] ?? 'Ocurrió un error. Intenta nuevamente.', ENT_QUOTES, 'UTF-8');
        ?>
      </div>
    <?php endif; ?>

    <form class="space-y-4" action="<?= BASE_URL ?>/controllers/comunes/registro-save.php" method="POST" autocomplete="off" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
      <div class="form-group">
        <label class="label" for="nombre">Nombre(s)</label>
        <input class="input teacher" id="nombre" name="nombre" type="text" maxlength="100" autocomplete="given-name" required>
      </div>

      <div class="form-group">
        <label class="label" for="apellidop">Apellido paterno</label>
        <input class="input teacher" id="apellidop" name="apellidop" type="text" maxlength="100" autocomplete="family-name" required>
      </div>

      <div class="form-group">
        <label class="label" for="apellidom">Apellido materno</label>
        <input class="input teacher" id="apellidom" name="apellidom" type="text" maxlength="100">
      </div>

      <div class="form-group">
        <label class="label" for="email">Correo electrónico</label>
        <input class="input teacher" id="email" name="email" type="email" maxlength="150" placeholder="profesor@universidad.edu" autocomplete="email" required>
      </div>

      <div class="form-group">
        <label class="label" for="password">Contraseña</label>
        <input class="input teacher" id="password" name="password" type="password" minlength="12" maxlength="72" autocomplete="new-password" required>
      </div>

      <div class="form-group">
        <label class="label" for="password_confirm">Confirmar Contraseña</label>
        <input class="input teacher" id="password_confirm" name="password_confirm" type="password" minlength="12" maxlength="72" autocomplete="new-password" required>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%; background: var(--teacher-primary); border-color: var(--teacher-primary); margin-top: 24px;">
        Registrar Docente
      </button>
    </form>

    <div style="margin-top: 24px; text-align: center; border-top: 1px solid #eaeaea; padding-top: 16px;">
      <p class="card-description" style="margin-bottom: 12px; font-size: 0.9rem;">¿Ya tienes una cuenta?</p>
      <a href="login.php" class="btn" style="width:100%; display:block; text-align:center; text-decoration:none;">
        Volver al inicio de sesión
      </a>
    </div>
  </div>
</div>

<?php include COMPONENTS_DIR . '/layout/footer.php'; ?>
