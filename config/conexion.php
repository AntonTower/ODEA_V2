<?php
$servername = "127.0.0.1";
$database = getenv("GESTOR_DB_NAME") ?: "gestor_evidencia_produccion";
$username = "root";
$password = "";
$port = 3308;

// Crear conexión
$conn = mysqli_connect(
    $servername,
    $username,
    $password,
    $database,
    $port
);

// Verificar conexión
if (!$conn) {
    die("Error de conexión MySQL: " . mysqli_connect_error());
}

// Configurar codificación
if (!$conn->set_charset("utf8mb4")) {
    die("Error al configurar UTF-8: " . $conn->error);
}
?>