<?php
// Definición de constantes de rutas si no han sido cargadas previamente
if (!defined('COMPONENTS_DIR')) {
    define('BASE_DIR', realpath(__DIR__ . '/..'));
    define('COMPONENTS_DIR', BASE_DIR . '/components');
    define('CONFIG_DIR', BASE_DIR . '/config');
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/ODEA/gestor_evidencias');
}

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
    <?php if (isset($_GET['err'])): ?>
      <div class="badge default" style="display:block; background: red; color: white; text-align:center; margin-bottom:12px; padding: 8px;">
        <?php 
          if($_GET['err'] == 'empty') echo "Por favor llena todos los campos.";
          elseif($_GET['err'] == 'pass') echo "Las contraseñas no coinciden.";
          elseif($_GET['err'] == 'exists') echo "Este correo ya está registrado.";
          else echo "Ocurrió un error. Intenta nuevamente.";
        ?>
      </div>
    <?php endif; ?>

    <form class="space-y-4" action="<?= BASE_URL ?>/controllers/comunes/registro-save.php" method="POST" autocomplete="off" novalidate>
      <div class="form-group">
        <label class="label" for="nombre">Nombre Completo</label>
        <input class="input teacher" id="nombre" name="nombre" type="text" placeholder="Ej. Dr. Carlos Mendoza" required>
      </div>

      <div class="form-group">
        <label class="label" for="email">Correo electrónico</label>
        <input class="input teacher" id="email" name="email" type="email" placeholder="profesor@universidad.edu" required>
      </div>

      <div class="form-group">
        <label class="label" for="password">Contraseña</label>
        <input class="input teacher" id="password" name="password" type="password" placeholder="••••••••" required>
      </div>

      <div class="form-group">
        <label class="label" for="password_confirm">Confirmar Contraseña</label>
        <input class="input teacher" id="password_confirm" name="password_confirm" type="password" placeholder="••••••••" required>
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