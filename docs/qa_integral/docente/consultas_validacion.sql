-- QA odea_qa, puerto 3318.
START TRANSACTION READ ONLY;
SELECT DATABASE() AS esquema, @@port AS puerto, @@version AS version_bd;
SELECT id_usuario,rol,activo FROM usuarios WHERE id_usuario IN (5,6,7);
SELECT id_evaluador,id_instrumento FROM evaluador_instrumento WHERE id_evaluador=6;
SELECT e.id_evidencia,e.titulo,e.id_docente,e.id_tipo_evidencia,e.archivo,e.archivo_mime,e.archivo_bytes,e.archivo_sha256,e.fecha_subida
FROM evidencias e WHERE id_docente=5 ORDER BY id_evidencia;
SELECT COUNT(*) AS evidencias_docente FROM evidencias WHERE id_docente=5 AND ocultar=0;
SELECT id_tipo_evidencia,id_instrumento FROM instrumento_tipo_evidencia WHERE id_tipo_evidencia IN (7,8,9);
SELECT COUNT(*) AS cargas_invalidas FROM evidencias WHERE titulo IN ('QA_ODEA_DO_Formato','QA_ODEA_DO_Vacia','QA_ODEA_DO_ExtensionFalsa','QA_ODEA_DO_SobreLimite');
SELECT v.id_evidencia,v.id_ate,a.slug,v.valor_texto,v.valor_largo,v.valor_fecha
FROM evidencia_valores_atributo v JOIN atributos_tipo_evidencia a USING(id_ate) WHERE v.id_evidencia IN(6,9);
SELECT o.id_evidencia,t.id_trabajo,t.estado,t.proveedor_usado,t.procesado_en,
       JSON_UNQUOTE(JSON_EXTRACT(r.metadatos_json,'$.metodo')) AS metodo
FROM origenes_trabajo_documental o JOIN trabajos_documentales t USING(id_trabajo)
LEFT JOIN resultados_trabajo_documental r USING(id_trabajo) ORDER BY o.id_evidencia;
SELECT texto_extraido AS texto_ocr FROM resultados_trabajo_documental WHERE id_trabajo=4;
SELECT id_revision,id_evidencia,id_trabajo FROM revisiones_metadatos_documentales;
SELECT (SELECT COUNT(*) FROM reglas_clasificacion_documental) AS reglas,
       (SELECT COUNT(*) FROM propuestas_clasificacion_documental) AS propuestas,
       (SELECT COUNT(*) FROM evidencia_aplicaciones_academicas) AS aplicaciones;
SELECT ev.id_evaluacion,ev.id_evidencia,ev.id_usuario_eval,ev.id_instrumento,ev.id_convocatoria,
       ev.id_version,s.codigo,ev.resultado,ev.cerrada,ev.comentario
FROM evaluaciones_versionadas ev JOIN estados_evaluacion s USING(id_estado) ORDER BY ev.id_evaluacion;
SELECT h.id_historial,h.id_evaluacion,h.id_usuario_actor,h.tipo_evento,h.resultado_anterior,h.resultado_nuevo
FROM historial_evaluaciones h ORDER BY h.id_historial;
SELECT p.id_periodo,c.id_convocatoria,c.id_instrumento,c.estado,v.id_version,v.estado AS estado_version
FROM periodos_academicos p JOIN convocatorias c USING(id_periodo,id_instrumento) JOIN versiones_criterios v USING(id_convocatoria)
WHERE c.id_convocatoria IN(3,4,5);
COMMIT;
