# Casos de prueba del Administrador

Entorno `odea_qa` en el puerto web 8088. Se usa el Administrador QA 7 y se reutilizan las convocatorias y cuentas del Prompt 2.

| ID | Flujo | Datos | Resultado esperado |
|---|---|---|---|
| AD-01 | Autenticación y dashboard | Administrador QA 7 | Entrar como rol 2 y navegar por módulos administrativos |
| AD-02 | Gestión de docentes | Docente QA 5 | Validar correo, guardar y consultar tras recargar |
| AD-03 | Instrumentos | Instrumento temporal 5; SNII, PRODEP y ESDEPED solo consulta | Filtrar, rechazar rango invertido y persistir configuración válida |
| AD-04 | Tipos de evidencia y atributos | Tipo 7, atributos 9–13 | Crear, editar, detectar duplicado, asociar tres instrumentos y mostrar en carga |
| AD-05 | Periodos y convocatorias | Periodo 3, convocatorias QA 3–5 | Consultar y filtrar; guardar periodo y proteger convocatoria activa |
| AD-06 | Criterios y fuentes | Versión 2 y fuente 1 de la convocatoria QA SNII | Consultar jerarquía y fuentes; editar borrador QA solo si existe |
| AD-07 | Carga en nombre del docente | Evidencias QA 5–7, dueño 5, tipo 7 | Cargar PDF, guardar atributos y confirmar propietario tras recarga |
| AD-08 | Validación documental | PDF de 1,659 bytes, copia idéntica, extensión .txt y archivo de 10 MiB + 1 byte | Guardar PDF válido, advertir duplicado y rechazar extensión, tamaño y valores requeridos vacíos |
| AD-09 | Consulta y filtrado | Docente 5, tipo 7 y evidencia 5 | Localizar registros y comprobar documento, valores y huella |
| AD-10 | Asignación de evaluadores | Evaluador QA 6 | Modificar, consultar y conservar el alcance para la siguiente fase |
| AD-11 | Reportes | Expediente del docente 5 y reportes SNII | Expediente disponible, PDF y listado válidos en estado vacío, ZIP bloqueado sin aprobaciones |
| AD-12 | Controles defensivos | Formulario QA y documento 5 | Rechazar token inválido, acceso sin sesión e ID inexistente |

La evidencia se obtiene de la interfaz real, con recarga y cotejo de MariaDB. Las pruebas de escritura utilizan registros QA y archivos sintéticos.
