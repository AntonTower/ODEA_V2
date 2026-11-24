# Errores detectados y correcciones

| Caso | Antes | Cambio | Después |
|---|---|---|---|
| DO-01 | La ruta Perfil solo mostraba un encabezado | La vista lee y presenta datos de la cuenta autenticada. Se mantuvo la política existente | Nombre, correo y rol visibles. Edición pendiente de un flujo autorizado |
| DO-02 | El expediente leía instrumentos desde la tabla legada y el contador de evaluaciones usaba eventos de historial | El expediente agrupa los instrumentos y cuenta los dictámenes desde el servicio de reportes | Cuatro dictámenes y seis eventos separados. Se confirma SNII en las evidencias evaluadas |
| DO-06 | El worker con proveedor deshabilitado pasó el trabajo 1 a ERROR con mensaje controlado | Se descargaron dependencias para QA y se ejecutó el worker local con Poppler y Tesseract en modo inglés. Se reintentó el trabajo desde la interfaz | Seis trabajos con resultados REQUIERE_REVISION. Tesseract quedó instalado en su ruta predeterminada de Windows aunque se indicó una carpeta dentro de QA |
| DO-08 | El patrón de horas cruzó un salto de línea y leyó 23 del día. Se omitieron fechas ISO y el rótulo Autor en singular | El patrón de horas se limita a la línea, admite etiqueta explícita y evita confundir segmentos de fecha. Se añadió soporte para ISO y Autor | Sobre el texto real de trabajos 1 y 4 se recuperó fecha 2026-11-23 y 12 horas. Trabajo 2 recuperó Emilio Ejemplo. Trabajo 6 conservó fecha y horas vacías. Pasaron 19 comprobaciones de extracción y revisión |
| DO-10 | La pantalla descartaba el mensaje de la operación al recargar cuando no había propuestas | Se muestra la respuesta accesible del servidor si no hay aplicaciones | La interfaz indica que no hay reglas activas con fuentes validadas; cero propuestas y cero aplicaciones persistidas |

El título de artículo inferido y algunas etiquetas de congreso siguen requiriendo revisión. Se preservaron los resultados originales y los ajustes humanos quedan en la tabla de revisiones.
