# Casos de prueba del SuperAdministrador

`QA_ODEA` identifica registros técnicos. Usar fechas de prueba explícitas y una cuenta QA activa para las fases posteriores. Eliminar credenciales y datos personales innecesarios del material de evidencia.

| ID | Módulo | Acción y datos | Resultado esperado |
|---|---|---|---|
| SA-01 | Autenticación | Abrir login; entrar con la cuenta activa del rol 1; comprobar dashboard, rol, menú y módulos; verificar continuidad de sesión. | Acceso legítimo y menú del rol 1. |
| SA-02 | Usuarios | Listar y abrir perfil; crear docente, evaluador y administrador QA; editar nombre y correo; probar duplicado y obligatorios; desactivar y reactivar una cuenta QA y comprobar el efecto en el login. | Cuentas originales intactas; cuentas QA activas al cierre. El login auxiliar de la cuenta QA solo verifica el efecto de su estado. |
| SA-03 | Instrumentos | Confirmar SNII, PRODEP y ESDEPED; probar configuración, asociación documental, validaciones y persistencia con instrumento temporal QA. | Instrumentos base intactos y asociación QA persistente. |
| SA-04 | Periodos | Crear y editar QA_ODEA_PERIODO_SNII, QA_ODEA_PERIODO_PRODEP y QA_ODEA_PERIODO_ESDEPED; probar fechas invertidas y código duplicado. | Tres periodos con instrumento correcto; entradas inválidas rechazadas. |
| SA-05 | Convocatorias | Crear QA_ODEA_CONVOCATORIA_SNII, QA_ODEA_CONVOCATORIA_PRODEP y QA_ODEA_CONVOCATORIA_ESDEPED en borrador; consultar, editar, probar fechas e intentar activar sin versión publicada; reiniciar sesión. | Asociaciones correctas, activación rechazada con motivo y borradores persistentes. |
| SA-06 | Versiones | Crear un borrador QA por convocatoria (código 1.0, nombre QA_ODEA_Versión técnica); editar, consultar y comprobar separación por instrumento; verificar protección tras publicar en SA-09. | Borradores editables y versiones publicadas protegidas. |
| SA-07 | Criterios | Crear categoría, criterio, subcriterio y requisito QA por versión; asociar los requisitos con criterio, subcriterio y tipo documental aplicable. | Jerarquía y relaciones persistentes dentro de su versión. |
| SA-08 | Fuentes | Registrar fuente QA_ODEA de demostración, vincularla a un criterio, comprobar campos, estados de revisión y restricción al publicar con fuente pendiente. | Vínculo persistente y estado de revisión respetado. |
| SA-09 | Publicación y activación | Intentar publicar incompleta; anotar requisitos; completar y validar por el flujo autorizado; publicar, intentar editar y activar convocatoria QA. | Bloqueo inicial con motivo; versión protegida y convocatoria activa si se cumplen los requisitos autorizados. |
| SA-10 | Validaciones y seguridad | Verificar controles defensivos de sesión, CSRF, alcance de objetos, rutas, campos, duplicados, valores y respuestas AJAX en el entorno autorizado. | Controles del servidor coherentes y estado de datos consistente. |

## Capturas manuales

Desde la aplicación QA, abrir el módulo y capturar `SA-<ID>-<acción>-antes.png` en `../capturas/superadministrador/`. Ejecutar la acción, capturar el mensaje y anotar el estado HTTP si corresponde. Recargar y comprobar la persistencia, cotejar el bloque SQL del mismo ID y guardar `SA-<ID>-<acción>-despues.png`. Para SA-01 capturar login vacío, dashboard y menú lateral. Para SA-09 capturar por separado los intentos, la publicación y la activación. Registrar cada subcaso con su evidencia y resultado.
