<?php
// Rutas físicas del servidor (Para usar con include o require)
define('BASE_DIR', realpath(__DIR__ . '/..'));
define('COMPONENTS_DIR', BASE_DIR . '/components');
define('CONFIG_DIR', BASE_DIR . '/config');
define('CONTROLLERS_DIR', BASE_DIR . '/controllers');

// Rutas web (Para usar en href, src, form action y redirecciones)
// Cambia 'ODEA/gestor_evidencias' por la ruta real de tu localhost
define('BASE_URL', 'http://localhost/ODEA/gestor_evidencias'); 
?>