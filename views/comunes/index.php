<?php
require_once __DIR__.'/../../config/paths.php';
require_once CONFIG_DIR.'/validacion.php';
require_once CONFIG_DIR.'/conexion.php';
require_once CONFIG_DIR.'/seguridad.php';
require_once CONFIG_DIR.'/tableros.php';

$usuarioActual=seguridadUsuarioActual($conn);
if((int)$usuarioActual['id_usuario']<=0){http_response_code(401);exit('Sesión no válida.');}

$rolActual=(int)$usuarioActual['rol'];
$esPanelAdmin=in_array($rolActual,[1,2],true);

include COMPONENTS_DIR.'/layout/header.php';
include COMPONENTS_DIR.'/layout/left-menu.php';
?>
<style>
  .common-access-state { max-width: 720px; margin: clamp(18px, 5vh, 48px) auto; text-align: center; }
  .common-access-state .card-content { padding: clamp(20px, 4vw, 36px); color: var(--muted-foreground); }
</style>
<?php if ($esPanelAdmin): ?>
<style>
  /* =========================================================================
     Dashboard de administración (roles 1 y 2)
     - El layout responde al ANCHO DEL CONTENEDOR (container queries), no al del
       viewport: así se adapta solo cuando el menú lateral se abre o se colapsa.
     - Por defecto es de 1 columna (móvil); las columnas se agregan al crecer.
     ========================================================================= */

  .admin-dashboard {
    --ad-gap: clamp(12px, 1.6vw, 24px);
    --ad-pad: clamp(14px, 1.5vw, 20px);
    --ad-line: var(--border, #e4e4e7);
    --ad-surface: var(--card, #ffffff);
    --ad-muted: var(--muted-foreground, #71717a);
    --ad-accent: var(--primary, #2563eb);
    --ad-radius: var(--radius, 12px);
    --ad-radius-sm: var(--radius-sm, 8px);
    --ad-shadow: 0 1px 2px rgba(0, 0, 0, .05);

    width: 100%;
    min-width: 0;
    container-type: inline-size;
    container-name: admin-dash;
  }

  /* ---------- Rejilla principal: 12 columnas, una sola fila por bloque ---------- */
  .admin-dashboard .dashboard-view-admin {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    align-content: start;
    gap: var(--ad-gap);
    width: 100%;
    max-width: 1680px;
    min-width: 0;
    margin-inline: auto;
  }
  /* Todo bloque ocupa una fila completa, salvo que se indique lo contrario */
  .admin-dashboard .dashboard-view-admin > * { grid-column: 1 / -1; min-width: 0; }

  /* El div agrupador no genera caja: sus secciones se reparten en la rejilla */
  .admin-dashboard .dashboard-view-admin > .dashboard-two-column { display: contents; }
  .admin-dashboard .dashboard-view-admin > .dashboard-two-column > * { grid-column: 1 / -1; min-width: 0; }

  /* ---------- Encabezado ---------- */
  .admin-dashboard .dashboard-view-admin .dashboard-hero {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px 20px;
    margin: 0;
    padding: 4px 0 6px;
  }
  .admin-dashboard .dashboard-view-admin .dashboard-hero > :not(.btn) { flex: 1 1 280px; min-width: 0; }
  .admin-dashboard .dashboard-view-admin .dashboard-hero h1 {
    margin: 0 0 4px;
    font-size: clamp(1.35rem, 1.1rem + 1vw, 1.85rem);
    line-height: 1.2;
    letter-spacing: -.02em;
    text-wrap: balance;
  }
  .admin-dashboard .dashboard-view-admin .dashboard-hero .dashboard-eyebrow {
    margin: 0 0 4px;
    font-size: .75rem;
    font-weight: 600;
    color: var(--ad-accent);
  }
  .admin-dashboard .dashboard-view-admin .dashboard-hero .text-muted-foreground {
    max-width: 64ch;
    margin: 0;
    font-size: .9rem;
    line-height: 1.5;
    color: var(--ad-muted);
  }
  .admin-dashboard .dashboard-view-admin .dashboard-hero > .btn {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    padding: 8px 16px;
    border: 1px solid var(--ad-line);
    border-radius: var(--ad-radius-sm);
    background: var(--ad-surface);
    color: var(--ad-accent);
    box-shadow: none;
  }

  /* ---------- Indicadores (KPI) ---------- */
  .admin-dashboard .dashboard-view-admin .dashboard-kpis {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: clamp(8px, 1vw, 14px);
  }
  /* Si queda un indicador suelto en la última fila, ocupa el ancho completo */
  .admin-dashboard .dashboard-view-admin .dashboard-kpi:last-child:nth-child(odd) { grid-column: 1 / -1; }
  .admin-dashboard .dashboard-view-admin .dashboard-kpi {
    min-width: 0;
    box-shadow: var(--ad-shadow), inset 3px 0 0 var(--ad-accent);
  }
  .admin-dashboard .dashboard-view-admin .dashboard-kpi .card-content {
    justify-content: space-between;
    gap: 4px;
    min-height: 92px;
    padding: 14px 16px;
  }
  .admin-dashboard .dashboard-view-admin .dashboard-kpi-label {
    font-size: .78rem;
    line-height: 1.35;
    color: var(--ad-muted);
  }
  .admin-dashboard .dashboard-view-admin .dashboard-kpi-value {
    font-size: clamp(1.4rem, 1.1rem + .9vw, 1.9rem);
    font-weight: 650;
    line-height: 1.1;
    font-variant-numeric: tabular-nums;
    overflow-wrap: anywhere;
  }
  .admin-dashboard .dashboard-view-admin .dashboard-kpi-hint {
    font-size: .72rem;
    line-height: 1.35;
    color: var(--ad-muted);
  }

  /* ---------- Aviso ---------- */
  .admin-dashboard .dashboard-view-admin .dashboard-note {
    margin: 0;
    padding: 10px 14px;
    border: 1px solid var(--ad-line);
    border-radius: var(--ad-radius-sm);
    background: color-mix(in srgb, var(--ad-accent) 6%, var(--ad-surface));
    box-shadow: inset 3px 0 0 var(--ad-accent);
    font-size: .82rem;
    line-height: 1.5;
  }

  /* ---------- Tarjetas y secciones ---------- */
  .admin-dashboard .dashboard-view-admin .card {
    display: flex;
    flex-direction: column;
    min-width: 0;
    border: 1px solid var(--ad-line);
    border-radius: var(--ad-radius);
    background: var(--ad-surface);
    box-shadow: var(--ad-shadow);
  }
  .admin-dashboard .dashboard-view-admin .card-content {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    min-width: 0;
    padding: var(--ad-pad);
  }
  .admin-dashboard .dashboard-view-admin .card-content > h2,
  .admin-dashboard .dashboard-view-admin .dashboard-section-heading h2 {
    margin: 0 0 2px;
    font-size: 1rem;
    font-weight: 650;
    line-height: 1.3;
  }
  .admin-dashboard .dashboard-view-admin .card-content > .text-muted-foreground,
  .admin-dashboard .dashboard-view-admin .dashboard-section-heading .text-muted-foreground {
    margin: 0 0 14px;
    font-size: .8rem;
    line-height: 1.5;
    color: var(--ad-muted);
  }

  /* ---------- Listas de estado ---------- */
  .admin-dashboard .dashboard-view-admin .dashboard-status-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    min-height: 44px;
    padding: 8px 6px;
    border-bottom: 1px solid var(--ad-line);
    border-radius: var(--ad-radius-sm);
    font-size: .875rem;
    transition: background-color .15s ease;
  }
  .admin-dashboard .dashboard-view-admin .dashboard-status-row:last-child { border-bottom-color: transparent; }
  .admin-dashboard .dashboard-view-admin .dashboard-status-row > :first-child { min-width: 0; overflow-wrap: anywhere; }
  .admin-dashboard .dashboard-view-admin .dashboard-status-row strong {
    flex: none;
    font-size: 1rem;
    font-weight: 650;
    font-variant-numeric: tabular-nums;
  }
  .admin-dashboard .dashboard-view-admin .dashboard-state-list .dashboard-status-row:nth-child(1) strong,
  .admin-dashboard .dashboard-view-admin .dashboard-state-list .dashboard-status-row:nth-child(4) strong { color: var(--warning); }
  .admin-dashboard .dashboard-view-admin .dashboard-state-list .dashboard-status-row:nth-child(5) strong { color: var(--destructive); }
  @media (hover: hover) {
    .admin-dashboard .dashboard-view-admin .dashboard-status-row:hover {
      background: color-mix(in srgb, var(--ad-accent) 6%, transparent);
    }
  }

  /* ---------- Estado vacío ---------- */
  .admin-dashboard .dashboard-view-admin .dashboard-empty {
    padding: 28px 16px;
    border: 1px dashed var(--ad-line);
    border-radius: var(--ad-radius-sm);
    background: color-mix(in srgb, var(--ad-muted) 5%, transparent);
    font-size: .85rem;
    line-height: 1.5;
    text-align: center;
    color: var(--ad-muted);
  }

  /* ---------- Tablas: scroll horizontal con sombras de aviso y encabezado fijo ---------- */
  .admin-dashboard .dashboard-view-admin .dashboard-table-wrap {
    width: 100%;
    min-width: 0;
    max-height: min(62vh, 440px);
    overflow: auto;
    border: 1px solid var(--ad-line);
    border-radius: var(--ad-radius-sm);
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
    background:
      linear-gradient(to right, var(--ad-surface) 30%, transparent) left center / 24px 100% no-repeat local,
      linear-gradient(to left, var(--ad-surface) 30%, transparent) right center / 24px 100% no-repeat local,
      radial-gradient(farthest-side at 0 50%, rgba(0, 0, 0, .16), transparent) left center / 10px 100% no-repeat scroll,
      radial-gradient(farthest-side at 100% 50%, rgba(0, 0, 0, .16), transparent) right center / 10px 100% no-repeat scroll;
    background-color: var(--ad-surface);
  }
  .admin-dashboard .dashboard-view-admin .dashboard-table {
    width: 100%;
    min-width: 460px;
    border-collapse: collapse;
  }
  .admin-dashboard .dashboard-view-admin .dashboard-table th,
  .admin-dashboard .dashboard-view-admin .dashboard-table td {
    padding: 10px 12px;
    font-size: .8rem;
    line-height: 1.4;
    text-align: start;
    vertical-align: middle;
  }
  .admin-dashboard .dashboard-view-admin .dashboard-table thead th {
    position: sticky;
    top: 0;
    z-index: 1;
    border-bottom: 1px solid var(--ad-line);
    background: var(--muted, var(--ad-surface));
    font-size: .74rem;
    font-weight: 600;
    white-space: nowrap;
    color: var(--ad-muted);
  }
  .admin-dashboard .dashboard-view-admin .dashboard-table td { overflow-wrap: anywhere; }
  .admin-dashboard .dashboard-view-admin .dashboard-table tbody tr + tr td { border-top: 1px solid var(--ad-line); }
  .admin-dashboard .dashboard-view-admin .dashboard-table tbody tr { transition: background-color .15s ease; }
  @media (hover: hover) {
    .admin-dashboard .dashboard-view-admin .dashboard-table tbody tr:hover {
      background: color-mix(in srgb, var(--ad-accent) 5%, transparent);
    }
  }

  /* ---------- Accesibilidad ---------- */
  .admin-dashboard :is(a, button, .btn, [tabindex]):focus-visible {
    outline: 2px solid var(--ring, var(--ad-accent));
    outline-offset: 2px;
  }
  @media (pointer: coarse) {
    .admin-dashboard .btn,
    .admin-dashboard .dashboard-status-row { min-height: 44px; }
  }
  @media (prefers-reduced-motion: reduce) {
    .admin-dashboard * { transition: none !important; }
  }

  /* =========================================================================
     Puntos de quiebre según el ancho disponible del contenedor
     ========================================================================= */

  /* Contenedor muy angosto (móviles pequeños): indicadores en una columna */
  @container admin-dash (max-width: 339px) {
    .admin-dashboard .dashboard-view-admin .dashboard-kpis { grid-template-columns: minmax(0, 1fr); }
    .admin-dashboard .dashboard-view-admin .dashboard-kpi .card-content { min-height: 80px; }
  }

  /* Móvil: el botón principal ocupa todo el ancho para tocarlo con facilidad */
  @container admin-dash (max-width: 559px) {
    .admin-dashboard .dashboard-view-admin .dashboard-hero > .btn { flex: 1 1 100%; }
    .admin-dashboard .dashboard-view-admin .dashboard-table { min-width: 440px; }
  }

  /* Tableta: los indicadores se acomodan solos según su ancho mínimo */
  @container admin-dash (min-width: 600px) {
    .admin-dashboard .dashboard-view-admin .dashboard-kpis {
      grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    }
    .admin-dashboard .dashboard-view-admin .dashboard-kpi:last-child:nth-child(odd) { grid-column: auto; }
  }

  /* Escritorio: las secciones agrupadas se reparten en dos columnas iguales */
  @container admin-dash (min-width: 900px) {
    .admin-dashboard .dashboard-view-admin > .dashboard-two-column > * { grid-column: span 6; }
    /* Con un número impar de secciones, la última ocupa la fila completa */
    .admin-dashboard .dashboard-view-admin > .dashboard-two-column > *:last-child:nth-child(odd) { grid-column: 1 / -1; }
  }

  /* Pantallas anchas: más aire entre bloques */
  @container admin-dash (min-width: 1400px) {
    .admin-dashboard .dashboard-view-admin .dashboard-kpi .card-content { min-height: 100px; padding: 16px 18px; }
  }

  /* Móviles en horizontal: compacta la altura */
  @media (max-height: 520px) and (orientation: landscape) {
    .admin-dashboard { --ad-gap: 12px; }
    .admin-dashboard .dashboard-view-admin .dashboard-kpi .card-content { min-height: 76px; }
    .admin-dashboard .dashboard-view-admin .dashboard-table-wrap { max-height: 70vh; }
  }
</style>
<?php endif; ?>
<?php
if ($esPanelAdmin) {
  $claseTablero = $rolActual === 1 ? 'admin-dashboard superuser-dashboard' : 'admin-dashboard';
  echo '<div class="'.$claseTablero.'">';
  include __DIR__.'/dashboard-administrador.php';
  echo '</div>';
} elseif ($rolActual === 3) {
  include __DIR__.'/dashboard-evaluador.php';
} elseif ($rolActual === 4) {
  include __DIR__.'/dashboard-docente.php';
} else {
  http_response_code(403);
  echo '<section class="card common-access-state"><div class="card-content">Sin acceso al tablero.</div></section>';
}

include COMPONENTS_DIR.'/layout/footer.php';
?>