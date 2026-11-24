# Casos de prueba del Docente

Pruebas ejecutadas sobre la copia aislada del puerto 8088. Los documentos y nombres de los archivos son sintéticos.

| Caso | Acción | Entrada | Esperado |
|---|---|---|---|
| DO-01 | Autenticación y perfil | Docente QA 5 | Rol 4, perfil propio, persistencia y edición habilitada si existe |
| DO-02 | Expediente académico | Docente 5 y evidencias 5–15 | Contrastar totales y consultar historial y dictámenes |
| DO-03 | Carga documental | Constancia, artículo, congreso, PDF escaneado, PNG y constancia incompleta | Propiedad correcta y documentos sintéticos recuperables |
| DO-04 | Duplicado por contenido | Evidencias 9 y 15 | Detectar el mismo PDF con nombre diferente |
| DO-05 | Validación de archivos | PDF, PNG, TXT, extensión falsa, 0 bytes y 10 MiB + 1 byte | Admitir válidos; rechazar inválidos y atributos requeridos vacíos |
| DO-06 | Procesamiento documental | Trabajos 1–6 | Encolar, informar fallas y obtener resultados reales |
| DO-07 | OCR local | PDF escaneado, evidencia 12, trabajo 4 | Recuperar texto de una página rasterizada |
| DO-08 | Extracción por campo | Constancia, artículo, congreso, escaneo e incompleta | Recuperar datos presentes y dejar vacíos los ausentes |
| DO-09 | Corrección y confirmación | Revisiones 1 y 2 de evidencias 9 y 10 | Distinguir sugerencia de corrección y conservar el original |
| DO-10 | Clasificación multiinstrumento | Artículo 10; convocatorias SNII, PRODEP y ESDEPED | Proponer solo coincidencias respaldadas |
| DO-11 | Revisión de la observación | Evidencia 6, evaluación 1 | Consultar observación y registrar una corrección autorizada |
| DO-12 | Consulta de evaluaciones | Evidencias 5, 6, 7 y 10 | Mostrar dictámenes e historial y restringir la escritura |
| DO-13 | Reportes autorizados | SNII, convocatoria 3, versión 2 | Generar PDF y ZIP coherentes y verificar contenido |
| DO-14 | Seguridad defensiva | Sesión Docente y endpoints protegidos | Bloquear operaciones ajenas y accesos sin autorización |
| DO-15 | Persistencia | Docente 5; documentos, trabajos, revisiones y evaluaciones | Reautenticar y cotejar interfaz, SQL y archivos |
| DO-16 | Integración de los cuatro roles | Artículo 10, contexto SNII 1/3/2 | Recorrido real hasta el dictamen y consulta posterior |
