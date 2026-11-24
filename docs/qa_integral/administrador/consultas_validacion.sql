-- Ejecutar con el esquema odea_qa en la instancia local del puerto 3318.
START TRANSACTION READ ONLY;
-- AD-01, AD-02
SELECT id_usuario,rol,activo,apellidom FROM usuarios WHERE id_usuario IN (5,6,7);
-- AD-03, AD-04
SELECT id_instrumento,abreviatura,tipo_calificacion,min_calificacion,max_calificacion FROM instrumentos WHERE id_instrumento IN (1,2,3,5);
SELECT t.id_tipo_evidencia,t.nombre_tipo,t.descripcion,i.id_instrumento
FROM tipos_de_evidencia t JOIN instrumento_tipo_evidencia i USING(id_tipo_evidencia)
WHERE t.id_tipo_evidencia=7;
SELECT id_ate,nombre_atributo,id_tipo_atributo,requerido FROM atributos_tipo_evidencia WHERE id_tipo_evidencia=7 ORDER BY orden;
-- AD-05, AD-06
SELECT p.id_periodo,p.estado AS estado_periodo,c.id_convocatoria,c.estado AS estado_convocatoria,v.id_version,v.estado AS estado_version
FROM periodos_academicos p JOIN convocatorias c USING(id_periodo,id_instrumento)
JOIN versiones_criterios v USING(id_convocatoria) WHERE c.id_convocatoria IN(3,4,5);
SELECT a.id_version,a.id_categoria,c.id_criterio,f.id_fuente,f.validacion_estado
FROM categorias_academicas a JOIN criterios_academicos c USING(id_categoria,id_version)
JOIN criterio_fuente_normativa cf USING(id_criterio,id_version)
JOIN fuentes_normativas f USING(id_fuente,id_version) WHERE a.id_version IN(2,3,4);
-- AD-07 a AD-09, AD-12
SELECT id_evidencia,titulo,id_docente,id_tipo_evidencia,archivo,archivo_sha256,archivo_mime,archivo_bytes FROM evidencias WHERE id_tipo_evidencia=7;
SELECT v.id_evidencia,a.slug,v.valor_texto,v.valor_fecha FROM evidencia_valores_atributo v JOIN atributos_tipo_evidencia a USING(id_ate)
WHERE v.id_evidencia IN(5,6,7) ORDER BY v.id_evidencia,a.orden;
SELECT COUNT(*) AS filas_de_cargas_rechazadas FROM evidencias
WHERE titulo IN('QA_ODEA_Constancia_ExtensionIncorrecta','QA_ODEA_Constancia_ExcedeLimite');
-- AD-10, AD-11
SELECT id_evaluador,id_instrumento FROM evaluador_instrumento WHERE id_evaluador=6 ORDER BY id_instrumento;
SELECT (SELECT COUNT(*) FROM calificacion_evidencia) AS calificaciones,
       (SELECT COUNT(*) FROM evaluaciones_versionadas) AS evaluaciones;
COMMIT;
