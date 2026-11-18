# Plan de pruebas baseline de ODEA

Fecha de preparación: 2026-11-18  
Alcance: línea base funcional para una instancia local desechable. No usar en producción ni con datos personales reales.

## 1. Requisitos de ejecución

| Componente | Requisito observado o requerido |
|---|---|
| Sistema web | Apache de XAMPP y PHP con `mysqli`, `fileinfo`, `mbstring` y `zip`. |
| PHP | En este equipo está disponible PHP CLI 8.0.30 en `C:\xampp\php\php.exe`. El README raíz pide PHP 8.1 o superior, mientras que el SQL y otros documentos citan PHP 8.0.30; conviene resolver esa discrepancia antes de fijar el entorno soportado. |
| Base de datos | MySQL/MariaDB. La instancia local responde como MariaDB 10.4.32 en `127.0.0.1:3308`. |
| Esquema | `sql/gestor_evidencia_produccion_v2.sql`; referencia documentada: `gestor_evidencia_produccion`. |
| Archivos | Directorio `uploads/files` escribible por Apache. Los archivos físicos no están incluidos en el volcado SQL. |
| Reportes | ZIP usa la extensión `zip`; PDF usa la librería incluida `fpdf/fpdf.php`. |
| Navegador | Navegador moderno con soporte para PDF, JavaScript, cookies y ventanas emergentes para probar el visor. |

En el equipo inspeccionado, PHP 8.0.30 está disponible fuera del `PATH`; están cargadas `mysqli`, `fileinfo`, `mbstring` y `zip`, y existe `C:\xampp\mysql\bin\mysql.exe`. El acceso de solo lectura a la base configurada fue posible. No se probó Apache ni el flujo HTTP desde navegador.

## 2. Configuración local

1. Iniciar Apache y MySQL desde XAMPP.
2. Usar una instancia local desechable y una base de pruebas separada. No apuntar las pruebas a una base institucional ni a una copia con datos personales.
3. Revisar `config/conexion.php`: host `127.0.0.1`, base `gestor_evidencia_produccion` salvo `GESTOR_DB_NAME`, usuario `root`, contraseña vacía y puerto `3308`.
4. Importar `sql/gestor_evidencia_produccion_v2.sql` solo en la base desechable vacía. El volcado incluye usuarios de demostración con correos y hashes predefinidos; no documentar ni reutilizar esas credenciales en entornos institucionales.
5. Crear `uploads/files` y verificar permisos de escritura. Respaldar cualquier archivo de prueba antes de comenzar.
6. Abrir `http://localhost/gestor_evidencias/public/login.php`.
7. Confirmar que `upload_max_filesize` permita al menos 10 MB y `post_max_size` más de 10 MB.

## 3. Usuarios y roles de prueba

Crear cuentas sintéticas únicamente en la instancia desechable. Usar dominios reservados como `example.invalid`; no copiar personas, correos, documentos ni contraseñas de usuarios reales.

| Cuenta sintética | Rol | Uso |
|---|---|---|
| `admin.baseline@example.invalid` | Super Usuario (rol 1) | Configuración y alcance administrativo global. |
| `operador.baseline@example.invalid` | Administrador (rol 2) | CRUD administrativo y consulta global permitida. |
| `evaluador.baseline@example.invalid` | Evaluador (rol 3) | Evaluación de instrumentos asignados y comprobación de denegación para instrumentos ajenos. |
| `docente.baseline@example.invalid` | Docente (rol 4) | Carga y consulta de evidencias propias; denegación sobre evidencia de otro docente. |
| `docente2.baseline@example.invalid` | Docente (rol 4) | Comprobación de separación de propiedad. |

Usar contraseñas temporales distintas de las institucionales. Registrar estas cuentas solo en el entorno local de pruebas y eliminarlas al restaurar la instancia.

## 4. Datos de prueba

- Instrumentos sintéticos: uno configurado como aprobación binaria y otro como numérico con rango definido para la prueba. No presentar esos rangos como criterios oficiales.
- Tipos sintéticos: `Tipo baseline A` asociado a ambos instrumentos y `Tipo baseline B` asociado solo a uno.
- Atributos: un campo obligatorio de texto, uno opcional y uno múltiple. Añadir un atributo tipo fecha si se prueba almacenamiento tipado.
- Archivos inventados: PDF pequeño válido, PNG/JPEG de prueba, archivo con MIME/extensión discordantes, archivo de más de 10 MB y archivo de texto con contenido HTML. No usar documentos institucionales.
- Evidencias: una completa, una con atributo obligatorio faltante, una oculta y otra propiedad del segundo docente.
- Evaluador: asignarle solo el primer instrumento; crear también un caso con asignación revocada durante una sesión existente.

## 5. Recorridos funcionales y resultados esperados

| ID | Recorrido | Resultado esperado |
|---|---|---|
| AUTH-01 | Iniciar sesión con credenciales sintéticas válidas. | Sesión iniciada y redirección al resumen. |
| AUTH-02 | Intentar credenciales incorrectas. | Acceso rechazado sin revelar si correo o contraseña fue el dato incorrecto. |
| AUTH-03 | Desactivar una cuenta y volver a iniciar sesión. | Una cuenta inactiva no debe acceder. El código actual parece permitirlo; registrar resultado y severidad. |
| AUTH-04 | Cerrar sesión y abrir una ruta protegida. | Sesión cerrada y redirección al login. |
| DOC-01 | Docente carga PDF permitido menor a 10 MB. | Archivo recibido, registro creado con propiedad del docente y disponible en su listado. |
| DOC-02 | Intentar extensión permitida con MIME no permitido y viceversa. | Carga rechazada; no debe quedar archivo huérfano. |
| DOC-03 | Intentar archivo de más de 10 MB. | Carga rechazada con mensaje claro y sin registro ni archivo huérfano. |
| DOC-04 | Completar y guardar atributos obligatorios; dejar un opcional vacío. | Se guarda la información; validar que la regla de completitud no bloquee evaluación por opcionales. |
| DOC-05 | Docente 1 consulta, edita, oculta y abre el archivo propio. | Operaciones permitidas según interfaz y reglas; el archivo solo se sirve a usuarios autorizados. |
| DOC-06 | Docente 1 intenta abrir evidencia/archivo de docente 2 cambiando el ID de la URL o POST. | Operación denegada en servidor. |
| DOC-07 | Probar ruta de archivo manipulada y contenido HTML con extensión de imagen/PDF. | Ruta fuera de `uploads/files` rechazada; contenido activo no servido inline. |
| ADM-01 | Crear, editar y consultar usuario sintético; cambiar rol de evaluador. | Datos consistentes; las asignaciones de instrumentos corresponden al rol vigente. |
| ADM-02 | Asignar dos instrumentos al evaluador y consultar su edición. | Ambas asignaciones aparecen y se conservan. |
| ADM-03 | Crear tipo asociado a varios instrumentos y configurar atributos. | Relaciones y atributos se guardan y se muestran en las vistas. |
| ADM-04 | Enviar IDs de instrumento inexistentes a alta/edición. | Rechazo íntegro; sin cambios parciales en relaciones. |
| EVAL-01 | Evaluador asignado abre evidencia completa de un tipo autorizado. | Solo aparecen instrumentos asignados, activos y relacionados con el tipo. |
| EVAL-02 | Evaluador intenta calificar instrumento no asignado mediante POST directo. | Rechazo del servidor; sin calificación nueva o modificada. |
| EVAL-03 | Evaluador califica evidencia incompleta. | Rechazo con indicación de información faltante. |
| EVAL-04 | Guardar resultado binario distinto de 0/1 y resultado numérico fuera de rango. | Ambos rechazados; valores válidos aceptados. |
| EVAL-05 | Revocar una asignación y repetir consulta/POST desde sesión existente. | El permiso revocado deja de funcionar sin requerir una sesión nueva. |
| REP-01 | Consultar calificaciones recibidas como docente. | Solo se muestran resultados propios y acciones acordes al rol. |
| REP-02 | Generar PDF y ZIP para cada instrumento en la instancia local. | Archivos válidos, nombres correctos y solo evidencias dentro del alcance autorizado. Verificar además el umbral numérico configurado por código, que actualmente es 60% del rango. |
| UI-01 | Navegar vistas en escritorio/móvil, operar modales con teclado y validar mensajes de éxito/error. | Navegación usable, foco visible, cierre accesible y mensajes comprensibles. Registrar problemas de foco y responsive sin rediseñar durante esta fase. |

## 6. Pruebas automatizadas y errores conocidos

Existe `tests/instrumentos.php`. El script crea una base de datos aleatoria, importa el volcado, inserta datos, copia y altera un archivo en `uploads/files`, elimina el archivo de sesión y finalmente ejecuta `DROP DATABASE`. **No se ejecutó**, porque la instrucción de esta fase prohíbe alterar la base y los archivos. Para una futura ejecución requiere aprobación explícita del uso de una base y un directorio temporal desechables.

En la línea base anterior a la fase de autenticación se realizó inspección estática de sintaxis con PHP CLI 8.0.30 sobre 83 archivos PHP de aplicación (excluida la carpeta de la librería FPDF). Se encontró un error que quedó corregido en esta fase:

- `controllers/comunes/registro-save.php`, línea 12: PHP Parse error por el token `\vert`; corregido al rehacer el endpoint con el esquema vigente.
- **Resuelto en la fase de autenticación:** el endpoint usa ahora `correo`/`rol` y busca el rol `Docente` en `roles`. Las nuevas cuentas quedan inactivas hasta aprobación administrativa.
- **Resuelto en la fase de autenticación:** el login filtra cuentas inactivas y conserva una respuesta indistinguible para contraseña incorrecta, correo inexistente y cuenta inactiva.
- **Verificado en esta fase:** 18 pruebas automatizadas de autenticación/registro pasaron sobre una base temporal independiente; la prueba elimina esa base y sus archivos temporales al finalizar.
- La sintaxis PHP pasó para todos los archivos modificados en esta fase.
- La documentación raíz declara PHP 8.1+, pero el PHP disponible es 8.0.30 y el SQL vigente documenta esa versión. La compatibilidad mínima requiere decisión explícita.
- La modificación local existente en `docs/DOCUMENTACION_TECNICA.md` se conservó; no forma parte de este plan ni se sobrescribió.

La conexión de solo lectura al servidor configurado respondió como MariaDB 10.4.32. La comparación de `information_schema` con el SQL encontró 11 tablas reales y coincidencia de nombres de tabla y columnas. Esta comprobación no valida igualdad exhaustiva de tipos, defaults, índices, restricciones o datos.

## 7. Procedimiento de restauración

Realizar la restauración solo en la instancia desechable y después de conservar cualquier evidencia de prueba que se necesite para el informe:

1. Registrar nombre de la base temporal y ubicación del directorio temporal de archivos antes de empezar.
2. Detener Apache para evitar escrituras concurrentes.
3. Exportar la base desechable y respaldar `uploads/files` antes de borrar o reemplazar datos, si se requiere conservar resultados.
4. Eliminar únicamente la base temporal identificada para esta sesión; verificar dos veces el nombre y no usar el nombre de la base institucional.
5. Eliminar únicamente los archivos creados por el baseline, identificados por su lista/nombre de prueba. No vaciar recursivamente `uploads/files`.
6. Si se desea reiniciar para repetir el baseline, crear una base temporal vacía, importar el SQL vigente y cargar solo datos sintéticos.
7. Restaurar el respaldo correspondiente de la base y de los archivos si el baseline se ejecutó sobre una copia existente.
8. Iniciar Apache y comprobar login, cantidad de tablas y lista de archivos antes de dar por cerrada la restauración.

## 8. Riesgos previos a modificaciones

- No ejecutar el test existente contra la base `gestor_evidencia_produccion`: por diseño crea y elimina una base, y toca archivos bajo `uploads/files`.
- El volcado SQL contiene cuentas de demostración identificables; mantenerlo en entorno local y evitar exponer sus credenciales.
- Los documentos se almacenan fuera de la base; restaurar solo SQL no restituye expedientes.
- La cuenta MySQL predeterminada es `root` sin contraseña en el puerto local configurado; no usarla para despliegue.
- El error de sintaxis del registro impide usar ese recorrido de prueba hasta una fase de corrección autorizada.
- No hay prueba de navegador/Apache en esta línea base; componentes visuales pueden diferir de lo inferible por inspección estática.
- Los criterios y umbrales académicos requieren validación con convocatorias/reglamentos oficiales vigentes antes de cualquier parametrización institucional.
