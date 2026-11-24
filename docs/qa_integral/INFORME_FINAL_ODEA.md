# Informe final de QA de ODEA

**23 de noviembre de 2026 · Dictamen: apto para demostración en QA con limitaciones.** Se verificó el recorrido de los cuatro roles con autenticación real, procesamiento documental local, revisión humana, evaluación versionada y consulta por el Docente. La extracción requiere revisión humana y la auditoría de permisos conserva subcasos pendientes.

## Ambiente y versión

- Aplicación `http://127.0.0.1:8088`, MariaDB 10.4.32 en `127.0.0.1:3318`, esquema `odea_qa` y almacén físico propio.
- PHP 8.0.30. Punto de partida Git `e8cd312` de `main`, más cambios de trabajo preexistentes y correcciones locales de esta auditoría. No corresponde a una versión empaquetada.
- Se conservó el respaldo verificado del Prompt 1 y el volcado privado anterior al Prompt 5. Las migraciones documentales se aplicaron exclusivamente en QA. Poppler 26.09.0 y Tesseract 5.4.0 procesaron archivos sintéticos mediante el worker local. Tesseract quedó instalado en la ruta predeterminada del equipo. No se usó un proveedor externo.

## Resumen de casos

Los estados se cuentan por ID de caso completo en las matrices de roles según su recorrido principal y última repetición. Las pruebas parciales o condicionales se registran por separado. El Prompt 1 fue de preparación y no agrega IDs a este total.

| Fase | IDs | Aprobadas | Fallidas | Bloqueadas | No ejecutadas completas |
|---|---:|---:|---:|---:|---:|
| [SuperAdministrador](superadministrador/resultados.md) | 10 | 10 | 0 | 0 | 0 |
| [Administrador](administrador/resultados.md) | 12 | 12 | 0 | 0 | 0 |
| [Evaluador y repetición de reportes](evaluador/resultados.md) | 12 | 12 | 0 | 0 | 0 |
| [Docente e integración](docente/resultados.md) | 16 | 13 | 1 | 2 | 0 |
| **Total** | **50** | **47** | **1** | **2** | **0** |

El contador de aprobadas incluye casos con un recorrido principal verificado y subcasos pendientes. La extracción estructurada DO-08 es la falla abierta. DO-01 y DO-14 se contabilizan bloqueadas porque quedó pendiente una parte exigida. Las exportaciones EV-11 se contabilizan aprobadas tras verificar el generador y el contenido en la repetición.

## Evidencia visual y archivos

Se conservaron **204 capturas reales**: 82 de SuperAdministrador, 40 de Administrador, 37 de Evaluador y 45 de Docente. Los informes enlazan cada captura representativa. El [reporte visual](INFORME_VISUAL_ODEA.html) reúne la secuencia y los resultados de los cuatro roles.

- [PDF SNII descargado por el Docente](docente/reporte-docente-snii.pdf). Incluye docente, evaluador, convocatoria, versión, estado y comentario.
- [ZIP SNII descargado por el Docente](docente/evidencias-docente-snii.zip). Contiene exclusivamente el PDF de la evidencia 5. SHA-256 coincide con MariaDB y el original.

## Datos persistentes e integridad

- **7 usuarios** activos. Las cuatro cuentas originales coinciden columna por columna con el respaldo restaurado, incluidos hashes y perfiles. Las tres cuentas QA continúan activas.
- **5 periodos, 5 convocatorias, 4 versiones y 4 categorías**. Las convocatorias QA 3–5 están activas y las versiones 2–4 publicadas. Tres criterios, tres subcriterios, tres requisitos y tres fuentes de demostración permanecen asociados.
- **11 evidencias y 11 archivos referenciados** del docente QA 5. Se verificaron los 11 SHA-256. Se conservaron también los cuatro PDF preexistentes del almacén, fuera del lote de QA.
- **6 trabajos, 6 resultados de extracción y 2 revisiones humanas**. La evidencia duplicada 15 comparte trabajo con la 9. El OCR del PDF 12 recuperó los cinco datos de referencia.
- **4 dictámenes versionados, 6 eventos de historial y 0 calificaciones legadas**. La evidencia 5 conserva la aceptación, la 6 la observación original y la 10 una observación nueva. La 7 conserva el cierre de QA. La 8 permanece sin evaluación.
- **0 reglas activas, 0 aplicaciones y 0 propuestas de clasificación**. El motor devolvió ausencia de coincidencias respaldadas. Los esquemas tienen **81 claves foráneas verificadas, cero huérfanos**.
- Se cotejó cada fila de las tablas originales con `odea_qa_restore`. No se observaron diferencias en las filas iniciales. Continúan disponibles los datos y documentos de las cinco fases. [Manifiesto persistente](06_manifiesto_datos_persistentes.md)

## Comprobación reproducible

Ejecutar las consultas contra la instancia QA, con una cuenta de lectura o el acceso autorizado existente. [SQL SuperAdministrador](superadministrador/consultas_validacion.sql) · [SQL Administrador](administrador/consultas_validacion.sql) · [SQL Evaluador](evaluador/consultas_validacion.sql) · [SQL Docente](docente/consultas_validacion.sql)

## Hallazgos, correcciones y repetición

- **Administración académica.** Se corrigieron selectores que omitían colecciones válidas, campos vacíos en multiselector y la respuesta de código duplicado. Las tres estructuras se crearon, publicaron y activaron con autorización. Se corrigió el perfil consultado desde Usuarios y la ruta del rol Administrador. [Hallazgos SuperAdministrador](superadministrador/errores_detectados.md) · [Hallazgos Administrador](administrador/errores_detectados.md)
- **Carga y evaluación.** Se configuró una carpeta temporal privada para evitar avisos de PHP en el JSON. Se corrigieron la recuperación del dictamen por versión, el historial visual, la visibilidad de evidencias incompletas, la persistencia del cierre y la consulta común de reportes. Se verificaron el rechazo al guardar una cerrada y el contenido de PDF y ZIP. [Hallazgos Evaluador](evaluador/errores_detectados.md)
- **Docente y procesamiento.** Se hizo visible el perfil propio, se separaron dictámenes y eventos en el expediente y se corrigieron los patrones de horas, fecha ISO y autor en singular. La repetición sobre texto real confirmó las correcciones, incluida la ausencia de valores en el documento incompleto. Se guardaron correcciones humanas sin modificar PDF. [Hallazgos Docente](docente/errores_detectados.md)

## Límites, riesgos y recomendaciones

1. **Extracción estructurada.** El título inferido y algunas etiquetas de artículo y congreso siguen fallando. Ampliar las pruebas de variaciones de etiquetas con documentos sintéticos y mantener confirmación humana solo para datos verificables.
2. **Clasificación.** Se verificó la ausencia de propuestas. Para demostrar coincidencias positivas, falta configurar reglas QA explícitas por instrumento, con fuentes validadas y criterios de prueba identificables.
3. **Permisos.** Se verificaron redirección administrativa, rechazo de escritura sin autorización, CSRF inválido y documento sin sesión. Quedaron pendientes los subcasos cruzados, la expiración de sesión del Docente y la confirmación de política para asignar rol 1. Completar una matriz defensiva con cuentas y documentos de prueba con alcance separado.
4. **Perfil y seguimiento docente.** El perfil es visible en modo de consulta. La edición propia y una acción explícita de reenvío a revisión requieren confirmar los flujos institucionales. La corrección documental y el historial de observaciones sí persisten.
5. **Operación del worker.** El worker local se ejecutó por CLI en QA. Pendiente probar una operación recurrente, el estado transitorio `EN_PROCESO` en interfaz y medir OCR en español con documentos de mayor complejidad. Mantener el entorno y dependencias documentados.
6. **Casos anteriores.** Los informes por fase conservan subcasos de filtros, estados alternativos, permisos y cobertura de interfaz. La ausencia de huérfanos y las pruebas defensivas ejecutadas respaldan la demostración en QA. La auditoría de seguridad y la precisión documental integral siguen abiertas.

**Dictamen técnico.** ODEA puede demostrar el flujo académico y documental en la copia QA con datos persistentes y exportaciones cotejadas. Los pendientes de extracción, clasificación positiva y permisos impiden aceptar el sistema como completamente validado.
