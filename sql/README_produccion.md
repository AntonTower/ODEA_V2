# Base de datos principal: `gestor_evidencia_produccion_v2.sql`

Este es el volcado vigente para instalar el Gestor de Evidencias. Contiene el esquema actualizado y datos iniciales para trabajar localmente con PHP 8.0.30 y MariaDB 10.4.32. Es compatible con MySQL/MariaDB que admita el formato del archivo.

## Contenido

- 11 tablas para usuarios, roles, instrumentos, tipos de evidencia, atributos, archivos y calificaciones.
- Cuatro cuentas activas: Super Usuario, Administrador, Docente y Evaluador.
- Los instrumentos SNII, PRODEP y ESDEPED.
- Tres tipos de evidencia de ejemplo, cuatro atributos configurados y tres evidencias.
- Relaciones e índices, incluida la asignación de instrumentos a evaluadores.
- La estructura actual de tipos de evidencia no usa las antiguas columnas `SNI`, `PRODEP` y `ESDEPED`; sus asociaciones se guardan en `instrumento_tipo_evidencia`.

Las evidencias del volcado guardan nombres de archivo, pero los documentos no están incluidos en el SQL. Los archivos físicos deben estar disponibles en `uploads/files/`.

## Usuarios de muestra y manejo seguro

El dump heredado contiene cuentas activas con información identificable y hashes de contraseñas compartidas. Está limitado a laboratorio local aislado. No lo publiques ni lo importes en producción sin sanear los registros y crear credenciales individuales nuevas. Los datos de carga no sustituyen la configuración institucional.

## Importación en XAMPP/phpMyAdmin

1. Inicia MySQL desde el panel de XAMPP y abre [phpMyAdmin](http://localhost/phpmyadmin).
2. Crea una base vacía llamada `gestor_evidencia_produccion` con cotejamiento `utf8mb4_general_ci`.
3. Selecciona esa base, abre **Importar**, elige `gestor_evidencia_produccion_v2.sql` y ejecuta la importación.
4. Confirma en `config/conexion.php` que el nombre de base, host, usuario y puerto correspondan a tu XAMPP. El valor por defecto actual usa `127.0.0.1`, el puerto `3308`, usuario `root` sin contraseña y la base `gestor_evidencia_produccion`.
5. Inicia Apache y abre `http://localhost/gestor_evidencias/public/login.php`.

El volcado no crea ni selecciona una base de datos; por eso debe importarse después de seleccionar la base vacía en phpMyAdmin. No lo importes encima de una base existente: crea tablas y carga datos iniciales.

## Carga de datos de demostración extendida

Para agregar los 15 tipos y 45 evidencias ficticias de la demostración, sigue [`prueba/README_demo.md`](prueba/README_demo.md). Importa primero este volcado v2 y después el SQL de demostración. La demostración es aditiva y no reemplaza los datos actuales.

## Esquemas anteriores

Los archivos `gestor_evidencia.sql`, `gestor_evidencia_asignaciones.sql` y `gestor_evidencia_produccion.sql` fueron reemplazados por v2. Usa únicamente `gestor_evidencia_produccion_v2.sql` para una instalación nueva.

## Migraciones posteriores y variables privadas

El esquema v2 solo instala el modelo original. En staging con respaldo verificado, aplica en orden las migraciones sql/migrations/20261118_integridad_documental.sql a 20261123_motor_clasificacion_multiinstrumento.sql. La instancia local llamada gestor_evidencia_produccion inspeccionada durante la validación no tiene instaladas esas tablas. No aplicar en producción sin aprobación y ensayo de restauración.

La conexión acepta GESTOR_DB_HOST, GESTOR_DB_NAME, GESTOR_DB_USER, GESTOR_DB_PASSWORD y GESTOR_DB_PORT. Inyéctalas en el entorno del servicio web, no en archivos versionados. Los valores por defecto solo son para XAMPP local aislado.
