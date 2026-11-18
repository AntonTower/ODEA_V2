# OCR y extracción de metadatos en ODEA

## Alcance implementado

- El proveedor local usa Poppler `pdftotext` para PDF con texto recuperable.
- Si el PDF devuelve menos de 10 caracteres útiles, convierte hasta 12 páginas a PNG con `pdftoppm` y aplica Tesseract por página. JPG, PNG y WEBP usan Tesseract.
- El archivo original nunca se modifica. Los raster temporales del OCR se eliminan al terminar.
- El reconocimiento de esquema y la extracción están desacoplados como `DocumentoIdentificationProvider` y `DocumentoMetadataProvider`. Los esquemas configurables viven en `config/esquemas_metadatos_documentales.php`.
- Se incluyen esquemas iniciales para artículos, libros/capítulos, constancias/diplomas, tesis/tutorías, ponencias/congresos y documentos generales. Las reglas buscan etiquetas y patrones explícitos; no asignan datos faltantes.
- Cada campo conserva valor (o `null`), fuente por línea, fragmento, confianza estimada y estado: explícito, inferido, ambiguo o no encontrado. Varios valores contradictorios se exponen como candidatos sin elegir uno.
- Las coincidencias basadas en reglas son extracción asistida, no clasificación académica ni autenticación documental. Incluso una coincidencia explícita queda en revisión humana.
- El pipeline cambió a versión 2 para separar sus resultados de los de la infraestructura anterior. Los trabajos previos no se reescriben ni se vuelven a ejecutar automáticamente; al volver a encolar un archivo tras cambiarlo/guardarlo, se crea la clave correspondiente a esta versión.
- La revisión compara el original privado con los campos detectados. Las correcciones se guardan como versiones append-only; reemplazar un valor confirmado exige autorización explícita y cada envío usa control de concurrencia.

## Configuración y dependencias

Por defecto `ODEA_DOCUMENT_PROCESSOR=disabled`; no se envía información a servicios externos. Para procesamiento local, el administrador debe configurar el proceso de Apache y del worker:

```text
ODEA_DOCUMENT_PROCESSOR=local
ODEA_PDFTOTEXT_BIN=pdftotext
ODEA_PDFTOPPM_BIN=pdftoppm
ODEA_TESSERACT_BIN=tesseract
ODEA_OCR_LANG=spa+eng
```

Aplicar las migraciones en orden, incluida `sql/migrations/20261122_revision_metadatos_documentales.sql`, sobre un respaldo verificado. No se ejecutan automáticamente. No se requiere Composer ni una API externa. El worker sigue siendo necesario:

```powershell
C:\xampp\php\php.exe scripts\procesamiento-documental-worker.php --max=20
```

En el entorno XAMPP revisado, PHP cuenta con `mysqli`, `mbstring`, `fileinfo` y `proc_open`. No se encuentran `pdftotext`, `pdftoppm` ni `tesseract` en `PATH`; por tanto, el OCR real de PDF escaneado e imágenes no se pudo ejecutar en esta máquina. El modo `local` dará error recuperable hasta que el administrador configure esos binarios. La recepción de evidencias se conserva aunque el procesador esté deshabilitado o falle.

## Precisión sintética y límites

La prueba determinista de artículo sintético identificó correctamente **7 de 7 campos** (título, autores, revista, año, DOI, ISSN y editorial). Casos adicionales comprueban campos ambiguos, campos ausentes, fuentes, confianza, snapshots, autorización de reemplazo e inmutabilidad. Este resultado mide el conjunto sintético de reglas, no constituye una estimación de precisión sobre documentos reales ni sobre OCR.

No se usa aprendizaje automático ni proveedor de IA externo. Los patrones dependen de etiquetas reconocibles; cambios de idioma, estructura, tablas, sellos, texto torcido o manuscrito pueden producir campos vacíos o incorrectos. La confianza es una heurística y no una probabilidad calibrada. Un sello o firma no se interpreta como autenticidad. La revisión no guarda valores dentro de atributos académicos existentes ni modifica evaluaciones.

## Matriz manual pendiente de OCR real

Tras configurar Poppler y Tesseract en un entorno privado, probar documentos sintéticos: PDF con texto, PDF escaneado, JPG/PNG, constancia con sello, escaneo de baja legibilidad, artículo con varios autores, documento sin fecha y texto contradictorio. Registrar por campo los valores correctos, incorrectos, ambiguos, inferidos y no encontrados; no reportar solo exactitud por documento. Verificar que el original mantenga el mismo SHA-256, que el raster temporal se elimine, que los campos no confirmados puedan corregirse y que los confirmados no cambien sin autorización.

Ejecutar la prueba aislada con `C:\xampp\php\php.exe tests\metadatos_documentales.php`. La prueba no usa documentos personales ni llama proveedores externos.
