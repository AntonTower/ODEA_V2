# Integridad documental ODEA

## Estado inspeccionado

Esquema `gestor_evidencia_produccion`, MariaDB 10.4.32, consultado en modo de solo lectura. Columnas confirmadas:

- `evidencias`: `id_evidencia`, `titulo`, `archivo`, `fecha_subida`, `id_docente`, `id_tipo_evidencia`, `ocultar`.
- `evidencia_valores_atributo`: `id_eva`, `id_evidencia`, `id_ate`, `indice`, valores tipados, `valor_archivo`, marcas de tiempo.
- `atributos_tipo_evidencia`: define el tipo de evidencia, tipo de atributo, `requerido`, `multiple`, límites y descripción.
- `tipos_atributo`: define el almacenamiento en `grupo_storage` y el validador.

La base no tenía columnas de hash, MIME o tamaño. Las cuatro relaciones esperadas ya tienen claves foráneas: evidencia→docente/tipo; valor→evidencia/atributo; atributo→tipo de atributo/tipo documental. Las tablas puente relevantes también tienen PK/FK en el esquema. Consultas de huérfanos dieron cero en relaciones de valores/atributos y cero valores asociados a atributos de otro tipo documental. No se añadieron FKs.

La consulta local encontró 4 evidencias y 0 valores de atributos. Dos PDFs almacenados localmente tienen el mismo SHA-256 aunque sus nombres físicos difieren; la migración y el backfill permitirán que la aplicación lo detecte como advertencia. No se revelan IDs ni nombres de terceros al usuario.

## Validación y comportamiento

`config/documentos.php` calcula SHA-256, detecta MIME real, exige extensión compatible, verifica tamaño y estructura básica de PDF/imágenes y genera nombres físicos aleatorios sin usar el título del usuario. El límite vigente continúa en 10 MiB. El backend permite duplicados y solo devuelve advertencias booleanas: duplicado del mismo docente y/o posible coincidencia externa, sin detalles de otras cuentas.

Las validaciones de atributos permanecen en servidor. Los campos requeridos vacíos detienen el guardado; los opcionales vacíos se omiten. La métrica de completitud ahora cuenta solo atributos requeridos, de modo que un opcional vacío no mantiene pendiente la evidencia. Las reglas de resultado/rango, asignaciones y persistencia de calificaciones se conservaron.

En el alta se muestran los campos requeridos y opcionales del tipo seleccionado, junto con descripciones informativas. La captura de atributos continúa en el formulario de edición posterior a crear la evidencia. La carga principal tiene barra de progreso y mensajes de validación concretos. Los archivos adjuntos de atributos se descargan por su ID de valor asociado a la evidencia, bajo el mismo control de acceso y confinamiento de ruta que el archivo principal.

## Despliegue del cambio de esquema

La migración entregada es [20261118_integridad_documental.sql](/C:/xampp/htdocs/ODEA/gestor_evidencias/sql/migrations/20261118_integridad_documental.sql). Añade metadatos e índices no únicos; no prohíbe duplicados. No fue ejecutada contra la base existente.

1. Hacer respaldo y verificar que las columnas aún no existan.
2. Aplicar el SQL antes de publicar controladores que escriben dichas columnas.
3. Ejecutar `php scripts/backfill_document_hashes.php` para simular la lectura y detectar archivos ausentes/inválidos.
4. Revisar las salidas y ejecutar `php scripts/backfill_document_hashes.php --apply` en ventana autorizada para poblar metadatos existentes.
5. Repetir la simulación: debería indicar cero procesables; resolver manualmente los omitidos.

El backfill no elimina archivos ni registros; en modo predeterminado es de solo lectura. Los índices SHA-256 no son únicos intencionalmente porque las coincidencias generan avisos, nunca bloqueos ni eliminaciones.

## Integridad relacional e índices

La migración agrega índices sobre las columnas hash para consultas de duplicados que abarcan archivo principal y archivo de atributo. Las tablas puente actuales ya tienen claves primarias compuestas (`evaluador_instrumento`, `instrumento_tipo_evidencia`) e índices inversos. `evidencia_valores_atributo` ya tiene unicidad por `(id_evidencia,id_ate,indice)` e índices por evidencia y atributo.

En una fase posterior se puede evaluar imponer unicidad de combinación entre archivos y docente, pero no se recomienda: bloquearía la carga de duplicados que este requisito pide permitir. La consistencia entre el tipo de evidencia y el tipo del atributo no puede imponerse con las FKs simples actuales; si se requiere garantía declarativa, primero habría que diseñar una clave compuesta y validar compatibilidad de CRUD/eliminaciones. Mientras tanto, la edición de tipo se rechaza si ya hay valores de atributo, para no dejar valores vinculados a atributos de otro tipo ni borrarlos silenciosamente.

## Verificación automatizada

- `php tests/documentos.php`: 6 comprobaciones de hash, contenido idéntico con nombre distinto, extensión falsa, exceso de tamaño, truncamiento y nombre físico seguro.
- `php tests/instrumentos.php`: 61 comprobaciones, incluyendo duplicados internos/externos sin exponer datos, archivo adjunto, acceso cruzado, consulta/edición, campos requeridos/opcionales y completitud con opcionales vacíos.
- `php tests/autenticacion.php`: 18 comprobaciones; `php tests/csrf.php`: 12 comprobaciones de regresión.

Las pruebas se ejecutaron con XAMPP PHP 8.0 y MariaDB local. Las pruebas de controlador usan una base temporal con la migración aplicada. La base configurada permaneció en 4 evidencias y 0 valores de atributos; la migración no se ejecutó en ella. El progreso XHR debe validarse además con una carga multipart manual desde navegador.
