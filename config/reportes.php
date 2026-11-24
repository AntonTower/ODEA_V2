<?php

/**
 * Carga atributos de muchas evidencias en lotes para evitar N+1 en reportes.
 * Retorna [id_evidencia][id_ate] => filas ordenadas.
 */
function reportesCargarValoresAtributos(mysqli $conn, array $evidenceIds): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $evidenceIds), static fn($id) => $id > 0)));
    $out = [];
    foreach (array_chunk($ids, 400) as $batch) {
        $placeholders = implode(',', array_fill(0, count($batch), '?'));
        $sql = "SELECT id_evidencia,id_eva,id_ate,indice,
                       valor_texto,valor_largo,valor_int,valor_decimal,
                       valor_fecha,valor_bool,valor_archivo,valor_json
                FROM evidencia_valores_atributo
                WHERE id_evidencia IN ($placeholders)
                ORDER BY id_evidencia,id_ate,indice,id_eva";
        $stmt = $conn->prepare($sql);
        $types = str_repeat('i', count($batch));
        $bind = [$types];
        foreach ($batch as $key => $value) $bind[] = &$batch[$key];
        call_user_func_array([$stmt, 'bind_param'], $bind);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $evidenceId = (int)$row['id_evidencia'];
            $attributeId = (int)$row['id_ate'];
            $out[$evidenceId][$attributeId][] = $row;
        }
        $stmt->close();
    }
    return $out;
}


/**
 * Dictámenes vigentes por contexto y calificaciones anteriores sin versión.
 * Un dictamen versionado desplaza la calificación legada del mismo par en
 * la consulta general. Los contextos versionados distintos se conservan.
 */
function reportesConsultarDictamenes(
    mysqli $conn,
    array $usuario,
    int $instrumento = 0,
    int $docente = 0,
    array $filtros = []
): array {
    $rol = (int) ($usuario['rol'] ?? 0);
    $actor = (int) ($usuario['id_usuario'] ?? 0);
    if ($actor <= 0 || !in_array($rol, [1, 2, 3, 4], true)) return [];
    if ($rol === 4) $docente = $actor;

    $estado = strtoupper(trim((string) ($filtros['estado'] ?? '')));
    $convocatoria = (int) ($filtros['convocatoria'] ?? 0);
    $version = (int) ($filtros['version'] ?? 0);
    $soloAceptadas = (bool) ($filtros['solo_aceptadas'] ?? false);

    $schema = $conn->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN
        ('evaluaciones_versionadas','estados_evaluacion','versiones_criterios','convocatorias')");
    $versionado = (int) $schema->fetch_row()[0] === 4;
    $schema->free();

    $legacy = "SELECT ce.id_evidencia,ce.id_instrumento,ce.id_usuario_eval,
        ce.resultado,ce.comentario,ce.calificado_en,ce.actualizado_en,
        NULL AS id_convocatoria,NULL AS id_version,NULL AS convocatoria,NULL AS codigo_version,
        'LEGADA' AS codigo_estado,'Legada' AS estado,0 AS id_evaluacion
        FROM calificacion_evidencia ce";
    $contextos = $legacy;
    if ($versionado && $estado !== 'LEGADA') {
        $contextos = "SELECT ev.id_evidencia,ev.id_instrumento,ev.id_usuario_eval,
            ev.resultado,ev.comentario,ev.creada_en AS calificado_en,ev.actualizada_en AS actualizado_en,
            ev.id_convocatoria,ev.id_version,c.nombre AS convocatoria,v.codigo_version,
            es.codigo AS codigo_estado,es.nombre AS estado,ev.id_evaluacion
            FROM evaluaciones_versionadas ev
            JOIN estados_evaluacion es USING(id_estado)
            LEFT JOIN convocatorias c USING(id_convocatoria)
            LEFT JOIN versiones_criterios v USING(id_version)
            WHERE ev.id_version IS NOT NULL OR NOT EXISTS (
                SELECT 1 FROM evaluaciones_versionadas vigente
                WHERE vigente.id_evidencia=ev.id_evidencia AND vigente.id_instrumento=ev.id_instrumento
                  AND vigente.id_version IS NOT NULL
            )
            UNION ALL $legacy
            WHERE NOT EXISTS (SELECT 1 FROM evaluaciones_versionadas ev
                WHERE ev.id_evidencia=ce.id_evidencia AND ev.id_instrumento=ce.id_instrumento
                  AND (ev.id_version IS NOT NULL OR ev.id_estado IS NOT NULL))";
    }

    $sql = "SELECT d.*, e.titulo,e.archivo,e.fecha_subida,e.id_docente,e.id_tipo_evidencia,
        u.nombre,u.apellidop,u.apellidom,t.nombre_tipo,
        i.abreviatura,i.nombre_completo,i.tipo_calificacion,
        COALESCE(i.min_calificacion,0) AS min_cal,COALESCE(i.max_calificacion,10) AS max_cal,
        TRIM(CONCAT_WS(' ',a.nombre,a.apellidop,a.apellidom)) AS evaluador
        FROM ($contextos) d
        JOIN evidencias e ON e.id_evidencia=d.id_evidencia AND e.ocultar=0
        JOIN usuarios u ON u.id_usuario=e.id_docente
        JOIN tipos_de_evidencia t ON t.id_tipo_evidencia=e.id_tipo_evidencia
        JOIN instrumentos i ON i.id_instrumento=d.id_instrumento AND i.activo=1
        JOIN instrumento_tipo_evidencia ite ON ite.id_tipo_evidencia=e.id_tipo_evidencia
            AND ite.id_instrumento=i.id_instrumento
        LEFT JOIN usuarios a ON a.id_usuario=d.id_usuario_eval
        WHERE (?=0 OR d.id_instrumento=?) AND (?=0 OR e.id_docente=?)
          AND (?=0 OR d.id_convocatoria=?) AND (?=0 OR d.id_version=?)";
    $types = 'iiiiiiii';
    $params = [$instrumento,$instrumento,$docente,$docente,$convocatoria,$convocatoria,$version,$version];
    if ($rol === 3) {
        $sql .= ' AND EXISTS (SELECT 1 FROM evaluador_instrumento ei
            WHERE ei.id_evaluador=? AND ei.id_instrumento=d.id_instrumento)';
        $types .= 'i';
        $params[] = $actor;
    }
    if ($estado !== '') {
        $sql .= ' AND d.codigo_estado=?';
        $types .= 's';
        $params[] = $estado;
    }
    if ($soloAceptadas) {
        // Se mantiene únicamente para el modelo legado el umbral histórico del 60 %.
        $sql .= " AND (d.codigo_estado='ACEPTADA' OR
            (d.codigo_estado='LEGADA' AND
                ((i.tipo_calificacion='APROBACION' AND d.resultado>=1) OR
                 (i.tipo_calificacion='NUMERICA' AND d.resultado>=
                    COALESCE(i.min_calificacion,0)+0.60*(COALESCE(i.max_calificacion,10)-COALESCE(i.min_calificacion,0))))))
            AND (d.id_evaluacion=0 OR NOT EXISTS (
                SELECT 1 FROM atributos_tipo_evidencia at
                WHERE at.id_tipo_evidencia=e.id_tipo_evidencia AND at.requerido=1
                AND NOT EXISTS (SELECT 1 FROM evidencia_valores_atributo val
                    WHERE val.id_evidencia=e.id_evidencia AND val.id_ate=at.id_ate)
            ))";
    }
    $sql .= ' ORDER BY d.actualizado_en DESC,d.id_evaluacion DESC,d.id_evidencia DESC,d.id_instrumento';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function reportesFiltrosContexto(array $entrada): array
{
    return [
        'convocatoria' => max(0, (int) ($entrada['convocatoria'] ?? 0)),
        'version' => max(0, (int) ($entrada['version'] ?? 0)),
        'estado' => (string) ($entrada['estado'] ?? ''),
    ];
}
