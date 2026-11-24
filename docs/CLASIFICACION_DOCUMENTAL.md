# Motor de clasificación documental multiinstrumento

## Alcance y separación

El motor procesa propuestas de forma independiente para cada combinación **instrumento + convocatoria + versión publicada**. Mantiene separadas la evidencia física, tipo documental, actividad académica, criterio, subcriterio, requisito, estado de validación humana y dictamen oficial. Una misma evidencia puede tener aplicaciones en SNII, PRODEP y ESDEPED sin copiar el archivo.

La migración `20261123_motor_clasificacion_multiinstrumento.sql` crea estructuras vacías. No inserta convocatorias, fuentes, actividades, criterios, requisitos, reglas ni puntajes. Una regla solo se consulta si su convocatoria y periodo están activos, la versión está publicada y la fuente normativa está marcada `VALIDADA` y vinculada al criterio. Las ventanas de convocatoria y versión también deben ser vigentes. Cada propuesta guarda la versión normativa y sus valores fuente como snapshot; una versión nueva no la reinterpreta.

## Configuración institucional de reglas

Las personas responsables configuran, tras revisión institucional, estas relaciones de la misma versión:

1. `actividades_academicas` define actividades, independientes de tipos documentales.
2. `criterio_actividad` relaciona una o varias actividades con un criterio.
3. `criterio_fuente_normativa` asocia una fuente normativa y `fuentes_normativas.validacion_estado='VALIDADA'` confirma la revisión de la fuente.
4. `requisitos_documentales` y sus relaciones `requisito_criterio` o `requisito_subcriterio` declaran requisitos. `requisito_tipo_documental` indica qué tipos de evidencia pueden documentarlos.
5. `reglas_clasificacion_documental.regla_json` contiene únicamente las condiciones configuradas para el criterio/subcriterio y actividad, junto al `id_fuente` validado. El motor requiere además responsable y fecha de validación de la fuente.

Formato de regla JSON soportado:

```json
{
  "version": 1,
  "condiciones": [
    {"campo": "doi", "operador": "presente"},
    {"campo": "anio", "operador": "anio_en_periodo"}
  ],
  "requisitos": []
}
```

Los nombres de campo deben existir en `config/esquemas_metadatos_documentales.php`. Los operadores son `presente`, `igual`, `contiene`, `tipo_evidencia_id`, `anio_en_periodo`, `fecha_en_periodo` e `incluye_docente`. Las condiciones se evalúan con AND. Los requisitos asociados por relaciones de criterio/subcriterio se incorporan automáticamente; `requisitos` puede repetir referencias vinculadas al mismo criterio, pero no sustituir esas relaciones. Un requisito obligatorio con tipo no admitido queda como faltante; `obligatorio=NULL` o una configuración inválida queda como información insuficiente.

`incluye_docente` no afirma autoría ni pertenencia: aun si encuentra el nombre del docente, siempre requiere revisión humana. Una fecha solo se compara cuando periodo y documento tienen valores interpretables. Reglas sin condiciones, campos no disponibles u operadores desconocidos no generan coincidencia. Reglas que contradicen datos conocidos no crean propuesta.

## Resultado y decisiones

- `PROPUESTA`: una regla configurada coincide; no es aprobación.
- `INFORMACION_INSUFICIENTE`: falta metadata, autoría, periodo o configuración validada.
- `validacion_documental`: `PRELIMINAR`, `INCOMPLETA` o `SIN_INFORMACION`; no es dictamen.
- `coincidencia_semantica`: `NO_CONFIGURADA` en esta fase; no se ejecuta modelo semántico.
- `dictamen_oficial`: siempre `NO_EMITIDO`; el motor no tiene operación para aprobar ni asignar puntajes.
- El docente propietario o un rol administrativo puede confirmar/rechazar cada propuesta. Estas decisiones se guardan en historial append-only. Confirmar una propuesta solo confirma su pertinencia para revisión; no modifica calificaciones.
- Evaluadores solo ven propuestas de instrumentos actualmente asignados. No pueden generar ni aceptar propuestas desde este flujo.
- Las corridas son idempotentes por evidencia/convocatoria/versión/criterio/subcriterio. Decisiones humanas preservadas no se sobrescriben al re-evaluar.

## Métricas sintéticas

`tests/clasificacion_documental.php` usa una ficha documental sintética y casos esperados definidos para la prueba. Hay dos coincidencias deterministas esperadas (tipo/DOI y año dentro del periodo), una aplicación de autoría que queda sin información suficiente y una regla contradictoria que no debe crear propuesta. En este fixture: **2/2 coincidencias esperadas**, **0 falsos positivos** en la regla contradictoria y **1 caso insuficiente**. Se verifica además que reglas con fuente revocada no participen en corridas posteriores, y que SNII, PRODEP y ESDEPED no compartan criterio/convocatoria.

Estos conteos son un control funcional de un conjunto diminuto y construido para las reglas, no una estimación de precisión/recall en documentos reales. Falsos negativos y tasa general de criterios insuficientes requieren un corpus sintético más amplio con etiquetas revisadas institucionalmente. El sistema no tiene datos para reportar precisión real, y no debe inferirse de este fixture.

## Dependencias, despliegue y prueba

Requiere las migraciones académica/versionada, OCR/metadatos y revisión humana anteriores; ejecutar todas en orden solo después de respaldo y aprobación institucional. No se ejecutaron migraciones en la base activa en esta fase. La salida de procesamiento debe contener `extraccion.campos` y mantener el hash del original.

```powershell
C:\xampp\php\php.exe tests\clasificacion_documental.php
C:\xampp\php\php.exe tests\metadatos_documentales.php
```

Las pruebas crean una base temporal, la eliminan al salir y no escriben en la base activa. No usan documentos personales ni servicios externos.
