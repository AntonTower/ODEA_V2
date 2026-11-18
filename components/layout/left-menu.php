<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

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
    if (event.key === 'Escape') closeMobile();
  });
  if (desktop.addEventListener) desktop.addEventListener('change', closeMobile);
  else if (desktop.addListener) desktop.addListener(closeMobile);
})();
</script>
