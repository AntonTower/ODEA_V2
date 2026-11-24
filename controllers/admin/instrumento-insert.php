<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../../config/paths.php';
require_once CONFIG_DIR . '/conexion.php';
require_once BASE_DIR . '/views/evaluador/permisos-instrumentos.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

exigirGestion($conn, 'instrumentos.php');

function instrumentoInsertarJson(int $status, string $message, array $extra = []): void
{
    http_response_code($status);
    echo json_encode(array_merge(['status' => $status < 400 ? 'ok' : 'error', 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

$abrev = trim((string)($_POST['abreviatura'] ?? ''));
$nombre = trim((string)($_POST['nombre_completo'] ?? ''));
if ($abrev === '' || $nombre === '' || mb_strlen($abrev, 'UTF-8') > 20 || mb_strlen($nombre, 'UTF-8') > 150) {
    instrumentoInsertarJson(422, 'Indica una abreviatura (hasta 20 caracteres) y un nombre (hasta 150).');
}
if (preg_match('/[\x00-\x1F\x7F]/u', $abrev . $nombre)) {
    instrumentoInsertarJson(422, 'El nombre y la abreviatura contienen caracteres no permitidos.');
}

try {
    $st = $conn->prepare('INSERT INTO instrumentos (abreviatura, nombre_completo) VALUES (?, ?)');
    $st->bind_param('ss', $abrev, $nombre);
    $st->execute();
    $id = (int)$st->insert_id;
    $st->close();
    instrumentoInsertarJson(201, 'Instrumento creado. Puedes configurar su calificación y asignarlo a tipos de evidencia y evaluadores.', ['id' => $id]);
} catch (mysqli_sql_exception $e) {
    if ((int)$e->getCode() === 1062) {
        instrumentoInsertarJson(409, 'Ya existe un instrumento con esa abreviatura.');
    }
    error_log('No se pudo crear instrumento: ' . $e->getMessage());
    instrumentoInsertarJson(500, 'No se pudo crear el instrumento. Inténtalo nuevamente.');
} catch (Throwable $e) {
    error_log('No se pudo crear instrumento: ' . $e->getMessage());
    instrumentoInsertarJson(500, 'No se pudo crear el instrumento. Inténtalo nuevamente.');
}
