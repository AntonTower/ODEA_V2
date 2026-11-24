# Errores detectados

| Caso | Falla inicial | Causa | Corrección y repetición |
|---|---|---|---|
| AD-02 | Ver perfil devolvía al Administrador al dashboard | La ruta de perfil faltaba en la lista de páginas autorizadas para el rol 2, aunque Gestión de Usuarios ofrecía la acción y su API ya permitía consultar datos | Se registró la vista en la lista de rutas del módulo para roles 1 y 2. Se abrió el perfil del docente QA y se comprobó el apellido guardado. Pasó el lint PHP |
| AD-07, AD-08 | La primera carga y la primera copia se guardaron, pero la interfaz mostró una respuesta inválida HTTP 200 | PHP usó el directorio temporal de respaldo y antepuso un aviso al JSON | En QA se definió un directorio privado de cargas y se desactivó la salida de avisos en respuestas. Se reinició PHP, se repitió la carga idéntica y se confirmaron sus datos, huella y acceso. Extensión y tamaño devolvieron JSON controlado |

Antes de las cargas se guardó un volcado de QA en el respaldo privado y se aplicó en `odea_qa` la migración aditiva `20261118_integridad_documental.sql`, necesaria para almacenar MIME, tamaño y hash. El procesamiento documental devolvió `NO_DISPONIBLE`. La prueba se verificó con el PDF y los metadatos persistidos.

## Hallazgo de revisión de código

En AD-01, los controladores de alta y edición de usuarios usan el rol enviado por el formulario y no se identificó en ellos una regla explícita que reserve la asignación del rol 1 al SuperAdministrador. No se intentó crear, promover ni editar una cuenta con ese rol. El resultado funcional de esa restricción queda pendiente de confirmar la política de asignación.