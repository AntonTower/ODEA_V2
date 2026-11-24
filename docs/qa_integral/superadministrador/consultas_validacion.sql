-- Seleccionar explícitamente el esquema QA verificado al conectar.
-- SA-02 identifica las cuentas mediante el prefijo de nombre; registrar sus IDs al crearlas.
SELECT DATABASE() AS esquema, @@hostname AS servidor, @@port AS puerto;
START TRANSACTION READ ONLY;

-- SA-01 Autenticación
SELECT u.id_usuario, u.rol, u.activo FROM usuarios u WHERE u.rol = 1 AND u.activo = 1;

-- SA-02 Usuarios
SELECT id_usuario, rol, activo FROM usuarios WHERE LEFT(nombre, 7) = 'QA_ODEA' ORDER BY id_usuario;

-- SA-03 Instrumentos
SELECT i.id_instrumento, i.abreviatura, i.activo, t.id_tipo_evidencia FROM instrumentos i LEFT JOIN instrumento_tipo_evidencia t USING (id_instrumento) WHERE i.abreviatura IN ('SNII','PRODEP','ESDEPED') OR LEFT(i.abreviatura,7) = 'QA_ODEA';

-- SA-04 Periodos
SELECT id_periodo, id_instrumento, codigo, fecha_inicio, fecha_fin, estado FROM periodos_academicos WHERE LEFT(codigo, 7) = 'QA_ODEA';

-- SA-05 Convocatorias
SELECT c.id_convocatoria,c.id_periodo,c.id_instrumento,c.codigo,c.estado,p.codigo AS periodo FROM convocatorias c JOIN periodos_academicos p USING (id_periodo,id_instrumento) WHERE LEFT(c.codigo,7) = 'QA_ODEA';

-- SA-06 Versiones
SELECT v.id_version,v.id_convocatoria,c.id_instrumento,v.codigo_version,v.estado FROM versiones_criterios v JOIN convocatorias c USING (id_convocatoria) WHERE LEFT(c.codigo,7) = 'QA_ODEA';

-- SA-07 Criterios
SELECT a.id_categoria,a.id_version,k.id_criterio,s.id_subcriterio FROM categorias_academicas a LEFT JOIN criterios_academicos k USING (id_categoria,id_version) LEFT JOIN subcriterios_academicos s USING (id_criterio,id_version) WHERE LEFT(a.codigo,7) = 'QA_ODEA';
SELECT r.id_requisito,r.id_version,k.id_criterio,s.id_subcriterio,t.id_tipo_evidencia FROM requisitos_documentales r LEFT JOIN requisito_criterio k USING (id_requisito,id_version) LEFT JOIN requisito_subcriterio s USING (id_requisito,id_version) LEFT JOIN requisito_tipo_documental t USING (id_requisito) WHERE LEFT(r.codigo,7) = 'QA_ODEA';

-- SA-08 Fuentes
SELECT f.id_fuente,f.id_version,f.validacion_estado,v.id_criterio FROM fuentes_normativas f LEFT JOIN criterio_fuente_normativa v USING (id_fuente,id_version) WHERE LEFT(f.nombre_documento,7) = 'QA_ODEA';

-- SA-09 Publicación y activación
SELECT c.id_convocatoria,c.estado AS estado_convocatoria,v.id_version,v.estado AS estado_version FROM convocatorias c LEFT JOIN versiones_criterios v USING (id_convocatoria) WHERE LEFT(c.codigo,7) = 'QA_ODEA';

-- SA-10 Validaciones y seguridad
SELECT TABLE_NAME,CONSTRAINT_NAME,CONSTRAINT_TYPE FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('usuarios','convocatorias','periodos_academicos') ORDER BY TABLE_NAME,CONSTRAINT_NAME;

-- Validación de alcances y comparación de cuentas originales con el ensayo.
SELECT e.id_evaluador, e.id_instrumento
FROM evaluador_instrumento e JOIN usuarios u ON u.id_usuario = e.id_evaluador
WHERE LEFT(u.nombre,7) = 'QA_ODEA' ORDER BY e.id_evaluador, e.id_instrumento;

SELECT COUNT(*) AS cuentas_originales,
       SUM(q.id_usuario IS NOT NULL AND
           BINARY q.nombre <=> BINARY r.nombre AND
           BINARY q.apellidop <=> BINARY r.apellidop AND
           BINARY q.apellidom <=> BINARY r.apellidom AND
           BINARY q.correo <=> BINARY r.correo AND
           BINARY q.password <=> BINARY r.password AND
           q.rol <=> r.rol AND q.activo <=> r.activo AND
           q.created_at <=> r.created_at AND q.updated_at <=> r.updated_at) AS cuentas_identicas
FROM odea_qa_restore.usuarios r LEFT JOIN usuarios q USING (id_usuario);

COMMIT;
