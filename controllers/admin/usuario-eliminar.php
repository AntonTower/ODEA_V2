<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../../config/paths.php';
require_once CONFIG_DIR . '/conexion.php';
require_once BASE_DIR . '/views/evaluador/permisos-instrumentos.php';

function usuarioEliminarJson(int $status, string $message, array $extra = []): void
{
    http_response_code($status);
    echo json_encode(array_merge(['status' => $status < 400 ? 'ok' : 'error', 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    usuarioEliminarJson(405, 'Método no permitido.');
}

// Verifica cuenta activa, facultad administrativa y token CSRF.
exigirGestion($conn, 'gestion-usuarios.php');
$actor = usuarioActualInstrumentos($conn);
$targetId = filter_var($_POST['id_usuario'] ?? null, FILTER_VALIDATE_INT);
$action = strtolower(trim((string)($_POST['accion'] ?? '')));
if (!$targetId || $targetId < 1) usuarioEliminarJson(400, 'ID de usuario inválido.');
if ($action !== '' && !in_array($action, ['activar', 'desactivar'], true)) usuarioEliminarJson(400, 'Acción de cuenta inválida.');
if ((int)$actor['id_usuario'] === $targetId) usuarioEliminarJson(409, 'No puedes desactivar tu propia cuenta.');

$conn->begin_transaction();
try {
    $lock = $conn->prepare('SELECT id_usuario,rol,activo FROM usuarios WHERE id_usuario=? FOR UPDATE');
    $lock->bind_param('i', $targetId);
    $lock->execute();
    $target = $lock->get_result()->fetch_assoc();
    $lock->close();
    if (!$target) {
        $conn->rollback();
        usuarioEliminarJson(404, 'El usuario ya no existe.');
    }
    if ((int)$actor['rol'] === 2 && (int)$target['rol'] === 1) {
        $conn->rollback();
        usuarioEliminarJson(403, 'Un administrador no puede cambiar el estado de una cuenta de Super Usuario.');
    }

    $isActive = (int)$target['activo'] === 1;
    $requestedAction = $action !== '' ? $action : ($isActive ? 'desactivar' : 'activar');
    if (($requestedAction === 'desactivar' && !$isActive) || ($requestedAction === 'activar' && $isActive)) {
        $conn->rollback();
        usuarioEliminarJson(409, 'El estado de la cuenta cambió. Actualiza la lista e inténtalo otra vez.');
    }

    if ($requestedAction === 'activar') {
        $update = $conn->prepare('UPDATE usuarios SET activo=1,updated_at=CURRENT_TIMESTAMP WHERE id_usuario=? AND activo=0');
        $update->bind_param('i', $targetId);
        $update->execute();
        $update->close();
        $conn->commit();
        usuarioEliminarJson(200, 'Cuenta reactivada. Revisa y vuelve a configurar sus asignaciones de evaluador si corresponden.', ['action' => 'activated']);
    }

    if (in_array((int)$target['rol'], [1, 2], true) && $isActive) {
        $admins = $conn->query('SELECT id_usuario FROM usuarios WHERE activo=1 AND rol IN (1,2) FOR UPDATE')->num_rows;
        if ($admins <= 1) {
            $conn->rollback();
            usuarioEliminarJson(409, 'No se puede desactivar la última cuenta administrativa activa.');
        }
    }

    // Conserva evidencias y evaluaciones históricas; desactiva acceso y revoca asignaciones.
    $update = $conn->prepare('UPDATE usuarios SET activo=0,updated_at=CURRENT_TIMESTAMP WHERE id_usuario=? AND activo=1');
    $update->bind_param('i', $targetId);
    $update->execute();
    $update->close();
    $revoke = $conn->prepare('DELETE FROM evaluador_instrumento WHERE id_evaluador=?');
    $revoke->bind_param('i', $targetId);
    $revoke->execute();
    $revoke->close();
    $conn->commit();
    usuarioEliminarJson(200, 'Cuenta desactivada; sus evidencias y evaluaciones se conservaron.', ['action' => 'deactivated']);
} catch (Throwable $e) {
    $conn->rollback();
    error_log('No se pudo desactivar usuario id=' . $targetId . ': ' . $e->getMessage());
    usuarioEliminarJson(500, 'No se pudo desactivar la cuenta. Inténtalo nuevamente.');
}
