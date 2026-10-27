<?php
// Definición de constantes de rutas si no han sido cargadas previamente
if (!defined('COMPONENTS_DIR')) {
    define('BASE_DIR', realpath(__DIR__ . '/..'));
    define('COMPONENTS_DIR', BASE_DIR . '/components');
    define('CONFIG_DIR', BASE_DIR . '/config');
}

// En el login NO queremos mostrar perfil; solo el header con toggle.
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
    <h2 class="card-title">ODEA — Organizador de Evidencias Académicas</h2>
    <p class="card-description">Inicia sesión</p>
  </div>

  <div class="card-content">
    <?php if (isset($_GET['err']) && $_GET['err'] == '1'): ?>
      <div class="badge default" style="display:block; background: red; color: white; text-align:center; margin-bottom:12px;">
        Credenciales inválidas. Inténtalo nuevamente.
      </div>
    <?php endif; ?>

    <form class="space-y-4" action="acceder.php" method="POST" autocomplete="on" novalidate>
      <div class="form-group">
        <label class="label" for="email">Correo electrónico</label>
        <input class="input teacher" id="email" name="email" type="email" placeholder="profesor@universidad.edu" required>
      </div>
      <div class="form-group">
        <label class="label" for="password">Contraseña</label>
        <input class="input teacher" id="password" name="password" type="password" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%; background: var(--teacher-primary); border-color: var(--teacher-primary);">
        Iniciar sesión
      </button>
    </form>

    <!-- INICIO SECCIÓN: Botón de Registro -->
    <div style="margin-top: 24px; text-align: center; border-top: 1px solid #eaeaea; padding-top: 16px;">
      <p class="card-description" style="margin-bottom: 12px; font-size: 0.9rem;">¿No tienes una cuenta en ODEA?</p>
      <a href="registro.php" class="btn" style="width:100%; display:block; text-align:center; text-decoration:none; color: var(--teacher-primary); border: 2px solid var(--teacher-primary); background: transparent; border-radius: 6px; padding: 0.5rem 1rem;">
        Registrar Usuario
      </a>
    </div>
    <!-- FIN SECCIÓN: Botón de Registro -->

  </div>
</div>

<?php include COMPONENTS_DIR . '/layout/footer.php'; ?>