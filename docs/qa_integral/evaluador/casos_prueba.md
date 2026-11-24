# Casos de prueba del Evaluador

Copia `odea_qa`, puerto web 8088. Se reutilizan cuentas, versiones y evidencias de los Prompts 2 y 3. Se añadió un PDF sintético incompleto por el formulario legítimo del Administrador para probar la completitud.

| ID | Acción | Entrada | Resultado esperado |
|---|---|---|---|
| EV-01 | Acceso y salida | Evaluador QA 6 | Inicio de sesión, dashboard y menú del rol 3; salida válida |
| EV-02 | Instrumentos y alcance | Instrumentos asignados 1–3 y temporal 5 | Mostrar SNII, PRODEP y ESDEPED y rechazar instrumento 5 |
| EV-03 | Bandeja de pendientes | Evidencias QA 5–8, docente 5 | Consultar propietario, tipo, filtros, búsqueda y contexto |
| EV-04 | Documento protegido | Evidencia 5 | Abrir visor y consultar PDF y atributos |
| EV-05 | Completitud | Evidencia 5 completa sin opcionales; evidencia 8 sin obligatorios | Permitir evaluar la primera y mostrar y bloquear la segunda |
| EV-06 | Observación | Evidencia 6, SNII, versión 2 y convocatoria 3 | Guardar observación y mostrarla al propietario |
| EV-07 | Primera evaluación | Evidencia 5, SNII, instrumento binario | Guardar resultado y contexto de QA |
| EV-08 | Revisión e historial | Evaluación 2 | Guardar resultado vigente y preservar el anterior |
| EV-09 | Contexto académico | Versiones 2, 3, 4 | Separar ofertas por instrumento y conservar el contexto evaluado |
| EV-10 | Controles defensivos | Sesión rol 3 e instrumento temporal 5 | Rechazar operaciones fuera del alcance autorizado |
| EV-11 | Consultas y reportes | SNII con evidencia 5 aceptada mediante versión 2 | Consultar aceptadas y generar exportaciones autorizadas coherentes |
| EV-12 | Persistencia entre sesiones | Evaluaciones 1 y 2 | Recuperar evaluación, observación y eventos al volver a entrar |
