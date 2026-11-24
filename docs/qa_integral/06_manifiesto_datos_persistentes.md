# Manifiesto de datos persistentes

| Elemento | Línea base | Criterio de preservación |
|---|---|---|
| Usuarios | 4 activos, `AUTO_INCREMENT=5` | Filas completas, IDs, perfiles, hashes, estados, asignaciones y secuencia idénticos |
| Roles | 4 | Filas y asignaciones idénticas |
| Instrumentos activos | 1 SNII, 2 PRODEP, 3 ESDEPED | Filas, IDs, estado y configuración idénticos |
| Permisos por instrumento | 0 asignaciones | Preservar todas las filas que existan en el destino autorizado |
| Catálogos y configuración | 13 tipos de atributo, 1 tipo de evidencia, 2 atributos, 3 vínculos instrumento–tipo, 6 estados y 0 transiciones | Preservar tablas y filas con acción A |
| Configuración académica | 2 periodos, 2 convocatorias, 1 versión, 1 categoría | Conservar salvo aprobación por ID tras revisar procedencia y dependencias |
| Archivos sin alcance verificado | 4 PDF y `.htaccess` en el almacén | Conservar documentos y regla de acceso |
| Historial de evaluaciones | 0 filas, 2 triggers de inmutabilidad | Conservar tabla, triggers y cualquier historial existente |
| Respaldo QA y manifiesto SHA-256 | Pendientes | Mantener fuera del acceso web, incluso después de limpiar |
| Registros de fases 2 a 5 | Futuros | Conservar en el mismo entorno QA |


## Inventario persistente al cierre del Prompt 5

| Tabla | ID o clave | Referencia | Rol de origen | Prueba | Estado y relaciones | Captura |
|---|---|---|---|---|---|---|
| usuarios | 1–4 | Cuentas originales | Línea base | Prompt 1 | Cuatro activas, filas idénticas al respaldo | [Inventario](03_inventario_usuarios.md) |
| usuarios | 5, 6, 7 | Docente, Evaluador y Administrador QA | SuperAdministrador | SA-02 | Activos. Evaluador 6 conserva instrumentos 1, 2 y 3 | [Usuarios](capturas/superadministrador/SA-02-usuarios-despues.png) |
| instrumentos | 1, 2, 3, 5 | SNII, PRODEP, ESDEPED y temporal QA | Línea base y SuperAdministrador | SA-03, AD-03 | Tres instrumentos base y temporal QA con rango 0–10 | [Instrumentos QA](capturas/administrador/AD-03-instrumento-despues.png) |
| periodos_academicos | 3, 4, 5 | Periodos QA | SuperAdministrador | SA-04 | Activos, instrumentos 1, 2 y 3 | [Periodos](capturas/superadministrador/SA-04-SNII-despues.png) |
| convocatorias | 3, 4, 5 | Convocatorias QA | SuperAdministrador | SA-05, DO-16 | Activas; periodos 3, 4 y 5 | [Convocatoria](capturas/docente/DO-16-superadmin-convocatoria.png) |
| versiones_criterios | 2, 3, 4 | Versiones 1.0 QA | SuperAdministrador | SA-06, SA-09 | Publicadas; convocatorias 3, 4 y 5 | [Publicación](capturas/superadministrador/SA-09-SNII-publicada.png) |
| categorias_academicas | 2, 3, 4 | Categorías QA | SuperAdministrador | SA-07 | Versiones 2, 3 y 4 | [Estructura](capturas/superadministrador/SA-07-SNII-despues.png) |
| criterios_academicos / subcriterios_academicos | 1, 2, 3 por tabla | Jerarquías QA | SuperAdministrador | SA-07 | Una jerarquía por versión | [Estructura](capturas/superadministrador/SA-07-SNII-despues.png) |
| requisitos_documentales | 2, 3, 4 | Requisitos QA | SuperAdministrador | SA-07 | Vínculos en la misma versión con criterio y subcriterio | [Estructura](capturas/superadministrador/SA-07-SNII-despues.png) |
| fuentes_normativas | 1, 2, 3 | Fuentes técnicas QA | SuperAdministrador | SA-08 | Validadas en QA, asociadas a sus criterios | [Fuente](capturas/superadministrador/SA-08-SNII-validada.png) |
| tipos_de_evidencia | 6, 7, 8, 9 | Tipo temporal, constancia, artículo y congreso QA | SuperAdministrador y Administrador | SA-03, AD-04, DO-16 | Constancia, artículo y congreso asociados a instrumentos 1, 2 y 3 | [Alta de artículo](capturas/docente/DO-16-admin-articulo.png) |
| atributos_tipo_evidencia | 9–13 | Campos de constancia QA | Administrador | AD-04 | Tres requeridos y dos opcionales, tipo 7 | [Atributos](capturas/administrador/AD-04-atributos.png) |
| evidencias | 5, 6, 7, 8 | PDF de fases 3 y 4 | Administrador | AD-07, EV-05 | Docente 5. Originales conservados | [Visor](capturas/evaluador/EV-04-visor.png) |
| evidencias | 9–15 | PDF y PNG sintéticos de fase 5 | Docente | DO-03, DO-04 | Docente 5. Hashes verificados. La evidencia 15 comparte contenido con 9 | [Carga](capturas/docente/DO-05-evidencia-guardada.png) |
| trabajos_documentales / resultados_trabajo_documental | 1–6 | Extracciones locales | Docente y worker | DO-06, DO-07 | REQUIERE_REVISION; trabajo 4 es OCR de evidencia 12 | [OCR](capturas/docente/DO-08-ocr.png) |
| revisiones_metadatos_documentales | 1, 2 | Correcciones y confirmaciones humanas | Docente | DO-09 | Evidencias 9 y 10 con sus originales intactos | [Corrección](capturas/docente/DO-09-metadatos-10-despues.png) |
| evaluaciones_versionadas | 1, 2, 3, 4 | Observada, Aceptada, cerrada QA y observación de artículo | Evaluador y Administrador QA | EV-06, EV-08, EV-CORR, DO-16 | Evidencias 6, 5, 7 y 10, SNII, convocatoria 3 y versión 2 | [Consulta Docente](capturas/docente/DO-16-docente-resultado.png) |
| historial_evaluaciones | 1–6 | Eventos inmutables | Evaluador y Administrador QA | EV-08, EV-CORR, DO-16 | Incluye transición 0 → 1 y observaciones | [Historial](capturas/docente/DO-13-historial.png) |
| evidencia_aplicaciones_academicas / propuestas_clasificacion_documental | — | Clasificación de prueba | Docente | DO-10 | Cero filas. No hay reglas QA activas con fuentes validadas | [Clasificación](capturas/docente/DO-10-clasificacion.png) |

Los IDs, nombres físicos, trabajos y relaciones del Docente se incluyen en el [manifiesto JSON](docente/manifiesto.json). Se conservan la base QA, los archivos y los respaldos.
