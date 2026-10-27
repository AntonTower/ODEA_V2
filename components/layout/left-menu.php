<?php
/**
 * LEFT MENU (sidebar) dinámico por BD
 * Incluir con: include COMPONENTS_DIR . '/layout/left-menu.php';
 *
 * Requisitos:
 * - $_SESSION['ROL'] = id_rol del usuario autenticado.
 * - Tablas: iconos, menu_pagina, menu_rol.
 * - Conexión $conn (si no existe, se incluye config/conexion.php).
 *
 * Comportamiento:
 * - Escritorio (>=1024px): colapsable con persistencia localStorage.
 * - Móvil/Tablet (<1024px): off-canvas con FAB/backdrop.
 * - "is-active" automático por URL actual.
 * - Si no hay sesión o no hay permisos, se muestra un fallback a login.php
 */

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Asegurar definición de constantes de rutas
if (!defined('BASE_DIR')) {
    define('BASE_DIR', realpath(__DIR__ . '/../../'));
    define('CONFIG_DIR', BASE_DIR . '/config');
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/ODEA/gestor_evidencias');
}

$user_role = $_SESSION['ROL'] ?? null;

// Carga de conexión si no está definida
if (!isset($conn)) {
  require_once CONFIG_DIR . '/conexion.php';
}

// Consulta de páginas por rol
$pages = [];
if ($user_role !== null) {
  $sql = "SELECT
    mp.id_mp, mp.nombre_pagina, mp.archivo, ic.imagen AS svg
    FROM menu_pagina mp
    INNER JOIN menu_rol mr ON mr.id_pagina = mp.id_mp AND mr.id_rol = ?
    INNER JOIN iconos ic ON ic.id_icono = mp.id_icono
    WHERE COALESCE(mp.ocultar,0) = 0 AND COALESCE(mr.ocultar,0) = 0
    ORDER BY mp.id_mp
  ";
  if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('i', $user_role);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
      $pages[] = $row;
    }
    $stmt->close();
  }
}

// Fallback si no hay sesión o no hay páginas para el rol
$renderFallback = (empty($pages));

// Detectar ruta actual o forzarla (override) para "is-active"
$overrideActiveFile = $overrideActiveFile ?? ($LEFT_ACTIVE_OVERRIDE ?? null);
$currentFile = strtolower(
  basename(
    $overrideActiveFile ? $overrideActiveFile : (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '')
  )
);

?>
<!-- Backdrop para off-canvas en móviles -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="sidebar" id="appSidebar" aria-label="Menú lateral de la plataforma">
  <!-- Header del sidebar -->
  <div class="sidebar-header">
    <div style="display:flex; align-items:center; min-width:0;">
      <div class="brand-mini" aria-hidden="true" title="Plataforma Académica">
        <!-- Birrete -->
        <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:16px;height:16px;">
          <path d="M22 10L12 5 2 10l10 5 10-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
          <path d="M6 12v4c2 1.5 4 2 6 2s4-.5 6-2v-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <h2 class="brand-title">Menú</h2>
    </div>

    <!-- Botón SIEMPRE visible en escritorio (colapsa/expande) -->
    <button id="sidebarCollapseBtn" class="collapse-btn" type="button" title="Contraer menú" aria-label="Contraer menú" aria-pressed="false">
      <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:16px;height:16px;">
        <path d="M11 19l-7-7 7-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M20 19l-7-7 7-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>
  </div>

  <!-- Zona desplazable -->
  <div class="sidebar-scroll">
    <nav class="menu" id="sidebarMenu">
      <div class="menu-section">Navegación</div>

      <?php if (!$renderFallback): ?>
        <?php foreach ($pages as $p): 
          $archivoRuta = $p['archivo'];
          $isActive = (strtolower(basename($archivoRuta)) === $currentFile) ? ' is-active' : '';
          
          // Construcción de la URL absoluta con BASE_URL
          if (strpos($archivoRuta, 'http') === 0 || strpos($archivoRuta, '/') === 0) {
              $fullUrl = $archivoRuta;
          } else {
              $fullUrl = BASE_URL . '/' . ltrim($archivoRuta, '/');
          }
        ?>
          <a class="menu-item<?= $isActive ?>" href="<?= htmlspecialchars($fullUrl) ?>" title="<?= htmlspecialchars($p['nombre_pagina']) ?>">
            <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <!-- SVG desde BD -->
              <?= $p['svg'] ?>
            </svg>
            <span class="label"><?= htmlspecialchars($p['nombre_pagina']) ?></span>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <!-- Fallback sin sesión/permisos: mostrar Login -->
        <a class="menu-item<?= ($currentFile === 'login.php' ? ' is-active' : '') ?>" href="<?= BASE_URL ?>/public/login.php" title="Login">
          <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="7.5" cy="15.5" r="3.5" stroke="currentColor" stroke-width="1.8"/>
            <path d="M10.5 15.5H22M15 12v7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
          <span class="label">Login</span>
        </a>
      <?php endif; ?>
    </nav>
  </div>
</aside>

<!-- Botón flotante móvil para abrir/cerrar -->
<button id="sidebarFab" class="sidebar-fab" type="button" aria-label="Abrir menú">
  <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
    <path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
  </svg>
</button>

<script>
(function(){
  const mqDesktop = window.matchMedia('(min-width: 1024px)');
  const body = document.body;
  const collapseBtn = document.getElementById('sidebarCollapseBtn');
  const fab = document.getElementById('sidebarFab');
  const backdrop = document.getElementById('sidebarBackdrop');
  const menu = document.getElementById('sidebarMenu');
  const hamburger = document.getElementById('headerHamburger');

  body.classList.add('has-sidebar');
  let desktopCollapsed = false;
  try { desktopCollapsed = localStorage.getItem('sidebarCollapsed') === 'true'; } catch (e) {}

  function setOpen(open) {
    body.classList.toggle('sidebar-open', open && !mqDesktop.matches);
    [fab, hamburger].forEach(button => {
      if (!button) return;
      button.setAttribute('aria-controls', 'appSidebar');
      button.setAttribute('aria-expanded', String(open && !mqDesktop.matches));
      button.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
    });
  }

  function resizeTables() {
    requestAnimationFrame(() => {
      if (!window.jQuery || !jQuery.fn.dataTable) return;
      const tables = jQuery.fn.dataTable.tables({ visible: true, api: true });
      tables.columns.adjust();
      if (tables.responsive) tables.responsive.recalc();
    });
  }

  function setBodyState() {
    if (mqDesktop.matches) {
      body.classList.remove('sidebar-offcanvas', 'sidebar-open');
      const collapsed = desktopCollapsed;
      body.classList.toggle('sidebar-collapsed', collapsed);
      body.classList.toggle('sidebar-expanded', !collapsed);
      updateCollapseButton(collapsed);
    } else {
      body.classList.add('sidebar-offcanvas');
      body.classList.remove('sidebar-expanded', 'sidebar-collapsed');
      body.classList.remove('sidebar-open');
      updateCollapseButton(false);
    }
    setOpen(false);
    resizeTables();
  }

  function updateCollapseButton(isCollapsed) {
    const label = !mqDesktop.matches ? 'Cerrar menú' : (isCollapsed ? 'Expandir menú' : 'Contraer menú');
    collapseBtn.setAttribute('title', label);
    collapseBtn.setAttribute('aria-label', label);
    collapseBtn.setAttribute('aria-pressed', isCollapsed ? 'true' : 'false');
    collapseBtn.innerHTML = isCollapsed
      ? `<svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:16px;height:16px;">
           <path d="M13 5l7 7-7 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
           <path d="M4 5l7 7-7 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
         </svg>`
      : `<svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:16px;height:16px;">
           <path d="M11 19l-7-7 7-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
           <path d="M20 19l-7-7 7-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
         </svg>`;
  }

  function toggleCollapse(){
    if (!mqDesktop.matches) { setOpen(false); return; }
    const collapsed = body.classList.contains('sidebar-collapsed');
    body.classList.toggle('sidebar-collapsed', !collapsed);
    body.classList.toggle('sidebar-expanded', collapsed);
    desktopCollapsed = !collapsed;
    try { localStorage.setItem('sidebarCollapsed', String(!collapsed)); } catch (e) {}
    updateCollapseButton(!collapsed);
    resizeTables();
  }

  function toggleOffcanvas(){
    if (!body.classList.contains('sidebar-offcanvas')) return;
    setOpen(!body.classList.contains('sidebar-open'));
  }

  function ensureActive(){
    const path = window.location.pathname.split('/').pop().toLowerCase();
    const links = menu.querySelectorAll('a.menu-item');
    let found = false;
    links.forEach(a => {
      const href = (a.getAttribute('href') || '').split('/').pop().toLowerCase();
      if (href === path && path !== '') {
        a.classList.add('is-active');
        a.setAttribute('aria-current', 'page');
        found = true;
      }
    });
    if (!found && path === '') {
      links.forEach(a => {
        const href = (a.getAttribute('href') || '').split('/').pop().toLowerCase();
        if (href === 'index.php') {
          a.classList.add('is-active');
          a.setAttribute('aria-current', 'page');
        }
      });
    }
  }

  setBodyState();
  ensureActive();
  mqDesktop.addEventListener('change', setBodyState);

  if (collapseBtn) collapseBtn.addEventListener('click', toggleCollapse);
  if (fab) fab.addEventListener('click', toggleOffcanvas);
  if (hamburger) hamburger.addEventListener('click', toggleOffcanvas);
  if (backdrop) backdrop.addEventListener('click', () => setOpen(false));

  document.addEventListener('keydown', (e)=> {
    if (e.key === 'Escape' && body.classList.contains('sidebar-open')) {
      setOpen(false);
      if (hamburger) hamburger.focus();
    }
  });
})();
</script>