# Historial de evaluaciones

## Modelo

La migración `20261120_historial_estados_evaluacion.sql` conserva `calificacion_evidencia` para compatibilidad y agrega una fila vigente por evidencia, instrumento y versión de criterios. La tabla `historial_evaluaciones` guarda instantáneas append-only del resultado, comentario, estado y actor en cada operación. No se deducen estados de las calificaciones antiguas; la importación genera un evento `MIGRACION_LEGADA` sin estado.

El resultado numérico o binario sigue gobernado por la configuración actual del instrumento. El estado de flujo es independiente. El catálogo inicial propone Pendiente de revisión, En revisión, Observada, Aceptada, Rechazada y Reenviada para revisión. Cada estado puede desactivarse; se pueden definir transiciones por instrumento en `transiciones_estado_evaluacion`. Si no se configura una matriz de transiciones, se admiten los estados activos para no imponer reglas institucionales nuevas.

Los periodos, convocatorias y versiones se incorporan con la migración `20261119_modelo_academico_versionado.sql`. Se crean vacíos; no se agregan criterios, ponderaciones o reglas académicas ficticias. Una evaluación versionada referencia una versión publicada y conserva su convocatoria. Las evaluaciones existentes quedan en el contexto legado sin versión.

## Compatibilidad y límites

- Para el contexto sin versión, los resultados también se reflejan en `calificacion_evidencia` para mantener las consultas y reportes existentes.
- Las evaluaciones asociadas a una versión se almacenan en `evaluaciones_versionadas`; no se mezclan con la calificación global anterior.
- Cerrar una evaluación solo está permitido a roles administrativos existentes (1 y 2). Reabrir o modificar una evaluación cerrada exige motivo y autorización administrativa.
- Los eventos se guardan en la misma transacción que el estado vigente.
- No se aplicó ninguna migración a la base persistente durante esta fase. Las migraciones se validaron en la base temporal creada por las pruebas.

## Operación y pruebas

Ejecutar desde la raíz del repositorio:

```powershell
C:\xampp\php\php.exe tests/instrumentos.php
```

La prueba prepara una base temporal desde el esquema distribuido, carga las migraciones documentales, académicas y de evaluación, verifica guardado, observación, preservación del resultado, consulta del historial y permisos. Al terminar elimina únicamente esa base temporal.

La configuración de las tablas y los seis estados iniciales es SQL. La interfaz evaluadora permite seleccionar estado, capturar motivo y consultar el historial. La administración visual de periodos, convocatorias, versiones y transiciones requiere un módulo administrativo dedicado; no se generan catálogos académicos ficticios.
