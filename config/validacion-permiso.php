<?php
require_once __DIR__ . '/sesion.php';
// Validar que el rol en sesión tenga permiso de acceder a la página actual
iniciarSesionSegura();

$user_role = $_SESSION['ROL'] ?? null;
require_once CONFIG_DIR . '/menu_config.php';

// El superusuario (1) siempre tiene acceso a todo.
if ($user_role == 1) {
    return;
}

$currentFile = strtolower(basename($_SERVER['PHP_SELF']));
$acceso_permitido = false;

// Buscar si el archivo actual está en el menú y validar si el rol tiene permiso
foreach ($MENU_CONFIG as $item) {
    if (strtolower(basename($item['ruta'])) === $currentFile) {
        if (in_array($user_role, $item['roles'])) {
            $acceso_permitido = true;
        }
        break;
    }
}

// Si la vista solicitada no está en el menú principal, puedes definir permisos extendidos aquí.
// (Por ejemplo: modales, detalle de evidencia, que comparten permisos con la vista padre)
$rutas_extendidas = [
    'evidencia-detalle.php' => [1, 2, 3, 4],
    'vista-gestion-usuarios.php' => [1, 2],
    'perfil-usuario.php' => [1, 2],
    'vista-tipos-evidencia.php' => [1, 2],
    // Añade aquí más sub-archivos según necesites
];

if (isset($rutas_extendidas[$currentFile]) && in_array($user_role, $rutas_extendidas[$currentFile])) {
    $acceso_permitido = true;
}

if (!$acceso_permitido) {
    // Redirigir si no tiene permisos
    header("Location: " . BASE_URL . "/views/comunes/index.php");
    exit();
}
?>
