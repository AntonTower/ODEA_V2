# Estado inicial — 23 de noviembre de 2026

`config/conexion.php` usa variables `GESTOR_DB_*`. En la terminal revisada no había sobrescrituras. La conexión TCP reproducida en `127.0.0.1:3308` seleccionó `gestor_evidencia_produccion` en MariaDB 10.4.32. El servidor solo mostró ese esquema de aplicación. La configuración efectiva del proceso web y la exclusividad del entorno siguen por confirmar. Se consultó el esquema en modo de lectura.

`INFORMATION_SCHEMA` reportó 31 tablas InnoDB, 54 claves foráneas, 2 triggers de inmutabilidad sobre `historial_evaluaciones` y ninguna vista. Se verificaron las referencias de las 54 claves con anti-joins que respetan columnas compuestas y nulos, además de `usuarios.rol → roles.id_rol`, relación sin FK. Resultado observado de huérfanos: cero.

## Diferencias con el repositorio

- Las tablas del modelo académico y de historial de `20261119` y `20261120` existen.
- Faltan las columnas de SHA-256, MIME y tamaño de `20261118` en `evidencias` y `evidencia_valores_atributo`.
- Faltan `trabajos_documentales`, `origenes_trabajo_documental` y `resultados_trabajo_documental` de `20261121`.
- Falta `revisiones_metadatos_documentales` de `20261122`.
- Faltan `actividades_academicas`, `criterio_actividad`, `reglas_clasificacion_documental`, `evidencia_aplicaciones_academicas`, `propuestas_clasificacion_documental` e `historial_clasificacion_documental` de `20261123`.
- No se observó una tabla independiente de registro general de actividad. Los historiales de evaluación y clasificación tienen los estados de disponibilidad indicados arriba.
- `20261124_gestion_academica.down.sql` revierte el modelo académico y contiene DDL destructivo. Queda fuera de esta limpieza.

## Documentos y Git

`uploads/files/` contiene cuatro PDF que suman 2,812,733 bytes y un `.htaccess`. Las tablas que guardan referencias a archivos principales y anexos tienen cero filas. La pertenencia y el uso de esos PDF siguen sin verificar.

El árbol inicial estaba en `main`, commit `e8cd312`, con 31 cambios rastreados y 32 entradas sin seguimiento. Esta fase añade únicamente documentación de QA. Los conteos por tabla están en el plan.

## Llaves primarias y foráneas observadas

| Tabla | Restricción | Columnas | Referencia | DELETE / UPDATE |
|---|---|---|---|---|
| `atributos_tipo_evidencia` | `fk_ate_tipo_atributo` | `id_tipo_atributo` | `tipos_atributo(id_tipo_atributo)` | RESTRICT / RESTRICT |
| `atributos_tipo_evidencia` | `fk_ate_tipo_evidencia` | `id_tipo_evidencia` | `tipos_de_evidencia(id_tipo_evidencia)` | CASCADE / RESTRICT |
| `atributos_tipo_evidencia` | `PRIMARY` | `id_ate` | — | — |
| `calificacion_evidencia` | `fk_ce_eval` | `id_usuario_eval` | `usuarios(id_usuario)` | RESTRICT / CASCADE |
| `calificacion_evidencia` | `fk_ce_evi` | `id_evidencia` | `evidencias(id_evidencia)` | CASCADE / CASCADE |
| `calificacion_evidencia` | `fk_ce_inst` | `id_instrumento` | `instrumentos(id_instrumento)` | CASCADE / CASCADE |
| `calificacion_evidencia` | `PRIMARY` | `id_calificacion` | — | — |
| `categorias_academicas` | `fk_categoria_version` | `id_version` | `versiones_criterios(id_version)` | RESTRICT / CASCADE |
| `categorias_academicas` | `PRIMARY` | `id_categoria` | — | — |
| `convocatorias` | `fk_conv_instrumento` | `id_instrumento` | `instrumentos(id_instrumento)` | RESTRICT / CASCADE |
| `convocatorias` | `fk_conv_periodo_instrumento` | `id_periodo,id_instrumento` | `periodos_academicos(id_periodo,id_instrumento)` | RESTRICT / CASCADE |
| `convocatorias` | `PRIMARY` | `id_convocatoria` | — | — |
| `criterios_academicos` | `fk_criterio_categoria_version` | `id_categoria,id_version` | `categorias_academicas(id_categoria,id_version)` | RESTRICT / CASCADE |
| `criterios_academicos` | `fk_criterio_version` | `id_version` | `versiones_criterios(id_version)` | RESTRICT / CASCADE |
| `criterios_academicos` | `PRIMARY` | `id_criterio` | — | — |
| `criterio_fuente_normativa` | `fk_criterio_fuente_criterio` | `id_criterio,id_version` | `criterios_academicos(id_criterio,id_version)` | RESTRICT / CASCADE |
| `criterio_fuente_normativa` | `fk_criterio_fuente_fuente` | `id_fuente,id_version` | `fuentes_normativas(id_fuente,id_version)` | RESTRICT / CASCADE |
| `criterio_fuente_normativa` | `PRIMARY` | `id_criterio,id_fuente` | — | — |
| `estados_evaluacion` | `PRIMARY` | `id_estado` | — | — |
| `evaluaciones_versionadas` | `fk_eval_versionada_conv_inst` | `id_convocatoria,id_instrumento` | `convocatorias(id_convocatoria,id_instrumento)` | RESTRICT / CASCADE |
| `evaluaciones_versionadas` | `fk_eval_versionada_estado` | `id_estado` | `estados_evaluacion(id_estado)` | RESTRICT / CASCADE |
| `evaluaciones_versionadas` | `fk_eval_versionada_evidencia` | `id_evidencia` | `evidencias(id_evidencia)` | RESTRICT / CASCADE |
| `evaluaciones_versionadas` | `fk_eval_versionada_instrumento` | `id_instrumento` | `instrumentos(id_instrumento)` | RESTRICT / CASCADE |
| `evaluaciones_versionadas` | `fk_eval_versionada_legada` | `id_calificacion_legada` | `calificacion_evidencia(id_calificacion)` | RESTRICT / CASCADE |
| `evaluaciones_versionadas` | `fk_eval_versionada_usuario` | `id_usuario_eval` | `usuarios(id_usuario)` | SET NULL / CASCADE |
| `evaluaciones_versionadas` | `fk_eval_versionada_version_conv` | `id_version,id_convocatoria` | `versiones_criterios(id_version,id_convocatoria)` | RESTRICT / CASCADE |
| `evaluaciones_versionadas` | `PRIMARY` | `id_evaluacion` | — | — |
| `evaluador_instrumento` | `fk_ei_evaluador` | `id_evaluador` | `usuarios(id_usuario)` | CASCADE / CASCADE |
| `evaluador_instrumento` | `fk_ei_instrumento` | `id_instrumento` | `instrumentos(id_instrumento)` | CASCADE / CASCADE |
| `evaluador_instrumento` | `PRIMARY` | `id_evaluador,id_instrumento` | — | — |
| `evidencias` | `evidencias_ibfk_1` | `id_docente` | `usuarios(id_usuario)` | CASCADE / RESTRICT |
| `evidencias` | `evidencias_ibfk_2` | `id_tipo_evidencia` | `tipos_de_evidencia(id_tipo_evidencia)` | CASCADE / RESTRICT |
| `evidencias` | `PRIMARY` | `id_evidencia` | — | — |
| `evidencia_criterio_versionado` | `fk_evcrit_criterio` | `id_criterio,id_version` | `criterios_academicos(id_criterio,id_version)` | RESTRICT / CASCADE |
| `evidencia_criterio_versionado` | `fk_evcrit_evidencia` | `id_evidencia` | `evidencias(id_evidencia)` | RESTRICT / CASCADE |
| `evidencia_criterio_versionado` | `PRIMARY` | `id_evidencia,id_criterio,id_version` | — | — |
| `evidencia_subcriterio_versionado` | `fk_evsub_evidencia` | `id_evidencia` | `evidencias(id_evidencia)` | RESTRICT / CASCADE |
| `evidencia_subcriterio_versionado` | `fk_evsub_subcriterio` | `id_subcriterio,id_version` | `subcriterios_academicos(id_subcriterio,id_version)` | RESTRICT / CASCADE |
| `evidencia_subcriterio_versionado` | `PRIMARY` | `id_evidencia,id_subcriterio,id_version` | — | — |
| `evidencia_valores_atributo` | `fk_eva_ate` | `id_ate` | `atributos_tipo_evidencia(id_ate)` | CASCADE / RESTRICT |
| `evidencia_valores_atributo` | `fk_eva_evidencia` | `id_evidencia` | `evidencias(id_evidencia)` | CASCADE / RESTRICT |
| `evidencia_valores_atributo` | `PRIMARY` | `id_eva` | — | — |
| `fuentes_normativas` | `fk_fuente_responsable` | `id_responsable_validacion` | `usuarios(id_usuario)` | SET NULL / CASCADE |
| `fuentes_normativas` | `fk_fuente_version` | `id_version` | `versiones_criterios(id_version)` | RESTRICT / CASCADE |
| `fuentes_normativas` | `PRIMARY` | `id_fuente` | — | — |
| `historial_evaluaciones` | `fk_historial_actor` | `id_usuario_actor` | `usuarios(id_usuario)` | SET NULL / CASCADE |
| `historial_evaluaciones` | `fk_historial_estado_anterior` | `id_estado_anterior` | `estados_evaluacion(id_estado)` | RESTRICT / CASCADE |
| `historial_evaluaciones` | `fk_historial_estado_nuevo` | `id_estado_nuevo` | `estados_evaluacion(id_estado)` | RESTRICT / CASCADE |
| `historial_evaluaciones` | `fk_historial_evaluacion` | `id_evaluacion` | `evaluaciones_versionadas(id_evaluacion)` | RESTRICT / CASCADE |
| `historial_evaluaciones` | `PRIMARY` | `id_historial` | — | — |
| `instrumentos` | `PRIMARY` | `id_instrumento` | — | — |
| `instrumento_tipo_evidencia` | `fk_ite_instrumento` | `id_instrumento` | `instrumentos(id_instrumento)` | CASCADE / CASCADE |
| `instrumento_tipo_evidencia` | `fk_ite_tipo` | `id_tipo_evidencia` | `tipos_de_evidencia(id_tipo_evidencia)` | CASCADE / CASCADE |
| `instrumento_tipo_evidencia` | `PRIMARY` | `id_instrumento,id_tipo_evidencia` | — | — |
| `periodos_academicos` | `fk_periodo_instrumento` | `id_instrumento` | `instrumentos(id_instrumento)` | RESTRICT / CASCADE |
| `periodos_academicos` | `PRIMARY` | `id_periodo` | — | — |
| `requisitos_documentales` | `fk_requisito_version` | `id_version` | `versiones_criterios(id_version)` | RESTRICT / CASCADE |
| `requisitos_documentales` | `PRIMARY` | `id_requisito` | — | — |
| `requisito_criterio` | `fk_req_criterio_criterio` | `id_criterio,id_version` | `criterios_academicos(id_criterio,id_version)` | RESTRICT / CASCADE |
| `requisito_criterio` | `fk_req_criterio_req` | `id_requisito,id_version` | `requisitos_documentales(id_requisito,id_version)` | RESTRICT / CASCADE |
| `requisito_criterio` | `PRIMARY` | `id_requisito,id_criterio` | — | — |
| `requisito_subcriterio` | `fk_req_sub_req` | `id_requisito,id_version` | `requisitos_documentales(id_requisito,id_version)` | RESTRICT / CASCADE |
| `requisito_subcriterio` | `fk_req_sub_subcriterio` | `id_subcriterio,id_version` | `subcriterios_academicos(id_subcriterio,id_version)` | RESTRICT / CASCADE |
| `requisito_subcriterio` | `PRIMARY` | `id_requisito,id_subcriterio` | — | — |
| `requisito_tipo_documental` | `fk_req_tipo_documental` | `id_tipo_evidencia` | `tipos_de_evidencia(id_tipo_evidencia)` | RESTRICT / CASCADE |
| `requisito_tipo_documental` | `fk_req_tipo_req` | `id_requisito` | `requisitos_documentales(id_requisito)` | RESTRICT / CASCADE |
| `requisito_tipo_documental` | `PRIMARY` | `id_requisito,id_tipo_evidencia` | — | — |
| `resultado_criterio_versionado` | `fk_rescrit_criterio` | `id_criterio,id_version` | `criterios_academicos(id_criterio,id_version)` | RESTRICT / CASCADE |
| `resultado_criterio_versionado` | `fk_rescrit_evaluacion` | `id_evaluacion,id_version` | `evaluaciones_versionadas(id_evaluacion,id_version)` | RESTRICT / CASCADE |
| `resultado_criterio_versionado` | `PRIMARY` | `id_evaluacion,id_criterio` | — | — |
| `resultado_subcriterio_versionado` | `fk_ressub_evaluacion` | `id_evaluacion,id_version` | `evaluaciones_versionadas(id_evaluacion,id_version)` | RESTRICT / CASCADE |
| `resultado_subcriterio_versionado` | `fk_ressub_subcriterio` | `id_subcriterio,id_version` | `subcriterios_academicos(id_subcriterio,id_version)` | RESTRICT / CASCADE |
| `resultado_subcriterio_versionado` | `PRIMARY` | `id_evaluacion,id_subcriterio` | — | — |
| `roles` | `PRIMARY` | `id_rol` | — | — |
| `subcriterios_academicos` | `fk_subcriterio_criterio_version` | `id_criterio,id_version` | `criterios_academicos(id_criterio,id_version)` | RESTRICT / CASCADE |
| `subcriterios_academicos` | `PRIMARY` | `id_subcriterio` | — | — |
| `tipos_atributo` | `PRIMARY` | `id_tipo_atributo` | — | — |
| `tipos_de_evidencia` | `PRIMARY` | `id_tipo_evidencia` | — | — |
| `transiciones_estado_evaluacion` | `fk_transicion_anterior` | `id_estado_anterior` | `estados_evaluacion(id_estado)` | RESTRICT / CASCADE |
| `transiciones_estado_evaluacion` | `fk_transicion_instrumento` | `id_instrumento` | `instrumentos(id_instrumento)` | RESTRICT / CASCADE |
| `transiciones_estado_evaluacion` | `fk_transicion_nuevo` | `id_estado_nuevo` | `estados_evaluacion(id_estado)` | RESTRICT / CASCADE |
| `transiciones_estado_evaluacion` | `PRIMARY` | `id_transicion` | — | — |
| `usuarios` | `PRIMARY` | `id_usuario` | — | — |
| `versiones_criterios` | `fk_version_convocatoria` | `id_convocatoria` | `convocatorias(id_convocatoria)` | RESTRICT / CASCADE |
| `versiones_criterios` | `PRIMARY` | `id_version` | — | — |
