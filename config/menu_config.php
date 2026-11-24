<?php
// Configuración estática del menú y rutas permitidas por rol
// Roles: 1 (Super), 2 (Admin), 3 (Evaluador), 4 (Docente)

$MENU_CONFIG = [
    [
        'titulo' => 'Resumen',
        'ruta' => 'views/comunes/index.php',
        'icono' => '<path d="M3 21V10l9-7 9 7v11" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 21v-6h6v6" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
        'roles' => [1, 2, 3, 4]
    ],
    [
        'titulo' => 'Expediente Académico',
        'ruta' => 'views/docente/expediente.php',
        'icono' => '<path d="M6 3h9l4 4v14H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M14 3v5h5M8 12h8M8 16h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'roles' => [1, 2, 4]
    ],
    [
        'titulo' => 'Usuarios',
        'ruta' => 'views/admin/gestion-usuarios.php',
        'icono' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.8"/><path d="M22 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'roles' => [1, 2]
    ],
    [
        'titulo' => 'Tipos de Evidencias',
        'ruta' => 'views/admin/tipos-evidencia.php',
        'icono' => '<rect x="5" y="3" width="14" height="18" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M9 3.5h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M8 10l2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'roles' => [1, 2]
    ],
    [
        'titulo' => 'Evidencias',
        'ruta' => 'views/docente/gestion-evidencias.php',
        'icono' => '<path d="M2 7a4 4 0 0 1 4-4h6v18H6a4 4 0 0 0-4 4V7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M22 7a4 4 0 0 0-4-4h-6v18h6a4 4 0 0 1 4 4V7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
        'roles' => [1, 2, 3, 4]
    ],
    [
        'titulo' => 'Instrumentos',
        'ruta' => 'views/admin/instrumentos.php',
        'icono' => '<rect x="3" y="6" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M7 4v4M17 4v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M8 13l2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'roles' => [1, 2]
    ],
    [
        'titulo' => 'Convocatorias',
        'seccion' => 'gestion_academica',
        'ruta' => 'views/admin/convocatorias.php',
        'icono' => '<path d="M4 4h16v16H4zM8 2v4m8-4v4M4 9h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'roles' => [1, 2]
    ],
    [
        'titulo' => 'Periodos Académicos',
        'seccion' => 'gestion_academica',
        'ruta' => 'views/admin/periodos-academicos.php',
        'icono' => '<path d="M4 5h16v15H4zM8 3v4m8-4v4M4 9h16m3 5h-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'roles' => [1, 2]
    ],
    [
        'titulo' => 'Criterios de Evaluación',
        'seccion' => 'gestion_academica',
        'ruta' => 'views/admin/criterios-evaluacion.php',
        'icono' => '<path d="M5 4h14v17H5zM8 8h8M8 12h8M8 16h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'roles' => [1, 2]
    ],
    [
        'titulo' => 'Fuentes Normativas',
        'seccion' => 'gestion_academica',
        'ruta' => 'views/admin/fuentes-normativas.php',
        'icono' => '<path d="M6 3h9l4 4v14H6zM14 3v5h5M9 12h7M9 16h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'roles' => [1, 2]
    ],
    [
        'titulo' => 'Evaluación',
        'ruta' => 'views/evaluador/evaluacion.php',
        'icono' => '<path d="M9 5h6a2 2 0 0 1 2 2v2H7V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8"/><rect x="7" y="9" width="10" height="10" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M9.5 14l1.5 1.5L14.5 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'roles' => [1, 2, 3]
    ],
    [
        'titulo' => 'Calificaciones',
        'ruta' => 'views/docente/calificaciones-recibidas.php',
        'icono' => '<path d="M6 10a6 6 0 1 1 12 0v5l2 2H4l2-2v-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M10 20a2 2 0 0 0 4 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'roles' => [1, 4]
    ],
    [
        'titulo' => 'Perfil',
        'ruta' => 'views/comunes/perfil.php',
        'icono' => '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="1.8"/><path d="M6.5 18c1.6-2.3 4-3.5 5.5-3.5S15.9 15.7 17.5 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'roles' => [1, 2, 3, 4]
    ]
];
?>
