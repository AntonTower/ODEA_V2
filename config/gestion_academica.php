<?php
declare(strict_types=1);
require_once __DIR__ . '/seguridad.php';

function academicaUsuarioAdmin(mysqli $conn): array
{
    $user = seguridadUsuarioActual($conn);
    if (!in_array((int)($user['rol'] ?? 0), [1, 2], true)) {
        http_response_code(403);
        throw new RuntimeException('No tienes permisos para gestionar la configuración académica.');
    }
    return $user;
}

function academicaTablasInstaladas(mysqli $conn): bool
{
    $required = ['periodos_academicos','convocatorias','versiones_criterios','categorias_academicas','criterios_academicos','subcriterios_academicos','requisitos_documentales','requisito_criterio','requisito_subcriterio','requisito_tipo_documental','fuentes_normativas','criterio_fuente_normativa','evidencia_criterio_versionado','evidencia_subcriterio_versionado','evaluaciones_versionadas','resultado_criterio_versionado','resultado_subcriterio_versionado'];
    $stmt = $conn->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');
    foreach ($required as $table) {
        $stmt->bind_param('s', $table);
        $stmt->execute();
        if ((int)$stmt->get_result()->fetch_row()[0] !== 1) {
            $stmt->close();
            return false;
        }
    }
    $stmt->close();
    return true;
}

function academicaFecha($value): string|false|null
{
    if ($value !== null && !is_string($value)) return false;
    $value = trim((string)$value);
    if ($value === '') return null;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : false;
}

function academicaTexto($value, int $max, string $label, bool $required = true): ?string
{
    if (!is_string($value)) throw new InvalidArgumentException("El campo $label no es válido.");
    $value = trim($value);
    if ($required && $value === '') throw new InvalidArgumentException("El campo $label es obligatorio.");
    if (mb_strlen($value, 'UTF-8') > $max) throw new InvalidArgumentException("El campo $label supera el máximo de $max caracteres.");
    return $value === '' ? null : $value;
}

function academicaFila(mysqli $conn, string $sql, string $types = '', array $values = []): ?array
{
    $stmt = $conn->prepare($sql);
    if ($types !== '') $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $row;
}

function academicaVersionBorrador(mysqli $conn, int $version): array
{
    $row = academicaFila($conn, 'SELECT v.id_version,v.id_convocatoria,v.estado,c.estado AS convocatoria_estado FROM versiones_criterios v JOIN convocatorias c USING(id_convocatoria) WHERE v.id_version=? FOR UPDATE', 'i', [$version]);
    if (!$row) throw new RuntimeException('La versión seleccionada no existe.');
    if ($row['estado'] !== 'BORRADOR') throw new RuntimeException('Las versiones publicadas o cerradas son inmutables. Crea una nueva versión para hacer cambios.');
    return $row;
}
