# Datos de demostración del Gestor de Evidencias

Esta demostración agrega usuarios, tipos de evidencia, atributos, archivos y calificaciones ficticias a una instalación creada con [`../gestor_evidencia_produccion_v2.sql`](../gestor_evidencia_produccion_v2.sql). El SQL de demostración usa el esquema actual: las asociaciones de instrumentos se registran en `instrumento_tipo_evidencia` y las asignaciones de evaluadores en `evaluador_instrumento`.

## Preparar e importar

1. Instala el sistema importando `sql/gestor_evidencia_produccion_v2.sql` en una base vacía. Consulta [`../README_produccion.md`](../README_produccion.md).
2. Desde PowerShell, en la raíz del proyecto, prepara los PDF:

   ```powershell
   powershell -NoProfile -ExecutionPolicy Bypass -File .\sql\prueba\preparar_archivos_demo.ps1
   ```

   El script copia `Evidencia de prueba.pdf` 90 veces a `uploads/files/`, verifica su SHA-256 y no sobrescribe destinos que tengan contenido distinto.

3. En phpMyAdmin, selecciona la base `gestor_evidencia_produccion` y usa **Importar** para ejecutar `sql/prueba/demostracion_completa.sql`.
4. Abre `http://localhost/gestor_evidencias/public/login.php` e inicia sesión con una cuenta de esta página.

El SQL extendido no crea ni elimina tablas, no necesita IDs fijos y evita duplicar los registros de demostración al reimportarse. No lo importes en una base antigua: requiere el esquema v2. El SQL agrega filas; importar de nuevo no deshace cambios hechos desde la aplicación. Para regresar al estado inicial, restaura la base desde el volcado v2 en una base vacía.

## Contenido

- 15 cuentas activas: 10 docentes, 3 evaluadores, 1 administrador y 1 superusuario.
- 15 tipos nuevos, todos identificados con el prefijo `[DEMO]`.
- 213 definiciones de atributos y 639 valores de atributo.
- 45 evidencias visibles, tres por tipo, distribuidas entre los diez docentes.
- 40 asociaciones entre tipos e instrumentos y 120 calificaciones: 105 aprobaciones y 15 rechazos.
- 90 PDF idénticos al documento de prueba: un archivo principal y un anexo por evidencia.

Las actividades representadas corresponden a 2023, 2024 y 2025. Los rechazos simulan dictámenes desfavorables para probar ambos estados. Nombres, instituciones, obras, identificadores, calificaciones y relaciones son datos ficticios e ilustrativos; no representan criterios oficiales de SNII, PRODEP o ESDEPED.

## Cuentas de acceso

Todas las cuentas de demostración usan la contraseña `1234`. La lista completa está en [`usuarios_demo.csv`](usuarios_demo.csv).

| Perfil | Correo | Contraseña |
|---|---|---|
| Docente | `ana.mendoza@demo.example` | `1234` |
| Evaluador SNII | `evaluador.snii@demo.example` | `1234` |
| Evaluador PRODEP | `evaluador.prodep@demo.example` | `1234` |
| Evaluador ESDEPED | `evaluador.esdeped@demo.example` | `1234` |
| Administrador | `administrador@demo.example` | `1234` |
| Super Usuario | `superusuario@demo.example` | `1234` |

Cada evaluador de demostración tiene asignado el instrumento correspondiente. Los resultados históricos contienen calificaciones en los tres instrumentos para facilitar la consulta de la demostración.

## Archivos de evidencia

El archivo de origen es `Evidencia de prueba.pdf`. El SQL guarda únicamente nombres como `demo_ge_01_01.pdf`; la aplicación los busca en `uploads/files/`. El SQL no puede copiar los documentos al servidor web, así que es necesario ejecutar el script PowerShell antes de utilizar los enlaces **Abrir**.

## Alcance de los datos

Los ISBN, ISSN, productos, instituciones y resultados son ejemplos ficticios. Las calificaciones binarias usan `1` para aprobado y `0` para no aprobado según el esquema; no son puntajes ni dictámenes oficiales.
