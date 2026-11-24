# Gestión Académica configurable

## Componentes

- `views/admin/convocatorias.php`: creación, edición de borradores, filtros y cambios de estado.
- `views/admin/periodos-academicos.php`: periodos por SNII, PRODEP o ESDEPED (según instrumentos instalados).
- `views/admin/criterios-evaluacion.php`: versiones, copia de versión, categorías, criterios, subcriterios y requisitos documentales.
- `views/admin/fuentes-normativas.php`: fuentes versionadas y relación con criterios.
- `controllers/admin/gestion-academica-api.php`: lectura del catálogo para usuarios administrativos activos.
- `controllers/admin/gestion-academica-save.php`: escrituras transaccionales con autorización de rol y CSRF.
- `config/gestion_academica.php`: comprobación de esquema y reglas compartidas.

Las migraciones base están en `sql/migrations/20261119_modelo_academico_versionado.sql` y `sql/migrations/20261120_historial_estados_evaluacion.sql`. La primera crea el catálogo académico versionado y relaciones de evidencias. La segunda crea estados e historial de evaluación e importa una copia de calificaciones vigentes; las tablas heredadas no se eliminan. La suite de integración usa además `20261118_integridad_documental.sql` para reproducir el orden de migraciones documentado.

## Reglas implementadas

- Periodos y convocatorias validan fechas y códigos únicos por instrumento mediante validación de servidor y restricciones SQL.
- Una convocatoria debe usar un periodo del mismo instrumento.
- Las convocatorias se crean como `BORRADOR`. Para activarlas, el periodo debe estar `ACTIVO` y la convocatoria debe tener una versión `PUBLICADA`.
- Solo se editan convocatorias en borrador. Las versiones publicadas o cerradas no aceptan cambios; se clonan a un nuevo borrador.
- Para publicar una versión debe tener al menos una categoría, un criterio y una fuente normativa validada asociada a cada criterio.
- Las reglas JSON y ponderaciones son opcionales. ODEA no precarga puntuaciones ni reglas oficiales.
- Al clonar una versión se copian categorías, criterios, subcriterios, requisitos, tipos documentales y asociaciones de fuentes. Las fuentes copiadas pasan a `PENDIENTE` y requieren validación en la versión nueva.
- La cuenta administrativa que marca una fuente como validada queda registrada con fecha y hora.

## Instalacion y estado de la base

El 2026-11-19 se instalaron `20261119_modelo_academico_versionado.sql` y `20261120_historial_estados_evaluacion.sql` en la instancia local XAMPP de MariaDB 10.4.32. La comprobacion inicial encontro 0 de las 20 tablas del modelo; despues se verificaron las 20 tablas nuevas. Los conteos antes y despues se mantuvieron: 4 usuarios, 0 evidencias y 0 calificaciones legadas. Las nuevas tablas de periodos, convocatorias, versiones, criterios, requisitos y fuentes quedaron vacias; `estados_evaluacion` contiene los seis estados configurables iniciales.

Antes de las migraciones se guardo y restauro correctamente un respaldo logico fuera de `htdocs`:

- Archivo: `C:\xampp\private-backups\gestor_evidencia_produccion_pre_academica_20261119_145711.sql`
- SHA-256: `DDD04CC654D1FEBFBFD3E72569DD0BAE2F1935B55F67CBDB1D2CDA28B03D580C`
- Restauracion de verificacion: 11 tablas; 4 usuarios; 0 evidencias; 0 calificaciones; base temporal eliminada.

La conexion de la aplicacion sigue apuntando a esa instancia local. No se aplico cambio a una instancia remota. Para nuevos despliegues, respalda la base, conserva el SHA-256 y valida una restauracion antes de aplicar las dos migraciones. La reversión `20261124_gestion_academica.down.sql` es manual, destructiva y protegida; no la ejecutes si alguna tabla académica contiene datos ni despues de migraciones posteriores que dependan de este modelo.

## Pruebas

Ejecutar `C:\xampp\php\php.exe tests\gestion_academica.php`. La suite crea una base `gestor_test_academico_*`, reemplaza identidades de fixture con datos sintéticos, aplica el esquema completo de prueba, ejercita API y vistas y elimina la base temporal al finalizar. No modifica la base configurada en ODEA.

Pruebas automatizadas ejecutadas: 31 de gestion academica, 97 de instrumentos, 17 de clasificación multiinstrumento y 11 de tableros/privacidad. También se verifico sintaxis PHP y JavaScript inline.
Comprobacion sobre la base configurada: la API devuelve los tres instrumentos activos y listas vacias; la vista renderiza el formulario sin aviso de migracion. El down script se verifico en una base efimera vacia y con un periodo sintetico; en el segundo caso preservo tablas y fila.

Pruebas manuales pendientes: iniciar sesión desde el navegador con Super Usuario y Administrador; navegar por las cuatro opciones; crear un periodo y convocatoria de prueba; comprobar publicación con fuente validada; y confirmar respuestas 403 con cuentas Evaluador y Docente. No se verificaron credenciales reales ni se crearon convocatorias de muestra.
