<?php
require_once __DIR__ . '/../../config/sesion.php';
iniciarSesionSegura();

if (!defined('BASE_DIR')) {
    define('BASE_DIR', realpath(__DIR__ . '/../..'));
}
require_once BASE_DIR . '/config/paths.php';
require_once CONFIG_DIR . '/menu_config.php';

$user_role = (int) ($_SESSION['ROL'] ?? 0);
$currentFile = strtolower(basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ''));
$activeOverride = strtolower(basename($LEFT_ACTIVE_OVERRIDE ?? ''));
if ($activeOverride !== '') {
    $currentFile = $activeOverride;
}
?>
<style>
  /* Navegación lateral: conserva rutas, permisos, clases y estados JS actuales. */
  .sidebar {
    --sb-ease: cubic-bezier(.22, .8, .3, 1);
    --sb-radius: 12px;
    width: var(--sidebar-width, 264px);
    border-right: 1px solid var(--sidebar-border);
    background:
      linear-gradient(180deg, var(--sidebar) 0%, color-mix(in srgb, var(--sidebar) 95%, var(--sidebar-primary)) 100%);
    box-shadow: 8px 0 32px rgba(20, 42, 63, .08);
    transition: transform .32s var(--sb-ease), box-shadow .32s ease;
    -webkit-tap-highlight-color: transparent;
  }

  /* ====== Encabezado ====== */
  .sidebar .sidebar-header {
    min-height: 68px; gap: 12px; padding: 12px 16px;
    background: transparent; border-bottom: 1px solid var(--sidebar-border);
  }
  .sidebar .brand-mini {
    width: 40px; height: 40px; flex: 0 0 40px; border-radius: 12px;
    background: linear-gradient(145deg, var(--sidebar-primary), color-mix(in srgb, var(--sidebar-primary) 62%, #50a5c8));
    box-shadow: 0 6px 16px color-mix(in srgb, var(--sidebar-primary) 28%, transparent);
    transition: transform .3s var(--sb-ease), box-shadow .3s ease;
  }
  .sidebar .sidebar-header:hover .brand-mini {
    transform: rotate(-6deg) scale(1.05);
    box-shadow: 0 8px 20px color-mix(in srgb, var(--sidebar-primary) 36%, transparent);
  }
  .sidebar .brand-mini .icon { width: 20px; height: 20px; color: var(--sidebar-primary-foreground, #fff); }
  .sidebar .sidebar-header .brand-title {
    flex: 1 1 auto; min-width: 0; margin: 0;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    font-size: .95rem; font-weight: 750; letter-spacing: .06em;
  }
  .sidebar .collapse-btn {
    width: 38px; height: 38px; flex: 0 0 38px; display: grid; place-items: center; padding: 0;
    border: 1px solid var(--sidebar-border); border-radius: 10px;
    background: var(--sidebar); color: var(--sidebar-foreground); cursor: pointer;
    transition: background-color .18s ease, border-color .18s ease, color .18s ease, transform .18s ease;
  }
  .sidebar .collapse-btn .icon { transition: transform .32s var(--sb-ease); }
  .sidebar .collapse-btn:hover { background: var(--sidebar-accent); border-color: var(--sidebar-primary); }
  .sidebar .collapse-btn:active { transform: scale(.92); }
  body.sidebar-collapsed .sidebar .collapse-btn .icon { transform: rotate(180deg); }
  .sidebar .collapse-btn:focus-visible, .sidebar .menu-item:focus-visible {
    outline: 3px solid color-mix(in srgb, var(--sidebar-ring) 48%, transparent); outline-offset: 2px;
  }

  /* ====== Zona con scroll ====== */
  .sidebar .sidebar-scroll {
    padding: 18px 12px max(18px, env(safe-area-inset-bottom));
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: color-mix(in srgb, var(--sidebar-foreground) 22%, transparent) transparent;
  }
  .sidebar .sidebar-scroll::-webkit-scrollbar { width: 6px; }
  .sidebar .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
  .sidebar .sidebar-scroll::-webkit-scrollbar-thumb {
    border-radius: 6px;
    background: color-mix(in srgb, var(--sidebar-foreground) 22%, transparent);
  }
  .sidebar .menu { gap: 6px; margin: 0; }

  /* ====== Título de sección ====== */
  .sidebar .menu-section {
    display: flex; align-items: center; gap: 10px;
    padding: 4px 12px 12px; color: var(--muted-foreground);
    font-size: .68rem; font-weight: 750; letter-spacing: .12em; text-transform: uppercase;
  }
  .sidebar .menu-section::after {
    content: ""; flex: 1 1 auto; height: 1px; background: var(--sidebar-border);
  }

  /* ====== Elementos del menú ====== */
  .sidebar .menu-item {
    position: relative; display: flex; align-items: center;
    min-height: 46px; gap: 12px; padding: 7px 12px 7px 8px; overflow: hidden;
    border: 1px solid transparent; border-radius: var(--sb-radius);
    color: var(--sidebar-foreground); text-decoration: none;
    transition: background-color .18s ease, border-color .18s ease, color .18s ease,
                transform .22s var(--sb-ease), box-shadow .22s ease;
  }
  .sidebar .menu-item .icon {
    box-sizing: content-box; width: 20px; height: 20px; flex: 0 0 20px; padding: 6px;
    border-radius: 10px;
    background: color-mix(in srgb, var(--sidebar-primary) 9%, transparent);
    color: color-mix(in srgb, var(--sidebar-foreground) 78%, var(--sidebar-primary));
    transition: background-color .2s ease, color .2s ease, transform .25s var(--sb-ease), box-shadow .2s ease;
  }
  .sidebar .menu-item .label {
    min-width: 0; flex: 1 1 auto;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    font-size: .88rem; font-weight: 560; letter-spacing: .005em;
  }
  /* Flecha que aparece al pasar el cursor */
  .sidebar .menu-item::after {
    content: ""; flex: 0 0 auto; width: 7px; height: 7px; margin-left: auto;
    border-top: 2px solid currentColor; border-right: 2px solid currentColor;
    opacity: 0; transform: rotate(45deg) translate(-5px, 5px);
    transition: opacity .2s ease, transform .25s var(--sb-ease);
  }
  .sidebar .menu-item:hover {
    background: var(--sidebar-accent);
    border-color: color-mix(in srgb, var(--sidebar-primary) 22%, var(--sidebar-border));
    transform: translateX(3px);
  }
  .sidebar .menu-item:hover .icon {
    background: color-mix(in srgb, var(--sidebar-primary) 18%, transparent);
    color: var(--sidebar-primary);
    transform: scale(1.08);
  }
  .sidebar .menu-item:hover::after { opacity: .5; transform: rotate(45deg) translate(0, 0); }
  .sidebar .menu-item:active { transform: translateX(1px) scale(.985); }

  /* Elemento activo */
  .sidebar .menu-item.is-active {
    background: linear-gradient(90deg,
      color-mix(in srgb, var(--sidebar-primary) 16%, var(--sidebar)),
      color-mix(in srgb, var(--sidebar-primary) 7%, var(--sidebar)));
    border-color: color-mix(in srgb, var(--sidebar-primary) 30%, var(--sidebar-border));
    color: var(--sidebar-accent-foreground);
    box-shadow: 0 4px 14px color-mix(in srgb, var(--sidebar-primary) 12%, transparent);
  }
  .sidebar .menu-item.is-active::before {
    position: absolute; inset-block: 10px; left: 0; width: 3px; border-radius: 0 4px 4px 0;
    background: var(--sidebar-primary); content: "";
  }
  .sidebar .menu-item.is-active .icon {
    background: var(--sidebar-primary);
    color: var(--sidebar-primary-foreground, #fff);
    box-shadow: 0 4px 10px color-mix(in srgb, var(--sidebar-primary) 35%, transparent);
  }
  .sidebar .menu-item.is-active .label { font-weight: 680; }
  .sidebar .menu-item.is-active::after { opacity: 0; }
  body.sidebar-collapsed .sidebar .menu-item::after { display: none; }

  /* ====== Fondo oscuro (móvil) ====== */
  .sidebar-backdrop {
    border: 0; padding: 0; cursor: pointer;
    background: rgba(8, 20, 32, .55);
    -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px);
    -webkit-tap-highlight-color: transparent;
  }
  body.sidebar-open .sidebar-backdrop { animation: sbFade .25s ease both; }
  body.sidebar-open { overflow: hidden; }

  @keyframes sbFade { from { opacity: 0; } to { opacity: 1; } }
  @keyframes sbItemIn {
    from { opacity: 0; transform: translateX(-14px); }
    to   { opacity: 1; transform: none; }
  }

  /* ====== Tablet y móvil ====== */
  @media (max-width: 1023.98px) {
    .sidebar {
      width: min(300px, calc(100vw - 44px)); max-width: calc(100vw - 44px);
      border-radius: 0 20px 20px 0;
      padding-left: env(safe-area-inset-left, 0px);
    }
    body.sidebar-open .sidebar { box-shadow: 14px 0 48px rgba(8, 20, 32, .32); }
    .sidebar .sidebar-header {
      min-height: 64px;
      padding-top: calc(12px + env(safe-area-inset-top, 0px));
    }
    .sidebar .menu-item { min-height: 48px; }
    body.has-sidebar > main.container,
    body.has-sidebar > .app-header { width: 100%; max-width: 100%; margin-left: 0; margin-right: 0; }

    /* Entrada escalonada de los elementos al abrir el menú */
    body.sidebar-open .sidebar .menu > * { animation: sbItemIn .38s var(--sb-ease) both; }
    body.sidebar-open .sidebar .menu > :nth-child(1) { animation-delay: .03s; }
    body.sidebar-open .sidebar .menu > :nth-child(2) { animation-delay: .06s; }
    body.sidebar-open .sidebar .menu > :nth-child(3) { animation-delay: .09s; }
    body.sidebar-open .sidebar .menu > :nth-child(4) { animation-delay: .12s; }
    body.sidebar-open .sidebar .menu > :nth-child(5) { animation-delay: .15s; }
    body.sidebar-open .sidebar .menu > :nth-child(6) { animation-delay: .18s; }
    body.sidebar-open .sidebar .menu > :nth-child(7) { animation-delay: .21s; }
    body.sidebar-open .sidebar .menu > :nth-child(8) { animation-delay: .24s; }
    body.sidebar-open .sidebar .menu > :nth-child(9) { animation-delay: .27s; }
    body.sidebar-open .sidebar .menu > :nth-child(n+10) { animation-delay: .3s; }
  }

  /* Pantallas táctiles: sin efectos de hover "pegados" */
  @media (hover: none) {
    .sidebar .menu-item:hover { transform: none; background: transparent; border-color: transparent; }
    .sidebar .menu-item:hover .icon { transform: none; }
    .sidebar .menu-item:hover::after { opacity: 0; }
    .sidebar .menu-item.is-active:hover {
      background: linear-gradient(90deg,
        color-mix(in srgb, var(--sidebar-primary) 16%, var(--sidebar)),
        color-mix(in srgb, var(--sidebar-primary) 7%, var(--sidebar)));
      border-color: color-mix(in srgb, var(--sidebar-primary) 30%, var(--sidebar-border));
    }
    .sidebar .menu-item:active { background: var(--sidebar-accent); transform: scale(.97); }
  }

  /* Móviles pequeños */
  @media (max-width: 430px) {
    .sidebar { width: min(288px, calc(100vw - 36px)); max-width: calc(100vw - 36px); }
    .sidebar .sidebar-scroll { padding-inline: 10px; }
    .sidebar .sidebar-header { padding-inline: 14px; }
  }
  @media (max-width: 359px) {
    .sidebar { width: min(272px, calc(100vw - 28px)); max-width: calc(100vw - 28px); }
    .sidebar .menu-item { gap: 10px; }
    .sidebar .menu-item .label { font-size: .84rem; }
  }

  /* Móvil en horizontal / pantallas bajas */
  @media (max-height: 520px) and (orientation: landscape) {
    .sidebar .sidebar-header { min-height: 56px; padding-block: 8px; }
    .sidebar .sidebar-scroll { padding-block: 10px; }
    .sidebar .menu-item { min-height: 42px; padding-block: 5px; }
    .sidebar .menu-item .icon { padding: 5px; }
    .sidebar .menu-section { padding-bottom: 8px; }
  }

  /* Respeta la preferencia de reducir animaciones */
  @media (prefers-reduced-motion: reduce) {
    .sidebar, .sidebar *, .sidebar-backdrop { transition-duration: .01ms !important; animation: none !important; }
  }
</style>
<aside class="sidebar" id="appSidebar" aria-label="Menú principal">
  <div class="sidebar-header">
    <div class="brand-mini" aria-hidden="true">
      <svg class="icon" viewBox="0 0 24 24" fill="none">
        <path d="M22 10L12 5 2 10l10 5 10-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
        <path d="M6 12v4c2 1.5 4 2 6 2s4-.5 6-2v-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
    <span class="brand-title">ODEA</span>
    <button id="sidebarCollapseBtn" class="collapse-btn" type="button" title="Contraer menú" aria-label="Contraer menú" aria-expanded="true">
      <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:16px;height:16px">
        <path d="M11 19l-7-7 7-7M20 19l-7-7 7-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>
  </div>

  <div class="sidebar-scroll">
    <nav class="menu" id="sidebarMenu">
      <div class="menu-section">Navegación</div>
      <?php if ($user_role > 0): ?>
        <?php foreach ($MENU_CONFIG as $item): ?>
          <?php if (in_array($user_role, $item['roles'], true)):
              $itemFile = strtolower(basename($item['ruta']));
              $isActive = $itemFile === $currentFile ? ' is-active' : '';
          ?>
            <?php if (($item['seccion'] ?? '') === 'gestion_academica' && $item['titulo'] === 'Convocatorias'): ?>
            <div class="menu-section">Gesti&oacute;n Acad&eacute;mica</div>
          <?php elseif ($itemFile === 'evaluacion.php'): ?>
            <div class="menu-section">Navegaci&oacute;n</div>
          <?php endif; ?>
          <a class="menu-item<?= $isActive ?>" href="<?= htmlspecialchars(BASE_URL . '/' . ltrim($item['ruta'], '/'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <?= $item['icono'] ?>
              </svg>
              <span class="label"><?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php else: ?>
        <a class="menu-item" href="<?= htmlspecialchars(BASE_URL . '/public/login.php', ENT_QUOTES, 'UTF-8') ?>">
          <span class="label">Iniciar sesión</span>
        </a>
      <?php endif; ?>
    </nav>
  </div>
</aside>
<button class="sidebar-backdrop" id="sidebarBackdrop" type="button" aria-label="Cerrar menú"></button>

<script>
(function () {
  const body = document.body;
  const sidebar = document.getElementById('appSidebar');
  const collapseButton = document.getElementById('sidebarCollapseBtn');
  const hamburger = document.getElementById('headerHamburger');
  const backdrop = document.getElementById('sidebarBackdrop');
  if (!body || !sidebar) return;

  const desktop = window.matchMedia('(min-width: 1024px)');
  let collapsed = false;
  try { collapsed = localStorage.getItem('odeaSidebarCollapsed') === 'true'; } catch (e) {}

  function render() {
    body.classList.add('has-sidebar');
    body.classList.toggle('sidebar-collapsed', desktop.matches && collapsed);
    body.classList.toggle('sidebar-expanded', desktop.matches && !collapsed);
    if (collapseButton) {
      collapseButton.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
      collapseButton.setAttribute('title', collapsed ? 'Expandir menú' : 'Contraer menú');
      collapseButton.setAttribute('aria-label', collapsed ? 'Expandir menú' : 'Contraer menú');
    }
    if (hamburger) hamburger.setAttribute('aria-expanded', body.classList.contains('sidebar-open') ? 'true' : 'false');
  }
  function closeMobile() { body.classList.remove('sidebar-open'); render(); }

  render();
  if (hamburger) hamburger.setAttribute('aria-controls', 'appSidebar');

  if (collapseButton) collapseButton.addEventListener('click', function () {
    collapsed = !collapsed;
    try { localStorage.setItem('odeaSidebarCollapsed', String(collapsed)); } catch (e) {}
    render();
  });
  if (hamburger) hamburger.addEventListener('click', function () {
    body.classList.toggle('sidebar-open');
    render();
  });
  if (backdrop) backdrop.addEventListener('click', closeMobile);
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      const wasOpen = body.classList.contains('sidebar-open');
      closeMobile();
      if (wasOpen && hamburger) hamburger.focus();
    }
  });
  if (desktop.addEventListener) desktop.addEventListener('change', closeMobile);
  else if (desktop.addListener) desktop.addListener(closeMobile);

  /* Mejoras de interacción (no alteran rutas ni permisos) */

  // Al elegir una opción en móvil, el menú se cierra
  sidebar.addEventListener('click', function (event) {
    const link = event.target.closest ? event.target.closest('.menu-item') : null;
    if (link && !desktop.matches) closeMobile();
  });

  // Deslizar hacia la izquierda cierra el menú en móvil
  let touchX = null, touchY = null;
  sidebar.addEventListener('touchstart', function (event) {
    const t = event.touches[0];
    touchX = t.clientX; touchY = t.clientY;
  }, { passive: true });
  sidebar.addEventListener('touchmove', function (event) {
    if (touchX === null || desktop.matches || !body.classList.contains('sidebar-open')) return;
    const t = event.touches[0];
    const dx = t.clientX - touchX;
    const dy = Math.abs(t.clientY - touchY);
    if (dx < -70 && dy < 40) { touchX = null; closeMobile(); }
  }, { passive: true });
  sidebar.addEventListener('touchend', function () { touchX = null; touchY = null; }, { passive: true });

  // Muestra el elemento activo dentro de la zona con scroll
  const scroller = sidebar.querySelector('.sidebar-scroll');
  const active = sidebar.querySelector('.menu-item.is-active');
  if (scroller && active) {
    const top = active.offsetTop - scroller.clientHeight / 2 + active.offsetHeight / 2;
    if (top > 0) scroller.scrollTop = top;
  }
})();
</script>
