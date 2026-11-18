# Procesamiento documental de ODEA

La ampliación de OCR local, esquemas de metadatos y validación humana está descrita en [`OCR_METADATOS.md`](OCR_METADATOS.md). Requiere la migración `20261122_revision_metadatos_documentales.sql` después de las migraciones documentales previas.

## Flujo

La recepción y validación continúan en los controladores actuales. Tras persistir un archivo, se agrega una referencia a la cola; si la cola no está disponible, la evidencia se conserva y la respuesta de carga no falla por ello.

La tabla `trabajos_documentales` representa un procesamiento por SHA-256, versión de pipeline y proveedor solicitado. `origenes_trabajo_documental` enlaza el trabajo con una o más evidencias/archivos de atributo sin compartir esos vínculos en la interfaz. `resultados_trabajo_documental` almacena el texto extraído y los metadatos. El texto se trata como información académica privada y solo se entrega a usuarios autorizados sobre alguna evidencia vinculada.

La cola evita procesar dos veces un documento sin cambios. El worker reclama cada trabajo dentro de una transacción y establece un lease/token; el segundo worker no puede reclamar la misma fila. Los leases vencidos se devuelven a la cola hasta alcanzar el máximo de intentos. Los trabajos fallidos pueden reintentarse desde la interfaz por el propietario docente o roles administrativos 1 y 2.

El proveedor `disabled` es el valor predeterminado. `local` usa `pdftotext` para PDF y Tesseract para imágenes. El procesamiento local no identifica oficialmente documentos ni clasifica criterios. El resultado extraído siempre se presenta como material que requiere validación humana; no crea ni cambia calificaciones.

## Dependencias

- PHP CLI 8.0 o posterior, con `mysqli`, `mbstring`, `fileinfo` y `json`.
- MariaDB/MySQL y aplicación previa de `sql/migrations/20261121_procesamiento_documental.sql` después de las migraciones anteriores.
- Para extracción local: Poppler `pdftotext`; Tesseract opcional para JPG, PNG y WEBP. Deben estar instalados en el servidor privado, nunca en el navegador.
- No hay dependencias Composer ni servicios externos obligatorios. No se envían documentos fuera del servidor.

En el entorno XAMPP inspeccionado para esta fase, PHP CLI es 8.0.30 y están disponibles `mysqli`, `mbstring`, `fileinfo` y `proc_open`. `pdftotext` y `tesseract` no aparecen instalados en `PATH`; por eso el modo `local` requiere instalarlos/configurarlos antes de procesar documentos. El modo predeterminado permanece deshabilitado.

## Configuración

Configuración mediante variables de entorno del proceso PHP/Apache y del worker:

```text
ODEA_DOCUMENT_PROCESSOR=disabled
ODEA_PDFTOTEXT_BIN=pdftotext
ODEA_TESSERACT_BIN=tesseract
ODEA_OCR_LANG=spa+eng
```

El modo `disabled` permite seguir recibiendo archivos, pero el worker marca el trabajo con error recuperable indicando que no se configuró un proveedor. Para habilitar extracción local, configurar `ODEA_DOCUMENT_PROCESSOR=local` y las rutas de los ejecutables en el entorno del servidor. No hay claves API en el repositorio; esta fase no usa proveedores externos.

La ejecución manual del worker en Windows, desde la raíz del repositorio:

```powershell
C:\xampp\php\php.exe scripts\procesamiento-documental-worker.php --once
C:\xampp\php\php.exe scripts\procesamiento-documental-worker.php --max=20
```

Se puede programar el segundo comando en el Programador de tareas de Windows. Configurar como directorio de inicio la raíz del repositorio y ejecutar la tarea con una cuenta que pueda leer los archivos privados y conectar a la base de datos. El worker solo acepta CLI.

## Límites y privacidad

- Se mantiene el máximo de carga actual de 10 MB.
- Un trabajo tiene lease de 90 segundos; el proveedor local recibe un límite de 45 segundos.
- La salida del proveedor se limita a 5 MB y el texto persistido a 1 MB.
- Se verifica ruta dentro de `uploads/files`, tamaño y SHA-256 antes de abrir un documento.
- Los logs solo registran el identificador del trabajo y un código de error, no el texto ni el contenido del archivo.
- El resultado de OCR/extracción requiere validación humana. No se ejecuta comparación académica ni decisión automática.

## Pruebas

Desde PowerShell:

```powershell
C:\xampp\php\php.exe tests\procesamiento_documental.php
C:\xampp\php\php.exe tests\instrumentos.php
```

Las pruebas crean una base temporal aislada y archivos temporales bajo `uploads/files`, y los eliminan al terminar. No usan documentos personales ni requieren proveedores reales.
