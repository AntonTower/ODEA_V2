<?php
/**
 * Conexión ODEA. Los valores por defecto mantienen compatibilidad con XAMPP;
 * los despliegues pueden inyectar credenciales desde variables de entorno.
 */
$servername = getenv('GESTOR_DB_HOST') ?: '127.0.0.1';
$database = getenv('GESTOR_DB_NAME') ?: 'gestor_evidencia_produccion';
$username = getenv('GESTOR_DB_USER') ?: 'root';
$password = getenv('GESTOR_DB_PASSWORD') ?: '';
$port = (int)(getenv('GESTOR_DB_PORT') ?: 3308);

try {
    $conn = mysqli_connect($servername, $username, $password, $database, $port);
    if (!$conn) {
        error_log('Error de conexión ODEA: ' . mysqli_connect_error());
        http_response_code(503);
        exit('El servicio de base de datos no está disponible.');
    }
    if (!$conn->set_charset('utf8mb4')) {
        error_log('Error al configurar utf8mb4 en ODEA: ' . $conn->error);
        http_response_code(503);
        exit('El servicio de base de datos no está disponible.');
    }
} catch (mysqli_sql_exception $e) {
    error_log('Error de conexión ODEA: ' . $e->getMessage());
    http_response_code(503);
    exit('El servicio de base de datos no está disponible.');
}
