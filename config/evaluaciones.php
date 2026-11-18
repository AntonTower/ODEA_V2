<?php

function evaluacionObtenerEstado(mysqli $conn, int $idEstado): ?array
{
    $st=$conn->prepare('SELECT id_estado,codigo,nombre FROM estados_evaluacion WHERE id_estado=? AND activo=1');
    $st->bind_param('i',$idEstado); $st->execute(); $row=$st->get_result()->fetch_assoc(); $st->close();
    return $row ?: null;
}

/** Si no hay transiciones configuradas para el instrumento, se permiten los estados activos.
 * Al definir al menos una transición, las operaciones quedan sujetas a esa matriz. */
function evaluacionValidarTransicion(mysqli $conn, int $instrumento, ?int $anterior, int $nuevo): array
{
    $st=$conn->prepare('SELECT COUNT(*) AS total FROM transiciones_estado_evaluacion WHERE id_instrumento=? AND activo=1');
    $st->bind_param('i',$instrumento); $st->execute(); $total=(int)$st->get_result()->fetch_assoc()['total']; $st->close();
    if ($total===0) return ['permitida'=>true,'requiere_motivo'=>false];
    $st=$conn->prepare('SELECT requiere_motivo FROM transiciones_estado_evaluacion WHERE id_instrumento=? AND id_estado_anterior <=> ? AND id_estado_nuevo=? AND activo=1 LIMIT 1');
    $st->bind_param('iii',$instrumento,$anterior,$nuevo); $st->execute(); $row=$st->get_result()->fetch_assoc(); $st->close();
    return ['permitida'=>(bool)$row,'requiere_motivo'=>$row ? (bool)$row['requiere_motivo'] : false];
}
