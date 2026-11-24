# Entorno aislado de QA

- Aplicación en `http://127.0.0.1:8088/public/login.php`.
- Instancia MariaDB independiente en `127.0.0.1:3318`, con directorio de datos propio y eventos desactivados. Esquema de trabajo `odea_qa`, esquema de ensayo `odea_qa_restore`.
- La comprobación desde PHP por HTTP confirmó `odea_qa` y `odea_app@127.0.0.1`. La cuenta web dispone de SELECT, INSERT, UPDATE y DELETE únicamente sobre el esquema de trabajo. Login respondió HTTP 200 y el acceso directo a `uploads` respondió HTTP 404.
- La copia de la aplicación y sus documentos se crearon bajo una carpeta temporal del perfil local, fuera de XAMPP y del repositorio. La aplicación original sigue usando su propia configuración.

## Respaldo y restauración

Directorio privado: `C:/Users/Luish/AppData/Local/Temp/ODEA-QA-af4767ccf32e4cfd91ed93dadd43d262/backup`. El volcado incluye 31 tablas, datos de 12 tablas y 2 triggers. El manifiesto `sha256.json` incluye el volcado y cinco archivos del almacén. Las huellas de los archivos de origen, respaldo y QA coincidieron. El directorio hereda permisos del perfil local.

Se restauró el volcado en ambos esquemas de la instancia aislada. Los volcados de comprobación coincidieron con el original en DDL, datos y triggers, excluyendo comentarios del exportador. La copia incluye las cuatro cuentas activas y conserva `AUTO_INCREMENT=5` para usuarios. La autenticación de SuperAdministrador se comprobó durante el Prompt 2.

## Lote propuesto para autorización

Las ocho tablas de acción B del plan ya estaban vacías en la copia. Cerrar esta parte mediante verificación de solo lectura, sin ejecutar borrados. Conservar los dos periodos, dos convocatorias, una versión, una categoría y los cuatro PDF mientras no se autorice un alcance por ID o por archivo. Conservar los catálogos, permisos, usuarios y respaldo.

Se autorizó usar la copia conservando los registros académicos. La verificación de las tablas vacías cerró el Prompt 1 sin borrados. La copia conserva el esquema observado en el Prompt 1. Cualquier migración necesaria para la auditoría se aplicará exclusivamente sobre QA y su alcance se registrará antes de las pruebas. Los registros académicos QA se crearán en el Prompt 2.
