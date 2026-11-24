<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../../config/paths.php';
require_once CONFIG_DIR . '/conexion.php';
require_once BASE_DIR . '/views/evaluador/permisos-instrumentos.php';

function instrumentoEliminarJson(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode(['status' => $status < 400 ? 'ok' : 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function instrumentoTablaExiste(mysqli $conn, string $table): bool
{
    $st = $conn->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=? LIMIT 1');
    $st->bind_param('s', $table);
    $st->execute();
    $exists = (bool)$st->get_result()->fetch_row();
    $st->close();
    return $exists;
}

function instrumentoTieneReferencias(mysqli $conn, int $id): bool
{
    $st = $conn->prepare("SELECT table_name,column_name FROM information_schema.key_column_usage
        WHERE referenced_table_schema=DATABASE() AND referenced_table_name='instrumentos'
          AND referenced_column_name='id_instrumento'");
    $st->execute();
    $refs = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    foreach ($refs as $ref) {
        $table = str_replace('`', '``', (string)$ref['table_name']);
        $column = str_replace('`', '``', (string)$ref['column_name']);
        $query = $conn->prepare("SELECT 1 FROM `$table` WHERE `$column`=? LIMIT 1");
        $query->bind_param('i', $id);
        $query->execute();
        $found = (bool)$query->get_result()->fetch_row();
        $query->close();
        if ($found) return true;
    }
    return false;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    instrumentoEliminarJson(405, 'Método no permitido.');
}
exigirGestion($conn, 'instrumentos.php');

$id = filter_var($_POST['id_instrumento'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) instrumentoEliminarJson(400, 'ID de instrumento inválido.');

$conn->begin_transaction();
try {
    $st = $conn->prepare('SELECT id_instrumento FROM instrumentos WHERE id_instrumento=? FOR UPDATE');
    $st->bind_param('i', $id);
    $st->execute();
    $exists = (bool)$st->get_result()->fetch_row();
    $st->close();
    if (!$exists) {
        $conn->rollback();
        instrumentoEliminarJson(404, 'El instrumento ya no existe.');
    }

    // Quita solo asignaciones configurables. Las evaluaciones, versiones y convocatorias
    // son referencias protegidas y se comprueban antes de permitir el DELETE definitivo.
    foreach (['evaluador_instrumento', 'instrumento_tipo_evidencia', 'transiciones_estado_evaluacion'] as $table) {
        if (!instrumentoTablaExiste($conn, $table)) continue;
        $st = $conn->prepare("DELETE FROM `$table` WHERE id_instrumento=?");
        $st->bind_param('i', $id);
        $st->execute();
        $st->close();
    }

    if (instrumentoTieneReferencias($conn, $id)) {
        $conn->rollback();
        instrumentoEliminarJson(409, 'No se puede eliminar por completo: hay evaluaciones, convocatorias u otros registros relacionados. Usa Desactivar para conservar el historial.');
    }

    $st = $conn->prepare('DELETE FROM instrumentos WHERE id_instrumento=?');
    $st->bind_param('i', $id);
    $st->execute();
    $deleted = $st->affected_rows;
    $st->close();
    if ($deleted !== 1) throw new RuntimeException('El instrumento no pudo eliminarse.');

    $conn->commit();
    instrumentoEliminarJson(200, 'Instrumento eliminado por completo.');
} catch (Throwable $e) {
    $conn->rollback();
    error_log('No se pudo eliminar instrumento id=' . $id . ': ' . $e->getMessage());
    instrumentoEliminarJson(500, 'No se pudo eliminar el instrumento. Inténtalo nuevamente.');
}
