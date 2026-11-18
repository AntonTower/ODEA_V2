<?php
/**
 * Reglas compartidas de acceso a evidencias.
 * Roles del esquema: 1 Super Usuario, 2 Administrador, 3 Evaluador, 4 Docente.
 */

function evidenciaUsuarioActual(mysqli $conn): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $id = (int)($_SESSION['ID'] ?? 0);
    if ($id <= 0) {
        return ['id_usuario' => 0, 'rol' => 0];
    }

    $stmt = $conn->prepare('SELECT id_usuario, rol FROM usuarios WHERE id_usuario = ? AND activo = 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $usuario ?: ['id_usuario' => 0, 'rol' => 0];
}

function evidenciaPuedeVer(mysqli $conn, array $usuario, array $evidencia): bool
{
    $rol = (int)($usuario['rol'] ?? 0);
    $usuarioId = (int)($usuario['id_usuario'] ?? 0);

    if ($rol === 1 || $rol === 2) {
        return true;
    }
    if ($rol === 4) {
        return $usuarioId > 0 && $usuarioId === (int)($evidencia['id_docente'] ?? 0);
    }
    if ($rol !== 3 || $usuarioId <= 0) {
        return false;
    }

    $stmt = $conn->prepare(
        'SELECT 1
         FROM instrumento_tipo_evidencia ite
         INNER JOIN evaluador_instrumento ei ON ei.id_instrumento = ite.id_instrumento
         INNER JOIN instrumentos i ON i.id_instrumento = ite.id_instrumento AND i.activo = 1
         WHERE ite.id_tipo_evidencia = ? AND ei.id_evaluador = ?
         LIMIT 1'
    );
    $tipoId = (int)($evidencia['id_tipo_evidencia'] ?? 0);
    $stmt->bind_param('ii', $tipoId, $usuarioId);
    $stmt->execute();
    $puedeVer = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $puedeVer;
}

function evidenciaPuedeEditar(array $usuario, array $evidencia): bool
{
    $rol = (int)($usuario['rol'] ?? 0);
    if ($rol === 1 || $rol === 2) {
        return true;
    }

    return $rol === 4
        && (int)($usuario['id_usuario'] ?? 0) > 0
        && (int)$usuario['id_usuario'] === (int)($evidencia['id_docente'] ?? 0);
}
