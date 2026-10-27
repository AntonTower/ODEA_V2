<?php
/**
 * Cierra la sesión del usuario de forma segura y redirige al login.
 * - Limpia variables de sesión
 * - Destruye la sesión y su cookie
 * - Regenera el ID para evitar fijación
 * - Redirige a public/login.php con un flag (?logout=1)
 */

declare(strict_types=1);

// Definición de constante de ruta si no existe
if (!defined('BASE_URL')) {
    define('BASE_URL', '/ODEA/gestor_evidencias');
}

// Iniciar sesión si no existe
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 1) Vaciar el arreglo de sesión
$_SESSION = [];

// 2) Borrar la cookie de sesión
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'] ?? '/',
        $params['domain'] ?? '',
        (bool)($params['secure'] ?? false),
        (bool)($params['httponly'] ?? true)
    );
}

// 3) Destruir la sesión en el servidor
session_destroy();

// 4) Regenerar un nuevo ID de sesión para el siguiente request
session_start();
session_regenerate_id(true);

// 5) Redirigir al login público mediante ruta absoluta
header('Location: ' . BASE_URL . '/public/login.php?logout=1');
exit;