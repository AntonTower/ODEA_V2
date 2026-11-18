<?php
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/seguridad.php';

/**
 * Reglas compartidas de acceso a evidencias.
 * Roles del esquema: 1 Super Usuario, 2 Administrador, 3 Evaluador, 4 Docente.
 */

function evidenciaUsuarioActual(mysqli $conn): array
{
    if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        csrf_exigir();
    }
    return seguridadUsuarioActual($conn);
}

function evidenciaPuedeVer(mysqli $conn, array $usuario, array $evidencia): bool
{
    return seguridadPuedeVerEvidencia($conn, $usuario, $evidencia);
}

function evidenciaPuedeEditar(array $usuario, array $evidencia): bool
{
    return seguridadPuedeEditarEvidencia($usuario, $evidencia);
}
