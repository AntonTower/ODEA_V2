<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../../config/paths.php';
require_once CONFIG_DIR . '/conexion.php';
require_once BASE_DIR . '/views/evaluador/permisos-instrumentos.php';
require_once CONFIG_DIR . '/seguridad.php';

function usuarioEliminarDefinitivoJson(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode(['status' => $status < 400 ? 'ok' : 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function usuarioEliminarDefinitivoTabla(mysqli $conn, string $table): bool
{
    $st = $conn->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=? LIMIT 1');
    $st->bind_param('s', $table);
    $st->execute();
    $found = (bool)$st->get_result()->fetch_row();
    $st->close();
    return $found;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    usuarioEliminarDefinitivoJson(405, 'Método no permitido.');
}

exigirGestion($conn, 'gestion-usuarios.php');
$actor = usuarioActualInstrumentos($conn);
$targetId = filter_var($_POST['id_usuario'] ?? null, FILTER_VALIDATE_INT);
if (!$targetId || $targetId < 1) usuarioEliminarDefinitivoJson(400, 'ID de usuario inválido.');
if ((int)$actor['id_usuario'] === $targetId) usuarioEliminarDefinitivoJson(409, 'No puedes eliminar tu propia cuenta.');

$filesToRemove = [];
$conn->begin_transaction();
$transactionOpen = true;
try {
    $st = $conn->prepare('SELECT id_usuario,rol,activo FROM usuarios WHERE id_usuario=? FOR UPDATE');
    $st->bind_param('i', $targetId);
    $st->execute();
    $target = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$target) {
        $conn->rollback();
        usuarioEliminarDefinitivoJson(404, 'El usuario ya no existe.');
    }
    if ((int)$actor['rol'] === 2 && (int)$target['rol'] === 1) {
        $conn->rollback();
        usuarioEliminarDefinitivoJson(403, 'Un administrador no puede eliminar una cuenta de Super Usuario.');
    }
    if (in_array((int)$target['rol'], [1, 2], true) && (int)$target['activo'] === 1) {
        $admins = $conn->query('SELECT id_usuario FROM usuarios WHERE activo=1 AND rol IN (1,2) FOR UPDATE')->num_rows;
        if ($admins <= 1) {
            $conn->rollback();
            usuarioEliminarDefinitivoJson(409, 'No se puede eliminar la última cuenta administrativa activa.');
        }
    }

    $st = $conn->prepare('SELECT id_evidencia,archivo FROM evidencias WHERE id_docente=? FOR UPDATE');
    $st->bind_param('i', $targetId);
    $st->execute();
    $evidences = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    $evidenceIds = array_map(static fn(array $row): int => (int)$row['id_evidencia'], $evidences);
    foreach ($evidences as $row) if ((string)$row['archivo'] !== '') $filesToRemove[] = (string)$row['archivo'];

    if ($evidenceIds) {
        $ids = implode(',', array_map('intval', $evidenceIds));
        // El historial académico es append-only. Si existe, la baja reversible conserva la trazabilidad.
        foreach ([
            ['evaluaciones_versionadas', 'id_evidencia', 'Hay evaluaciones históricas asociadas a sus evidencias. Desactiva la cuenta para conservarlas.'],
            ['evidencia_aplicaciones_academicas', 'id_evidencia', 'Hay aplicaciones académicas asociadas a sus evidencias. Desactiva la cuenta para conservarlas.'],
            ['revisiones_metadatos_documentales', 'id_evidencia', 'Hay revisiones documentales históricas asociadas. Desactiva la cuenta para conservarlas.'],
        ] as [$table, $column, $message]) {
            if (!usuarioEliminarDefinitivoTabla($conn, $table)) continue;
            $safeTable = str_replace('`', '``', $table);
            $safeColumn = str_replace('`', '``', $column);
            if ($conn->query("SELECT 1 FROM `$safeTable` WHERE `$safeColumn` IN ($ids) LIMIT 1")->num_rows) {
                $conn->rollback();
                usuarioEliminarDefinitivoJson(409, $message);
            }
        }
        // Captura adjuntos guardados como atributos dinámicos para borrarlos solo si quedan huérfanos.
        $st = $conn->prepare("SELECT valor_archivo FROM evidencia_valores_atributo WHERE id_evidencia IN ($ids) AND valor_archivo IS NOT NULL AND valor_archivo<>''");
        $st->execute();
        foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $filesToRemove[] = (string)$row['valor_archivo'];
        $st->close();

        // Los enlaces a trabajos son restrictivos y no contienen el archivo fuente: se retiran antes de la evidencia.
        if (usuarioEliminarDefinitivoTabla($conn, 'origenes_trabajo_documental')) {
            $st = $conn->prepare("DELETE FROM origenes_trabajo_documental WHERE id_evidencia IN ($ids)");
            $st->execute();
            $st->close();
        }
        $st = $conn->prepare("DELETE FROM evidencias WHERE id_evidencia IN ($ids)");
        $st->execute();
        $st->close();
    }

    // Evaluaciones legadas hechas por esta cuenta en evidencias de terceros no se borran junto con el usuario.
    $st = $conn->prepare('SELECT 1 FROM calificacion_evidencia WHERE id_usuario_eval=? LIMIT 1');
    $st->bind_param('i', $targetId);
    $st->execute();
    $hasRatings = (bool)$st->get_result()->fetch_row();
    $st->close();
    if ($hasRatings) {
        $conn->rollback();
        usuarioEliminarDefinitivoJson(409, 'Esta cuenta tiene calificaciones legadas en evidencias de terceros. Desactívala para conservar la atribución histórica.');
    }

    $st = $conn->prepare('DELETE FROM evaluador_instrumento WHERE id_evaluador=?');
    $st->bind_param('i', $targetId);
    $st->execute();
    $st->close();
    $st = $conn->prepare('DELETE FROM usuarios WHERE id_usuario=?');
    $st->bind_param('i', $targetId);
    $st->execute();
    if ($st->affected_rows !== 1) throw new RuntimeException('No se eliminó el usuario.');
    $st->close();
    $conn->commit();
    $transactionOpen = false;

    // Elimina únicamente archivos privados ya sin referencias en evidencias ni atributos.
    foreach (array_unique($filesToRemove) as $filename) {
        $st = $conn->prepare('SELECT 1 FROM evidencias WHERE archivo=? UNION ALL SELECT 1 FROM evidencia_valores_atributo WHERE valor_archivo=? LIMIT 1');
        $st->bind_param('ss', $filename, $filename);
        $st->execute();
        $referenced = (bool)$st->get_result()->fetch_row();
        $st->close();
        if (!$referenced) {
            $path = seguridadResolverArchivo($filename);
            if ($path !== null && !@unlink($path)) error_log('No se pudo retirar archivo de usuario eliminado: ' . basename($filename));
        }
    }
    usuarioEliminarDefinitivoJson(200, 'Usuario y evidencias sin historial protegido eliminados por completo.');
} catch (Throwable $e) {
    if ($transactionOpen) $conn->rollback();
    error_log('No se pudo eliminar definitivamente usuario id=' . $targetId . ': ' . $e->getMessage());
    usuarioEliminarDefinitivoJson(500, 'No se pudo completar la eliminación. Inténtalo nuevamente.');
}
