# Resultados del Evaluador

Se operó la aplicación real mediante Chrome en el puerto 8088. Los resultados vigentes y el historial se verificaron también en MariaDB.

| ID | Acción | Entrada | Esperado | Observado | Estado | Visual | SQL |
|---|---|---|---|---|---|---|---|
| EV-01 | Acceso y salida | Evaluador QA 6 | Inicio de sesión, dashboard y menú del rol 3; salida válida | Acceso por formulario, navegación a Evaluación y cierre de sesión comprobados | APROBADA | [Captura](../capturas/evaluador/EV-01-dashboard.png) | [EV-01](consultas_validacion.sql) |
| EV-02 | Instrumentos y alcance | Instrumentos asignados 1–3 y temporal 5 | Mostrar SNII, PRODEP y ESDEPED y rechazar instrumento 5 | Selector con los tres asignados; historial rechazado para 5 y reporte 403; expediente no presente en el menú del Evaluador | APROBADA | [Captura](../capturas/evaluador/EV-02-instrumentos.png) | [EV-02](consultas_validacion.sql) |
| EV-03 | Bandeja de pendientes | Evidencias QA 5–8, docente 5 | Consultar propietario, tipo, filtros, búsqueda y contexto | Búsqueda por título, filtro SNII y estado probados. El contexto de convocatoria y versión está en el modal; propietario y tipo cotejados | APROBADA | [Captura](../capturas/evaluador/EV-03-evidencias-pendientes.png) | [EV-03](consultas_validacion.sql) |
| EV-04 | Documento protegido | Evidencia 5 | Abrir visor y consultar PDF y atributos | Visor por controlador con sesión. El contenido respondió PDF 200, firma %PDF- y 1,659 bytes. Tipo, fecha, docente y atributos consultados en pantalla y SQL | APROBADA | [Captura](../capturas/evaluador/EV-04-visor.png) | [EV-04](consultas_validacion.sql) |
| EV-05 | Completitud | Evidencia 5 completa sin opcionales; evidencia 8 sin obligatorios | Permitir evaluar la primera y mostrar y bloquear la segunda | La 5 se guardó; la 8 aparece con 0 % y sus campos sin valores. Guardar la 8 fue rechazado y no creó evaluación | APROBADA | [Captura](../capturas/evaluador/EV-05-rechazo.png) | [EV-05](consultas_validacion.sql) |
| EV-06 | Observación | Evidencia 6, SNII, versión 2 y convocatoria 3 | Guardar observación y mostrarla al propietario | Evaluación 1 Observada, historial 1, actor 6. El docente 5 recuperó la observación con su sesión desde la consulta de historial | APROBADA | [Captura](../capturas/evaluador/EV-06-observacion.png) | [EV-06](consultas_validacion.sql) |
| EV-07 | Primera evaluación | Evidencia 5, SNII, instrumento binario | Guardar resultado y contexto de QA | Evaluación 2, primera revisión con 0 y comentario técnico. Historial 2 conserva el resultado inicial | APROBADA | [Captura](../capturas/evaluador/EV-07-evaluacion.png) | [EV-07](consultas_validacion.sql) |
| EV-08 | Revisión e historial | Evaluación 2 | Guardar resultado vigente y preservar el anterior | La evaluación vigente pasó a 1 y Aceptada. Historial 3 conserva 0 → 1, comentarios, contexto y actor | APROBADA | [Captura](../capturas/evaluador/EV-08-historial.png) | [EV-08](consultas_validacion.sql) |
| EV-09 | Contexto académico | Versiones 2, 3, 4 | Separar ofertas por instrumento y conservar el contexto evaluado | SNII ofrece 2, PRODEP 3 y ESDEPED 4. Evaluaciones 1 y 2 conservan convocatoria 3 y versión 2; estados publicados permanecen | APROBADA | [Captura](../capturas/evaluador/EV-09-resultados.png) | [EV-09](consultas_validacion.sql) |
| EV-10 | Controles defensivos | Sesión rol 3 e instrumento temporal 5 | Rechazar operaciones fuera del alcance autorizado | AJAX con CSRF inválido dio 419. Gestión de Usuarios redirigió al dashboard. Historial y reporte del instrumento 5 rechazados | APROBADA | [Captura](../capturas/evaluador/EV-10-permisos.png) | [EV-10](consultas_validacion.sql) |
| EV-11 | Consultas y reportes | SNII con evidencia 5 aceptada mediante versión 2 | Consultar aceptadas y generar exportaciones autorizadas coherentes | El filtro Completa de SNII mostró la evidencia 5 tras corregir el conteo. Listado de descargas vacío, PDF válido pero vacío y ZIP 404, porque consultan calificaciones legadas | FALLIDA EN EXPORTACIONES | [Captura](../capturas/evaluador/EV-11-filtro-completas.png) | [EV-11](consultas_validacion.sql) |
| EV-12 | Persistencia entre sesiones | Evaluaciones 1 y 2 | Recuperar evaluación, observación y eventos al volver a entrar | Sesión nueva recuperó 1 y Aceptada en la evidencia 5 y la observación en la 6 al seleccionar la versión 2; historial confirmado | APROBADA | [Captura](../capturas/evaluador/EV-12-evaluacion-recuperada.png) | [EV-12](consultas_validacion.sql) |

## Subcasos de la ejecución inicial

- **EV-03 y EV-04**. El ordenamiento interactivo, las clasificaciones y el cotejo de evidencia con requisitos de versión no se probaron. Los atributos requeridos del tipo sí se verificaron. El esquema QA aún carece del módulo de clasificación.
- **EV-05**. La muestra incompleta se creó por el flujo normal de alta y carece de los tres valores obligatorios. La captura muestra la evidencia antes de completar atributos, el 0 % y el rechazo.
- **EV-10**. Sin evaluación cerrada QA, la modificación de una evaluación cerrada quedó sin ejecutar. No se ejecutaron subcasos de escritura fuera de alcance por cambio de IDs. Los rechazos de lectura por alcance, CSRF y acceso a ruta administrativa sí se observaron.
- **EV-11**. El PDF y ZIP de evaluaciones versionadas no se validaron con contenido. El reporte legado no incluye la aceptación versionada. Véase el hallazgo en [errores detectados](errores_detectados.md).

La evidencia 5 se conserva Aceptada. La evidencia 6 se conserva Observada, con el comentario solicitado. La evidencia 7 permanece disponible. La evidencia 8 permanece incompleta a propósito. No se borraron registros. La evaluación y la observación pertenecen al docente QA 5, se guardaron por el evaluador QA 6 y quedaron en SNII, convocatoria 3 y versión 2.


## Verificación final del Prompt 4

Se repitieron las pruebas en `odea_qa` por el navegador real, usando las cuentas QA existentes y consultas SQL de solo lectura para cotejar el estado final.

| Subcaso | Antes | Resultado final | Evidencia |
|---|---|---|---|
| Listado SNII, convocatoria 3, versión 2 | Colección vacía | HTTP 200. Aceptada devuelve solo la evidencia 5. Observada devuelve solo la 6 | [Listado](../capturas/evaluador/EV-CORR-listado.png) |
| PDF del contexto aceptado | PDF vacío | HTTP 200 y firma PDF. Se descomprimieron sus streams y se verificaron título, docente, estado, convocatoria y comentario de la evidencia 5 | [Visor](../capturas/evaluador/EV-CORR-pdf.png) · [PDF](reporte-snii-v2.pdf) |
| ZIP del contexto aceptado | HTTP 404 | HTTP 200. Al solicitar las evidencias QA 5, 6 y 8, el ZIP contiene solo el PDF 5, de 1,659 bytes. Coincide con el SHA-256 del original | [Selección del ZIP](../capturas/evaluador/EV-CORR-seleccion-zip.png) · [ZIP comprobado](evidencias-snii-v2.zip) |
| Evidencia 5 e historial | Evaluación 2, resultado 1 e historial 2 y 3 | Mismos registros tras una sesión nueva. Historial conserva 0 → 1 y ambos comentarios | [Historial](../capturas/evaluador/EV-CORR-historial-5.png) · [Persistencia](../capturas/evaluador/EV-CORR-persistencia.png) |
| Observación de evidencia 6 | Evaluación 1, estado Observada | Mismo comentario y estado; aparece para el docente propietario en su expediente y queda fuera de aceptadas | [Observación](../capturas/evaluador/EV-CORR-observacion-6.png) |
| Evidencia incompleta 8 | Cero evaluaciones | El guardado normal fue rechazado. SQL vuelve a mostrar cero evaluaciones y el archivo queda fuera de aceptadas | [Rechazo](../capturas/evaluador/EV-CORR-incompleta-8.png) |
| Evaluación cerrada de QA | El alta dejó un evento CIERRE con la fila abierta | Se corrigió el guardado inicial. Se completó el cierre de la evaluación 3 sobre la evidencia 7. El guardado normal del Evaluador se rechazó y no añadió un evento | [Consulta](../capturas/evaluador/EV-CORR-cerrada.png) · [Rechazo](../capturas/evaluador/EV-CORR-cerrada-rechazo.png) |
| Alcance y CSRF | Controles parciales | Listado del instrumento 5 dio HTTP 403. La petición de escritura con token inválido dio 419. Se comprobó el guardado normal sobre una evaluación cerrada, sin alterar las evaluaciones 1 y 2 | [Fuera de alcance](../capturas/evaluador/EV-CORR-fuera-de-alcance.png) |

La selección ZIP quedó capturada. Chromium reconoció la respuesta como descarga, pero no se obtuvo una captura del archivo terminado en el gestor de descargas. El archivo enlazado es la respuesta ZIP obtenida en la sesión del Evaluador y verificada byte a byte.

Las escrituras dirigidas con IDs manipulados y la modificación de versiones publicadas quedaron sin ejecutar. Se verificaron por revisión defensiva los controles de instrumento, propietario del dictamen y consistencia convocatoria–versión. El caso de cierre sí se ejecutó de extremo a extremo. No se modificaron las evaluaciones ni el historial requeridos para el Prompt 5.
