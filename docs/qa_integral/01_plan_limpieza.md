# Plan de limpieza pendiente de autorización

Alcance ejecutable: una copia comprobada de QA con esquema, configuración web y almacén de documentos propios. Los conteos siguientes describen la base local observada, sirven de referencia y deben repetirse en la copia antes de autorizar el lote.

- **A** Preservar íntegramente.
- **B** Vaciar exclusivamente en QA tras autorización. Los conteos actuales de estas tablas son cero.
- **C** Revisar manualmente. Cualquier borrado requiere IDs, evidencia de procedencia de prueba y autorización del alcance.
- **D** No modificar.

| Tabla | Filas | Función | Referencias salientes | Acción | Motivo | Riesgo |
|---|---:|---|---|---|---|---|
| `atributos_tipo_evidencia` | 2 | Definiciones de atributos | `tipos_atributo`, `tipos_de_evidencia` | A | Configuración o acceso persistente | Alto si cambia |
| `calificacion_evidencia` | 0 | Calificaciones legadas | `usuarios`, `evidencias`, `instrumentos` | B | Dato operativo de la copia QA | Alto fuera de QA |
| `categorias_academicas` | 1 | Categorías de versión | `versiones_criterios` | C | Validar origen de prueba y dependencias por ID | Pérdida de configuración académica |
| `convocatorias` | 2 | Convocatorias por periodo | `instrumentos`, `periodos_academicos` | C | Validar origen de prueba y dependencias por ID | Pérdida de configuración académica |
| `criterios_academicos` | 0 | Criterios de versión | `categorias_academicas`, `versiones_criterios` | C | Validar origen de prueba y dependencias por ID | Pérdida de configuración académica |
| `criterio_fuente_normativa` | 0 | Vínculos criterio–fuente | `criterios_academicos`, `fuentes_normativas` | C | Validar origen de prueba y dependencias por ID | Pérdida de configuración académica |
| `estados_evaluacion` | 6 | Catálogo de estados | — | A | Configuración o acceso persistente | Alto si cambia |
| `evaluaciones_versionadas` | 0 | Evaluaciones por versión | `convocatorias`, `estados_evaluacion`, `evidencias`, `instrumentos`, `calificacion_evidencia`, `usuarios`, `versiones_criterios` | B | Dato operativo de la copia QA | Alto fuera de QA |
| `evaluador_instrumento` | 0 | Permisos por instrumento | `usuarios`, `instrumentos` | A | Configuración o acceso persistente | Alto si cambia |
| `evidencias` | 0 | Evidencias y archivo principal | `usuarios`, `tipos_de_evidencia` | B | Dato operativo de la copia QA | Alto fuera de QA |
| `evidencia_criterio_versionado` | 0 | Vínculos evidencia–criterio | `criterios_academicos`, `evidencias` | B | Dato operativo de la copia QA | Alto fuera de QA |
| `evidencia_subcriterio_versionado` | 0 | Vínculos evidencia–subcriterio | `evidencias`, `subcriterios_academicos` | B | Dato operativo de la copia QA | Alto fuera de QA |
| `evidencia_valores_atributo` | 0 | Valores y anexos | `atributos_tipo_evidencia`, `evidencias` | B | Dato operativo de la copia QA | Alto fuera de QA |
| `fuentes_normativas` | 0 | Fuentes por versión | `usuarios`, `versiones_criterios` | C | Validar origen de prueba y dependencias por ID | Pérdida de configuración académica |
| `historial_evaluaciones` | 0 | Auditoría de evaluaciones | `usuarios`, `estados_evaluacion`, `estados_evaluacion`, `evaluaciones_versionadas` | D | Auditoría inmutable y vacía | Bloquea borrado de evaluaciones si aparece actividad |
| `instrumentos` | 3 | Instrumentos base | — | A | Configuración o acceso persistente | Alto si cambia |
| `instrumento_tipo_evidencia` | 3 | Tipos admitidos por instrumento | `instrumentos`, `tipos_de_evidencia` | A | Configuración o acceso persistente | Alto si cambia |
| `periodos_academicos` | 2 | Periodos por instrumento | `instrumentos` | C | Validar origen de prueba y dependencias por ID | Pérdida de configuración académica |
| `requisitos_documentales` | 0 | Requisitos de versión | `versiones_criterios` | C | Validar origen de prueba y dependencias por ID | Pérdida de configuración académica |
| `requisito_criterio` | 0 | Vínculos requisito–criterio | `criterios_academicos`, `requisitos_documentales` | C | Validar origen de prueba y dependencias por ID | Pérdida de configuración académica |
| `requisito_subcriterio` | 0 | Vínculos requisito–subcriterio | `requisitos_documentales`, `subcriterios_academicos` | C | Validar origen de prueba y dependencias por ID | Pérdida de configuración académica |
| `requisito_tipo_documental` | 0 | Vínculos requisito–tipo | `tipos_de_evidencia`, `requisitos_documentales` | C | Validar origen de prueba y dependencias por ID | Pérdida de configuración académica |
| `resultado_criterio_versionado` | 0 | Resultados por criterio | `criterios_academicos`, `evaluaciones_versionadas` | B | Dato operativo de la copia QA | Alto fuera de QA |
| `resultado_subcriterio_versionado` | 0 | Resultados por subcriterio | `evaluaciones_versionadas`, `subcriterios_academicos` | B | Dato operativo de la copia QA | Alto fuera de QA |
| `roles` | 4 | Catálogo de roles | — | A | Configuración o acceso persistente | Alto si cambia |
| `subcriterios_academicos` | 0 | Subcriterios de versión | `criterios_academicos` | C | Validar origen de prueba y dependencias por ID | Pérdida de configuración académica |
| `tipos_atributo` | 13 | Catálogo de tipos de atributo | — | A | Configuración o acceso persistente | Alto si cambia |
| `tipos_de_evidencia` | 1 | Catálogo documental | — | A | Configuración o acceso persistente | Alto si cambia |
| `transiciones_estado_evaluacion` | 0 | Configuración de estados | `estados_evaluacion`, `instrumentos`, `estados_evaluacion` | A | Configuración o acceso persistente | Alto si cambia |
| `usuarios` | 4 | Cuentas, perfil, estado y rol | `roles` por lógica de aplicación | A | Configuración o acceso persistente | Alto si cambia |
| `versiones_criterios` | 1 | Versiones de convocatoria | `convocatorias` | C | Validar origen de prueba y dependencias por ID | Pérdida de configuración académica |
| `uploads/files/` | 4 PDF | Documentos físicos | Sin vínculo comprobado | D | Propiedad y uso pendientes de verificar | Pérdida irreversible |
| Tablas ausentes de migraciones | — | Procesamiento, metadatos y clasificación | Reinspeccionar tras instalar en QA | D | Fuera del esquema observado y del lote | Deriva del alcance |

## Aislamiento y respaldo

1. Identificar el servidor, puerto, esquema, cuenta SQL y raíz de archivos usados por la aplicación QA. Verificar `SELECT DATABASE(), @@hostname, @@port, CURRENT_USER()` desde el mismo entorno de ejecución web y comprobar que ningún servicio ajeno usa ese destino. Usar una cuenta limitada al esquema QA y una copia física independiente. Repetir inventario y evaluación de tablas tras cualquier migración aprobada.
2. Pausar escrituras web y workers de QA durante respaldo y limpieza. Registrar conteos exactos por tabla, distribución por rol, valor de `AUTO_INCREMENT` de usuarios y huellas privadas de filas protegidas en orden de PK, con serialización determinista que incluya nulos y todos los campos.
3. Volcar el esquema QA con `mysqldump --single-transaction --routines --events --triggers --hex-blob --result-file=<ruta_privada>`, usando host, puerto y esquema explícitos. Mantener credenciales fuera de argumentos visibles y del repositorio. Copiar el almacén íntegro, incluidos archivos ocultos y reglas de acceso, conservando rutas relativas. Guardar volcado, archivos y manifiesto SHA-256 en una carpeta fuera de la raíz web con acceso restringido.
4. Comprobar código de salida del volcado, presencia de DDL, datos de tablas pobladas y triggers. Comparar SHA-256 de originales y copias. Importar en un esquema vacío de restauración aislado, comprobar que el volcado no redirige al origen y mantener workers y eventos desactivados durante el ensayo. Comparar estructura, conteos, huellas y referencias. Si el ensayo no es posible, registrar la limitación antes de solicitar autorización.
5. Presentar para autorización el destino verificado, el respaldo, la lista de IDs C aprobables y los archivos verificables. Los cuatro PDF observados quedan fuera del lote.

## Ejecución después de autorizar

1. Confirmar de nuevo el destino, las huellas protegidas, los conteos y la ausencia de actividad concurrente. Mantener `FOREIGN_KEY_CHECKS=1`. Usar `DELETE` en una transacción InnoDB con un registro de operaciones, predicados, IDs y filas afectadas.
2. Orden B: resultados por subcriterio y criterio, evaluaciones versionadas, calificaciones legadas, vínculos evidencia–subcriterio y evidencia–criterio, valores de atributos y evidencias. Preverificar historiales antes de borrar evaluaciones. Si existen filas de historial dependientes, detenerse. Su trigger impide el borrado directo y su FK restringe el borrado de la evaluación. La inmutabilidad se conserva.
3. Orden C, solo para IDs autorizados y tras confirmar ausencia de dependientes persistentes: vínculos de fuentes y requisitos, requisitos y fuentes, subcriterios, criterios, categorías, versiones, convocatorias y periodos. Si aparecen dependencias adicionales en la copia, detenerse y revisar el lote. Actualmente los candidatos con filas son dos periodos, dos convocatorias, una versión y una categoría.
4. Verificar antes del commit los conteos esperados, huellas protegidas, instrumentos y anti-joins de FK y de roles. Ante una discrepancia, ejecutar rollback y registrar el fallo. Evitar DDL y reinicios de secuencias en el lote.
5. Tras confirmar el commit, retirar únicamente copias QA de archivos respaldados cuyo propietario, alcance y falta de referencias restantes se hayan comprobado. Verificar los resultados y registrar cada operación. Ante un fallo posterior al commit, detenerse y restaurar volcado y archivos desde el respaldo verificado en un destino QA limpio. Validar la restauración antes de reactivar los servicios.
