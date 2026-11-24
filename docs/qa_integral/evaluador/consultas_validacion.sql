-- Ejecutar en odea_qa, instancia 3318.
START TRANSACTION READ ONLY;
-- EV-01 y EV-02
SELECT u.id_usuario,u.rol,u.activo,i.id_instrumento,i.abreviatura
FROM usuarios u JOIN evaluador_instrumento ei ON ei.id_evaluador=u.id_usuario
JOIN instrumentos i USING(id_instrumento) WHERE u.id_usuario=6;
-- EV-03 a EV-05
SELECT e.id_evidencia,e.id_docente,e.id_tipo_evidencia,e.fecha_subida,e.archivo_mime,e.archivo_bytes,e.archivo_sha256,
       (SELECT COUNT(*) FROM atributos_tipo_evidencia a WHERE a.id_tipo_evidencia=e.id_tipo_evidencia AND a.requerido=1) AS requeridos,
       (SELECT COUNT(DISTINCT v.id_ate) FROM evidencia_valores_atributo v JOIN atributos_tipo_evidencia a USING(id_ate)
        WHERE v.id_evidencia=e.id_evidencia AND a.requerido=1) AS requeridos_capturados
FROM evidencias e WHERE e.id_evidencia IN(5,6,7,8);
-- EV-06 a EV-10 y EV-12
SELECT ev.id_evaluacion,ev.id_evidencia,ev.id_usuario_eval,ev.id_instrumento,ev.id_convocatoria,ev.id_version,
       s.codigo,ev.resultado,ev.comentario,ev.creada_en,ev.actualizada_en
FROM evaluaciones_versionadas ev JOIN estados_evaluacion s USING(id_estado)
WHERE ev.id_evidencia IN(5,6,7,8) ORDER BY ev.id_evaluacion;
SELECT h.id_historial,h.id_evaluacion,h.id_usuario_actor,h.creado_en,h.tipo_evento,h.resultado_anterior,h.resultado_nuevo,
       h.comentario_anterior,h.comentario_nuevo FROM historial_evaluaciones h WHERE h.id_evaluacion IN(1,2) ORDER BY h.id_historial;
SELECT id_version,id_convocatoria,estado FROM versiones_criterios WHERE id_version IN(2,3,4);
-- EV-11
SELECT COUNT(*) AS calificaciones_legadas FROM calificacion_evidencia WHERE id_evidencia IN(5,6,7,8);
SELECT COUNT(*) AS evaluaciones_de_evidencia_incompleta FROM evaluaciones_versionadas WHERE id_evidencia=8;
-- Verificación final. La evidencia 7 es el único escenario nuevo de cierre.
SELECT e.id_evidencia,ev.id_evaluacion,ev.id_usuario_eval,ev.id_convocatoria,ev.id_version,
       ev.resultado,es.codigo,ev.cerrada,ev.cerrada_en,ev.comentario
FROM evidencias e LEFT JOIN evaluaciones_versionadas ev USING(id_evidencia)
LEFT JOIN estados_evaluacion es USING(id_estado)
WHERE e.id_evidencia IN(5,6,7,8) ORDER BY e.id_evidencia;
SELECT id_historial,id_evaluacion,tipo_evento,id_usuario_actor,resultado_anterior,resultado_nuevo
FROM historial_evaluaciones WHERE id_evaluacion IN(1,2,3) ORDER BY id_historial;
SELECT (SELECT COUNT(*) FROM resultado_criterio_versionado) AS resultados_criterio,
       (SELECT COUNT(*) FROM resultado_subcriterio_versionado) AS resultados_subcriterio,
       (SELECT COUNT(*) FROM calificacion_evidencia) AS legadas;
COMMIT;
