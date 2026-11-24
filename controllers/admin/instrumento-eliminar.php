<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../../config/paths.php';
require_once CONFIG_DIR . '/conexion.php';
require_once BASE_DIR . '/views/evaluador/permisos-instrumentos.php';

function instrumentoEstadoJson(int $status, string $message, array $extra = []): void
{
    http_response_code($status);
    echo json_encode(array_merge(['status' => $status < 400 ? 'ok' : 'error', 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    instrumentoEstadoJson(405, 'Método no permitido.');
}

exigirGestion($conn, 'instrumentos.php');
$id = filter_var($_POST['id_instrumento'] ?? null, FILTER_VALIDATE_INT);
$action = strtolower(trim((string)($_POST['accion'] ?? '')));
if (!$id || $id < 1) instrumentoEstadoJson(400, 'ID de instrumento inválido.');
if (!in_array($action, ['desactivar', 'activar'], true)) instrumentoEstadoJson(400, 'Acción de instrumento inválida.');

$conn->begin_transaction();
try {
    $st = $conn->prepare('SELECT activo FROM instrumentos WHERE id_instrumento=? FOR UPDATE');
    $st->bind_param('i', $id);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$row) {
        $conn->rollback();
        instrumentoEstadoJson(404, 'El instrumento ya no existe.');
    }

    $isActive = (int)$row['activo'] === 1;
    if (($action === 'desactivar' && !$isActive) || ($action === 'activar' && $isActive)) {
        $conn->rollback();
        instrumentoEstadoJson(409, 'El estado del instrumento cambió. Actualiza la lista e inténtalo otra vez.');
    }

    $active = $action === 'activar' ? 1 : 0;
    $st = $conn->prepare('UPDATE instrumentos SET activo=?,actualizado_en=CURRENT_TIMESTAMP WHERE id_instrumento=?');
    $st->bind_param('ii', $active, $id);
    $st->execute();
    $st->close();
    $conn->commit();

    if ($active) {
        instrumentoEstadoJson(200, 'Instrumento reactivado. Sus relaciones existentes se conservaron.', ['action' => 'activated']);
    }
    instrumentoEstadoJson(200, 'Instrumento dado de baja. Se conservaron las asignaciones, evidencias y evaluaciones históricas.', ['action' => 'deactivated']);
} catch (Throwable $e) {
    $conn->rollback();
    error_log('No se pudo cambiar el estado del instrumento id=' . $id . ': ' . $e->getMessage());
    instrumentoEstadoJson(500, 'No se pudo cambiar el estado del instrumento. Inténtalo nuevamente.');
}
