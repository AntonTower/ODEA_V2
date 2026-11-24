<?php
// Header global (roles reales: Administrador, Docente, Evaluador, Super Usuario)
// - Usa variables de sesión. Si no hay sesión, no muestra dropdown de usuario.
// - Subtítulo: "Panel de <Rol>" según RNOMBRE.
// - En desktop: perfil compacto (solo avatar) + botón de tema + cerrar sesión en el dropdown.
// - En móvil: hamburguesa para abrir el left-menu + avatar con dropdown (perfil, tema, cerrar sesión).

require_once __DIR__ . '/../../config/csrf.php';
iniciarSesionSegura();

if (!defined('BASE_DIR')) {
  define('BASE_DIR', realpath(__DIR__ . '/../..'));
}
require_once BASE_DIR . '/config/paths.php';

$loggedIn = isset($_SESSION['SESUSUARIO']);

// Normaliza el nombre del rol desde la sesión
$roleNameRaw = $_SESSION['RNOMBRE'] ?? '';
$roleName = mb_strtolower(trim($roleNameRaw), 'UTF-8');

// Determina el subtítulo visible bajo el título
$panelLabel = 'Autenticación';
if ($loggedIn) {
  if ($roleName === 'administrador') {
    $panelLabel = 'Panel de Administrador';
  } elseif ($roleName === 'docente') {
    $panelLabel = 'Panel de Docente';
  } elseif ($roleName === 'evaluador') {
    $panelLabel = 'Panel de Evaluador';
  } elseif ($roleName === 'super usuario' || $roleName === 'superusuario' || $roleName === 'super_user') {
    $panelLabel = 'Panel de Super Usuario';
  } else {
    $panelLabel = 'Panel';
  }
}

// Tema / fondo del <body> (puedes diferenciar por rol más adelante)
$bodyClass = 'teacher-theme teacher-gradient-bg';

// Datos de usuario para avatar
$fullName = trim(($_SESSION['SESUSUARIO'] ?? '') . ' ' . ($_SESSION['APELLIDO'] ?? ''));
$parts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY);
$initials = '';
if (!empty($parts)) {
  $initials .= mb_strtoupper(mb_substr($parts[0], 0, 1, 'UTF-8'));
  if (count($parts) > 1) {
    $initials .= mb_strtoupper(mb_substr(end($parts), 0, 1, 'UTF-8'));
  }
}
$initials = $initials ?: 'US';
$emailUser = $_SESSION['EMAIL'] ?? '';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
  <title>ODEA - Plataforma Académica</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/styles/global.css">
  <link rel="shortcut icon" href="<?= BASE_URL ?>/assets/icons/favicon.ico">
  <style>
    /* Estilos locales del header; la interacción sigue en sus scripts actuales. */
    .app-header .header-inner { min-width: 0; min-height: 68px; padding-block: 10px; gap: 16px; }
    .app-header .header-left, .app-header .header-right { min-width: 0; }
    .app-header .header-left { flex: 1 1 auto; }
    .app-header .brand { flex: 0 1 auto; }
    .app-header .brand-box {
      width: 40px; height: 40px; border-radius: 12px;
      background: linear-gradient(145deg, var(--primary), color-mix(in srgb, var(--primary) 68%, #50a5c8));
      box-shadow: 0 5px 14px color-mix(in srgb, var(--primary) 20%, transparent);
    }
    .app-header .brand-box .icon { width: 21px; height: 21px; }
    .app-header .brand-title { font-size: 1.08rem; font-weight: 750; letter-spacing: .015em; }
    .app-header .brand-subtitle { margin-top: 3px; font-size: .75rem; letter-spacing: .01em; }
    .app-header .header-right { flex: 0 0 auto; gap: 12px; }
    .app-header #themeToggle { min-height: 40px; }
    .app-header .header-user-summary {
      display: grid; min-width: 0; max-width: 220px; gap: 2px;
      padding-inline: 4px 8px; text-align: right;
    }
    .app-header .header-user-name, .app-header .header-user-email {
      overflow: hidden; margin: 0; text-overflow: ellipsis; white-space: nowrap;
    }
    .app-header .header-user-name { color: var(--foreground); font-size: .82rem; font-weight: 650; }
    .app-header .header-user-email { color: var(--muted-foreground); font-size: .72rem; }
    .app-header .avatar-button {
      width: 42px; height: 42px; border: 2px solid color-mix(in srgb, var(--primary) 24%, var(--border));
      background: var(--accent); color: var(--accent-foreground); font-weight: 700;
      box-shadow: 0 2px 8px rgba(18, 42, 64, .08);
    }
    .app-header .avatar-button:hover, .app-header .avatar-button[aria-expanded="true"] {
      border-color: var(--primary); box-shadow: 0 0 0 4px color-mix(in srgb, var(--ring) 14%, transparent);
    }
    .app-header .dropdown-menu { width: min(320px, calc(100vw - 24px)); padding: 10px; border-radius: 14px; }
    .app-header .dropdown-header { min-width: 0; padding: 7px; }
    .app-header .dropdown-header .profile-text { min-width: 0; }
    .app-header .dropdown-header .profile-name, .app-header .dropdown-header .profile-email { overflow-wrap: anywhere; }
    .app-header .dropdown-item { min-height: 42px; border-radius: 9px; }
    .app-header .dropdown-item .icon { flex: 0 0 18px; width: 18px; height: 18px; }
    .app-header .logout-form { width: 100%; }
    .app-header .logout-form button { text-align: left; }
    @media (max-width: 1199.98px) { .app-header .header-user-summary { display: none; } }
    @media (max-width: 1023.98px) {
      .app-header .header-inner { min-height: 62px; }
      .app-header .header-left { gap: 10px; }
      .app-header #headerHamburger { flex: 0 0 40px; }
    }
    @media (max-width: 430px) {
      .app-header .header-inner { gap: 8px; padding-block: 8px; }
      .app-header .header-left, .app-header .header-right { gap: 7px; }
      .app-header .brand { gap: 8px; }
      .app-header .brand-box { width: 36px; height: 36px; flex-basis: 36px; }
      .app-header .brand-title { font-size: 1rem; }
      .app-header .brand-subtitle { max-width: 42vw; }
      .app-header .avatar-button { width: 38px; height: 38px; }
    }
    @media (max-width: 359px) {
      .app-header .brand-subtitle { display: none; }
      .app-header .brand-box { width: 34px; height: 34px; flex-basis: 34px; }
      .app-header #headerHamburger { flex-basis: 38px; min-width: 38px; padding: 7px; }
      .app-header .avatar-button { width: 36px; height: 36px; }
    }
  </style>
  <script>
  (function(){
    const originalFetch = window.fetch.bind(window);
    const csrfMethods = new Set(['POST', 'PUT', 'PATCH', 'DELETE']);
    window.fetch = function(input, init){
      const options = init ? { ...init } : {};
      const requestMethod = options.method || (input instanceof Request ? input.method : 'GET');
      const method = String(requestMethod).toUpperCase();
      if (csrfMethods.has(method)) {
        try {
          const url = new URL(input instanceof Request ? input.url : input, window.location.href);
          if (url.origin === window.location.origin) {
            const headers = new Headers(options.headers || (input instanceof Request ? input.headers : undefined));
            if (!headers.has('X-CSRF-Token')) {
              const meta = document.querySelector('meta[name="csrf-token"]');
              if (meta) headers.set('X-CSRF-Token', meta.content);
            }
            options.headers = headers;
          }
        } catch (_) {}
      }
      return originalFetch(input, options);
    };
  })();
  </script>
</head>
<body class="page <?= htmlspecialchars($bodyClass) ?>">
  <header class="app-header">
    <div class="header-inner container header-bar">
      <!-- IZQUIERDA: Botón hamburguesa (móvil) + Marca -->
      <div class="header-left">
        <?php if ($loggedIn): ?>
          <button id="headerHamburger" class="btn-icon only-mobile" type="button" aria-label="Abrir menú">
            <!-- Hamburguesa -->
            <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
          </button>
        <?php endif; ?>

        <div class="brand">
          <div class="brand-box" aria-hidden="true" title="Plataforma Académica">
            <!-- Birrete -->
            <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M22 10L12 5 2 10l10 5 10-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
              <path d="M6 12v4c2 1.5 4 2 6 2s4-.5 6-2v-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="brand-text">
            <h1 class="brand-title" title="Plataforma Académica">ODEA</h1>
            <p class="brand-subtitle"><?= htmlspecialchars($panelLabel) ?></p>
          </div>
        </div>
      </div>

      <!-- DERECHA: Tema (desktop) + Avatar con dropdown -->
      <div class="header-right">
        <!-- Botón de tema visible en desktop -->
        <button id="themeToggle" class="btn hide-on-mobile" type="button" aria-pressed="false">
          <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span class="btn-text">Oscuro</span>
        </button>

        <?php if ($loggedIn): ?>
          <!-- Dropdown de usuario (desktop y móvil) -->
          <div class="header-user-summary" aria-label="Usuario actual">
            <p class="header-user-name"><?= htmlspecialchars($fullName ?: 'Usuario') ?></p>
            <p class="header-user-email"><?= htmlspecialchars($emailUser) ?></p>
          </div>
          <div class=" dropdown dropdown-right">
            <button id="profileBtn" class="avatar avatar-button" aria-haspopup="menu" aria-expanded="false" aria-controls="profileMenu">
              <?= htmlspecialchars($initials) ?>
            </button>
            <div id="profileMenu" class="dropdown-menu dropdown-right" role="menu" aria-labelledby="profileBtn">
              <div class="dropdown-header">
                <div class="avatar" aria-hidden="true"><?= htmlspecialchars($initials) ?></div>
                <div class="profile-text">
                  <p class="profile-name"><?= htmlspecialchars($fullName ?: 'Usuario') ?></p>
                  <p class="profile-email"><?= htmlspecialchars($emailUser) ?></p>
                </div>
              </div>

              <div class="dropdown-sep"></div>

              <!-- Toggle tema (también disponible dentro del menú; útil en móvil) -->
              <button id="themeToggleInMenu" class="dropdown-item" type="button">
                <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Cambiar tema</span>
              </button>

              <a class="dropdown-item" href="<?= BASE_URL ?>/views/comunes/perfil.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Z" stroke="currentColor" stroke-width="1.8"/>
                  <path d="M3 21a9 9 0 0 1 18 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                <span>Mi Perfil</span>
              </a>

              <div class="dropdown-sep"></div>

              <form method="POST" action="<?= BASE_URL ?>/public/logout.php" class="logout-form" style="margin:0">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <button class="dropdown-item text-danger" type="submit">
                <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                  <path d="M16 17l5-5-5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M21 12H9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                <span>Cerrar Sesión</span>
                </button>
              </form>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="container">
<script>
// ===== Modo oscuro persistente =====
(function(){
  const htmlEl = document.documentElement;
  const saved = localStorage.getItem('darkMode');
  let isDark = false;
  if (saved !== null) { try { isDark = JSON.parse(saved); } catch(e){} }

  function renderToggle(btn, enabled){
    if (!btn) return;
    btn.setAttribute('aria-pressed', enabled ? 'true' : 'false');
    if (btn.id === 'themeToggle') {
      btn.innerHTML = enabled
        ? `<svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
             <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8"/>
             <path d="M12 2v2m0 16v2M2 12h2m16 0h2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
           </svg><span class="btn-text">Claro</span>`
        : `<svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
             <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
           </svg><span class="btn-text">Oscuro</span>`;
    }
  }
  function setDark(enabled){
    htmlEl.classList.toggle('dark', enabled);
    localStorage.setItem('darkMode', JSON.stringify(enabled));
    renderToggle(document.getElementById('themeToggle'), enabled);
  }

  // Init
  setDark(isDark);

  addEventListener('DOMContentLoaded', function(){
    const deskBtn = document.getElementById('themeToggle');
    const menuBtn = document.getElementById('themeToggleInMenu');

    if (deskBtn) deskBtn.addEventListener('click', function(){
      setDark(!document.documentElement.classList.contains('dark'));
    });
    if (menuBtn) menuBtn.addEventListener('click', function(){
      setDark(!document.documentElement.classList.contains('dark'));
      closeProfileMenu();
    });
  });
})();

// ===== Dropdown perfil (desktop/móvil) =====
(function(){
  const btn  = document.getElementById('profileBtn');
  const menu = document.getElementById('profileMenu');
  if (!btn || !menu) return;

  function toggleMenu(){
    const expanded = btn.getAttribute('aria-expanded') === 'true';
    btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    menu.classList.toggle('open', !expanded);
  }
  function closeOnOutside(e){
    if (!menu.classList.contains('open')) return;
    if (!menu.contains(e.target) && e.target !== btn) {
      btn.setAttribute('aria-expanded', 'false');
      menu.classList.remove('open');
    }
  }
  function closeOnEsc(e){
    if (e.key === 'Escape' && menu.classList.contains('open')) {
      btn.setAttribute('aria-expanded', 'false');
      menu.classList.remove('open');
    }
  }
  btn.addEventListener('click', toggleMenu);
  document.addEventListener('click', closeOnOutside);
  document.addEventListener('keydown', closeOnEsc);

  // Exponer utilitario global
  window.closeProfileMenu = function(){
    btn.setAttribute('aria-expanded', 'false');
    menu.classList.remove('open');
  };
})();

// La hamburguesa se conecta en left-menu.php para compartir estado y breakpoint.
</script>
