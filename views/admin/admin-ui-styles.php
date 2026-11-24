<style>
  /* Lenguaje visual compartido por las páginas y tablas administrativas. */
  body.has-sidebar main > section.space-y-6 {
    display: flex;
    flex-direction: column;
    gap: clamp(16px, 2vw, 22px);
    min-width: 0;
  }
  body.has-sidebar main > section.space-y-6 > header {
    align-items: center;
    flex-wrap: wrap;
    gap: 12px 20px;
    margin: 0;
  }
  body.has-sidebar main > section.space-y-6 > header h1 {
    margin: 0 0 5px;
    font-size: clamp(1.35rem, 1.8vw, 1.7rem);
    letter-spacing: -.02em;
  }
  body.has-sidebar main > section.space-y-6 > header p {
    margin: 0;
    max-width: 72ch;
    font-size: .88rem;
    line-height: 1.5;
  }
  body.has-sidebar main > section.space-y-6 > header > div:last-child {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }
  body.has-sidebar main > section.space-y-6 > header .btn,
  body.has-sidebar main > section.space-y-6 > header > button {
    min-height: 40px;
    white-space: nowrap;
  }

  /* Barra de búsqueda y filtros de cada listado. */
  body.has-sidebar main > section.space-y-6 > .card:first-of-type > .card-content {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px;
  }
  body.has-sidebar main > section.space-y-6 > .card:first-of-type #search,
  body.has-sidebar main > section.space-y-6 > .card:first-of-type #ins_search {
    flex: 1 1 220px;
    width: auto;
    min-width: 0;
  }
  body.has-sidebar main > section.space-y-6 > .card:first-of-type #clearSearch,
  body.has-sidebar main > section.space-y-6 > .card:first-of-type #ins_clearSearch {
    flex: 0 0 auto;
  }
  #filterRole { width: min(100%, 280px); min-width: min(100%, 220px) !important; }
  #instFilters { max-width: 100%; gap: 8px 14px !important; }
  #instFilters label,
  body.has-sidebar main > section.space-y-6 > .card:first-of-type label {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: var(--foreground);
    font-size: .82rem;
  }
  #instFilters input[type="checkbox"],
  body.has-sidebar main > section.space-y-6 > .card:first-of-type input[type="checkbox"] {
    width: 16px;
    height: 16px;
    flex: 0 0 auto;
  }

  /* Páginas administrativas con formularios incorporados. */
  #formNuevoInstrumento {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    align-items: end;
    gap: 14px;
    padding-top: 4px;
  }
  #formNuevoInstrumento > label {
    display: grid;
    min-width: 0;
    gap: 6px;
    color: var(--foreground);
    font-size: .82rem;
    font-weight: 600;
  }
  #formNuevoInstrumento > p { grid-column: 1 / -1; margin: 0; color: var(--muted-foreground); font-size: .82rem; }
  #formNuevoInstrumento > button { justify-self: start; }
  #resultadoNuevoInstrumento { min-height: 1.4em; }
  body.has-sidebar main > section.space-y-6 > details.card > summary {
    padding: 15px 18px;
    color: var(--foreground);
    font-weight: 650;
    cursor: pointer;
  }
  body.has-sidebar main > section.space-y-6 > details.card[open] > summary {
    border-bottom: 1px solid var(--border);
  }

  /* Respuesta AJAX: scroll local conserva todas las columnas en móvil. */
  #tablaGestionUsuarios .card-content,
  #tablaGestionTipos .card-content,
  #tablaAtributosTipo .card-content,
  #tablaInstrumentos .card-content {
    min-width: 0;
    padding: 12px;
    overflow-x: auto !important;
    overflow-y: hidden;
    overscroll-behavior-inline: contain;
    -webkit-overflow-scrolling: touch;
  }
  #tablaGestionUsuarios .table,
  #tablaGestionTipos .table,
  #tablaAtributosTipo .table,
  #tablaInstrumentos .table {
    width: 100% !important;
    margin: 0;
    color: var(--foreground);
  }
  #tabla-usuarios { min-width: 1040px; }
  #tabla-tipos-evidencia { min-width: 760px; }
  #tabla-atributos-tipo { min-width: 900px; }
  #tabla-instrumentos { min-width: 700px; }
  #tablaGestionUsuarios .dataTables_wrapper,
  #tablaGestionTipos .dataTables_wrapper,
  #tablaAtributosTipo .dataTables_wrapper,
  #tablaInstrumentos .dataTables_wrapper,
  #tablaGestionUsuarios .dt-container,
  #tablaGestionTipos .dt-container,
  #tablaAtributosTipo .dt-container,
  #tablaInstrumentos .dt-container { width: 100%; min-width: 0; }
  #tablaGestionUsuarios .table thead th,
  #tablaGestionTipos .table thead th,
  #tablaAtributosTipo .table thead th,
  #tablaInstrumentos .table thead th {
    padding: 11px 12px;
    color: var(--secondary-foreground);
    background: var(--muted);
    border-bottom: 1px solid var(--border);
    font-size: .76rem;
    font-weight: 700;
    letter-spacing: .025em;
    white-space: nowrap;
  }
  #tablaGestionUsuarios .table tbody td,
  #tablaGestionTipos .table tbody td,
  #tablaAtributosTipo .table tbody td,
  #tablaInstrumentos .table tbody td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
    font-size: .82rem;
    line-height: 1.45;
  }
  #tablaGestionUsuarios .table tbody tr:hover,
  #tablaGestionTipos .table tbody tr:hover,
  #tablaAtributosTipo .table tbody tr:hover,
  #tablaInstrumentos .table tbody tr:hover { background: color-mix(in srgb, var(--accent) 42%, var(--card)); }
  #tablaGestionUsuarios .table td:last-child,
  #tablaGestionTipos .table td:last-child,
  #tablaAtributosTipo .table td:last-child,
  #tablaInstrumentos .table td:last-child {
    display: flex;
    align-items: center;
    gap: 6px !important;
    white-space: nowrap;
  }
  #tablaGestionUsuarios td:last-child .btn,
  #tablaGestionTipos td:last-child .btn,
  #tablaAtributosTipo td:last-child .btn,
  #tablaInstrumentos td:last-child .btn {
    min-width: 36px;
    min-height: 36px;
    justify-content: center;
    padding: 7px;
    border-radius: 8px;
    background: var(--card);
    box-shadow: none;
  }
  #tablaGestionUsuarios td:last-child button[title="Ver perfil"] { background: var(--success-soft); border-color: color-mix(in srgb, var(--success) 24%, var(--border)); }
  #tablaGestionUsuarios td:last-child button[title="Editar"],
  #tablaGestionTipos td:last-child button[title="Editar"],
  #tabla-atributos-tipo td:last-child button[title="Editar"],
  #tabla-instrumentos td:last-child button[title="Editar"] { background: var(--info-soft); border-color: color-mix(in srgb, var(--info) 24%, var(--border)); }
  #tablaGestionUsuarios td:last-child button[title="Eliminar"],
  #tablaGestionTipos td:last-child button[title="Eliminar"],
  #tablaAtributosTipo td:last-child button[title="Eliminar"] { background: color-mix(in srgb, var(--destructive) 8%, var(--card)); border-color: color-mix(in srgb, var(--destructive) 24%, var(--border)); }
  #tablaGestionUsuarios .dt-layout-row,
  #tablaGestionTipos .dt-layout-row,
  #tablaAtributosTipo .dt-layout-row,
  #tablaInstrumentos .dt-layout-row { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 14px; }
  #tablaGestionUsuarios .dt-layout-cell,
  #tablaGestionTipos .dt-layout-cell,
  #tablaAtributosTipo .dt-layout-cell,
  #tablaInstrumentos .dt-layout-cell { min-width: 0; }
  #tablaGestionUsuarios .dt-search input,
  #tablaGestionTipos .dt-search input,
  #tablaAtributosTipo .dt-search input,
  #tablaInstrumentos .dt-search input { max-width: min(100%, 240px); }
  #tablaGestionUsuarios .dt-paging-button,
  #tablaGestionTipos .dt-paging-button,
  #tablaAtributosTipo .dt-paging-button,
  #tablaInstrumentos .dt-paging-button { min-width: 34px; min-height: 34px; border-radius: 8px !important; }
  #tablaGestionUsuarios .dt-empty,
  #tablaGestionTipos .dt-empty,
  #tablaAtributosTipo .dt-empty,
  #tablaInstrumentos .dt-empty { padding: 24px 14px !important; color: var(--muted-foreground); text-align: center; }

  /* Chips presentes en las tablas de tipos, atributos e instrumentos. */
  #tablaGestionTipos .t-pill { padding: 4px 8px; border-color: var(--border); border-radius: 999px; background: var(--muted); color: var(--secondary-foreground); font-size: .72rem; line-height: 1.25; }
  #tablaInstrumentos .pill { padding: 4px 8px; border-color: var(--border); border-radius: 999px; font-size: .72rem; line-height: 1.25; }
  #tablaInstrumentos .p-ap { background: var(--success-soft); color: var(--success); }
  #tablaInstrumentos .p-num { background: var(--info-soft); color: var(--info); }
  #tablaAtributosTipo code { overflow-wrap: anywhere; color: var(--accent-foreground); }

  /* Modal de confirmación y feedback de librería ya instalada. */
  body.has-sidebar .dialog-card { border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); }
  body.has-sidebar .dialog-actions { gap: 8px; }
  body.has-sidebar .dialog-actions .btn-primary { background: var(--destructive); border-color: var(--destructive); color: var(--destructive-foreground); }
  body .swal2-popup { border: 1px solid var(--border); border-radius: var(--radius-lg); background: var(--card); color: var(--card-foreground); box-shadow: var(--shadow-lg); }
  body .swal2-title { color: var(--foreground); }
  body .swal2-html-container { color: var(--muted-foreground); }

  @media (max-width: 767px) {
    body.has-sidebar main > section.space-y-6 > header { align-items: flex-start; }
    body.has-sidebar main > section.space-y-6 > header > button,
    body.has-sidebar main > section.space-y-6 > header .btn { width: 100%; justify-content: center; }
    body.has-sidebar main > section.space-y-6 > .card:first-of-type > .card-content { align-items: stretch; padding: 12px; }
    body.has-sidebar main > section.space-y-6 > .card:first-of-type > .card-content > div { max-width: 100% !important; }
    #filterRole { width: 100%; min-width: 0 !important; }
    #formNuevoInstrumento { grid-template-columns: minmax(0, 1fr); }
    #formNuevoInstrumento > p { grid-column: auto; }
    #formNuevoInstrumento > button { width: 100%; justify-content: center; }
    #tablaGestionUsuarios .dt-layout-row,
    #tablaGestionTipos .dt-layout-row,
    #tablaAtributosTipo .dt-layout-row,
    #tablaInstrumentos .dt-layout-row { justify-content: flex-start; }
  }
  @media (max-width: 430px) {
    body.has-sidebar main > section.space-y-6 > header > div:last-child { width: 100%; }
    body.has-sidebar main > section.space-y-6 > header > div:last-child .btn { flex: 1 1 auto; justify-content: center; }
    body.has-sidebar main > section.space-y-6 > .card:first-of-type > .card-content { gap: 10px; }
    #tablaGestionUsuarios .card-content,
    #tablaGestionTipos .card-content,
    #tablaAtributosTipo .card-content,
    #tablaInstrumentos .card-content { padding: 8px; }
  }
  @media (max-height: 520px) and (orientation: landscape) {
    body.has-sidebar main > section.space-y-6 { gap: 14px; }
    body.has-sidebar main > section.space-y-6 > header { align-items: center; }
  }
</style>
