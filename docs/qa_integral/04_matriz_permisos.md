# Matriz de permisos inicial

Derivada de `config/menu_config.php` y de las restricciones revisadas de gestión académica. Las marcas indican visibilidad prevista en el menú. La autorización efectiva de cada ruta y acción queda pendiente de pruebas en QA.

| Módulo | Super Usuario | Administrador | Evaluador | Docente |
|---|---|---|---|---|
| Resumen, evidencias y perfil | ✓ | ✓ | ✓ | ✓ |
| Expediente académico | ✓ | ✓ | | ✓ |
| Usuarios, tipos de evidencia e instrumentos | ✓ | ✓ | | |
| Periodos, convocatorias, criterios y fuentes | ✓ | ✓ | | |
| Evaluación | ✓ | ✓ | ✓ | |
| Calificaciones recibidas | ✓ | | | ✓ |

El controlador de eliminación definitiva de usuarios impide al administrador actuar sobre el Super Usuario. En evaluación, verificar el alcance por instrumento. En evidencias y descargas, verificar el alcance por propietario y rol.
