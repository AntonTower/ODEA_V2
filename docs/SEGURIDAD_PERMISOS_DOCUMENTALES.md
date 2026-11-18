# Matriz de permisos y seguridad documental

Revisión de código y esquema `gestor_evidencia_produccion_v2.sql`, 18 de noviembre de 2026. Los identificadores de rol están definidos en `roles`.

## Facultades que implementa el sistema

| Rol | Facultades observadas en el código |
|---|---|
| 1 — Super Usuario | Administración de usuarios, instrumentos, tipos y atributos; acceso global a evidencias; edición/ocultamiento administrativo; evaluación en instrumentos activos relacionados con el tipo; consulta de documentos aprobados. |
| 2 — Administrador | Mismas facultades de gestión que Super Usuario para las pantallas administrativas configuradas; acceso global a evidencias; evaluación de instrumentos activos relacionados con el tipo; consulta de documentos aprobados. |
| 3 — Evaluador | Consulta de evidencias cuyos tipos pertenecen a instrumentos activos asignados; lectura y calificación solo en instrumentos asignados y relacionados con el tipo de evidencia; consulta de aprobadas solo para esos instrumentos. |
| 4 — Docente | Alta de evidencias propias; consulta, edición y ocultamiento de las propias; consulta/descarga de aprobadas filtradas a sus evidencias; sin facultad para calificar. |

Estas son facultades encontradas en las rutas actuales. La asignación de instrumentos proviene de `evaluador_instrumento`; la relación con tipos proviene de `instrumento_tipo_evidencia`. La revisión no altera datos ni la jerarquía institucional.

## Controles centralizados

`config/seguridad.php` concentra la consulta del usuario activo, roles, acceso a instrumentos, propiedad/visibilidad y edición de evidencias, permiso de evaluación y resolución segura de archivos. `config/evidencias.php` y `views/evaluador/permisos-instrumentos.php` conservan sus interfaces existentes y delegan en estos controles. `config/validacion.php` renueva los atributos de rol desde la cuenta activa y rechaza sesiones de cuentas inactivas.

## Endpoints auditados

| Grupo/rutas | Control en servidor |
|---|---|
| `controllers/admin/{usuario, instrumento, tipo-evidencia, atributo-tipo}-{get,insert,update,eliminar}.php` | `exigirGestion`; cuenta activa, roles administrativos 1/2 y CSRF en mutaciones. |
| `controllers/docente/evidencia-{get,insert,update,eliminar}.php`, `evidencia-detalle-{get,save}.php`, `evidencia-atributos-get.php` | Cuenta activa; creación por roles 1/2/4; propiedad docente o facultad administrativa para ver/editar/ocultar; CSRF en cambios. |
| `controllers/evaluador/evaluacion-{get,save}.php` | Cuenta activa; roles evaluadores/administrativos; asignación activa y relación instrumento-tipo. Los docentes no califican. |
| `controllers/docente/evidencia-archivo.php` | Evidencia visible, cuenta activa y propietario/rol/asignación; resuelve el nombre dentro de `uploads/files`, solo archivos legibles bajo la raíz. |
| `controllers/comunes/descargas-api.php` | Sesión activa; instrumento activo y autorizado antes de listar/crear ZIP; rol docente restringido a evidencias propias; IDs ZIP intersectados con aprobadas autorizadas; archivos resueltos bajo raíz privada. |
| `views/docente/calificaciones-pdf.php` | Sesión activa y acceso a instrumento; rol docente recibe únicamente evidencias propias; Evaluador requiere asignación. |
| `controllers/comunes/registro-save.php` | Ruta pública de registro; no concede permisos administrativos y asigna rol docente conforme al flujo institucional actual. |

El almacenamiento existente permanece en `uploads/files` para conservar compatibilidad con documentos y rutas actuales. `uploads/files/.htaccess` deshabilita listado y acceso HTTP directo; todas las entregas de aplicación usan el visor autorizado. El ZIP sin `ZipArchive` también descarga mediante ese visor.

## Pruebas negativas y regresión ejecutadas

`tests/instrumentos.php` crea y elimina una base temporal, sin tocar la base real. También crea y elimina un archivo temporal. Resultado: 52 comprobaciones correctas, entre ellas docente ajeno, cambio manual de ID, docente intentando calificar, Evaluador no asignado, cuenta inactiva con sesión previa, instrumento inactivo, ZIP no autorizado, endpoint administrativo directo, rutas físicas fuera de almacenamiento y bloqueo del acceso directo desde la aplicación.

`tests/autenticacion.php`: 18 correctas. `tests/csrf.php`: 12 correctas. No se ejecutaron migraciones ni se modificaron datos existentes.

## Limitaciones operativas

- La regla `.htaccess` requiere Apache con `AllowOverride` habilitado para el directorio; XAMPP usa Apache. En despliegues con otro servidor debe aplicarse la regla equivalente de denegación directa.
- La carpeta está físicamente bajo el árbol del proyecto por compatibilidad histórica. Para quedar fuera del document root en otro despliegue habría que configurar una ruta de almacenamiento privada y migrar archivos con una fase autorizada aparte.
- La política de acceso entre Administrador y Super Usuario se conserva como aparece actualmente en el código; cualquier diferenciación requiere una decisión institucional.
