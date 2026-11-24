# Informe de validación integral de ODEA

Fecha: 2026-11-18. Entorno: checkout local en XAMPP, PHP CLI 8.0.30 y MariaDB 10.4.32 en `127.0.0.1:3308`. La base fue inspeccionada solo mediante consultas de lectura. No se importaron migraciones ni se modificaron filas.

## Resultado ejecutivo

La suite automatizada disponible pasó completa: **9 archivos, 174 comprobaciones, 0 fallos** (incluye la corrección de la acción de administración de usuarios). Estas suites son pruebas de componentes e integración contra bases temporales, no recorridos E2E en navegador. No se ejecutaron los recorridos manuales completos de docente, evaluador y administrador.

La base local llamada `gestor_evidencia_produccion` contiene las 11 tablas del esquema base v2. No contiene las migraciones `20261118` a `20261123`; por tanto, procesamiento, revisión de metadatos, historial/versiones y clasificación no están habilitados en esa base. Algunas pantallas indican que falta infraestructura; el guardado de evaluaciones versionadas requiere sus tablas. La aplicación no está lista para desplegar las fases nuevas sobre esta instancia hasta aplicar y validar la cadena de migraciones en una copia aislada.

## Funcionalidad disponible en el código

- Autenticación con consulta preparada, `password_verify`, cuenta activa, sesión rotada y registro docente pendiente de activación.
- Protección CSRF centralizada en formularios y endpoints POST comprobados por las pruebas.
- Carga local con MIME, límite de 10 MiB, SHA-256, nombres aleatorios y descargas autorizadas.
- Atributos dinámicos y relaciones de evidencias con tipos e instrumentos existentes.
- Estados e historial de evaluación, versiones académicas, cola documental, extracción/revisión humana y propuestas multiinstrumento, sujetos a instalar sus migraciones y configurar fuentes institucionales.
- Tableros por rol y expediente docente con controles de propietario/asignación ejercitados en pruebas de integración.

## Pruebas ejecutadas

| Suite | Resultado |
|---|---:|
| `tests/autenticacion.php` | 18 correctas |
| `tests/clasificacion_documental.php` | 17 correctas |
| `tests/csrf.php` | 12 correctas |
| `tests/documentos.php` | 6 correctas |
| `tests/instrumentos.php` | 77 correctas |
| `tests/metadatos_documentales.php` | 19 correctas |
| `tests/procesamiento_documental.php` | 11 correctas |
| `tests/reportes.php` | 3 correctas |
| `tests/tableros.php` | 11 correctas |

Comandos usados: `C:\xampp\php\php.exe tests\<archivo>.php`. Las suites crean bases temporales y las eliminan. El conjunto documental es sintético. No se midió rendimiento con carga representativa ni navegador automatizado. El test de tablero verifica menos de 2 segundos sobre su pequeño fixture; no debe extrapolarse a producción.

## Integridad observada en la base local

Lecturas de integridad encontraron 0 evidencias sin docente/tipo, 0 valores de atributos sin evidencia/definición, 0 calificaciones sin evidencia y 0 pares duplicados de evidencia/instrumento. La base tiene índices en claves primarias, relaciones de evidencia y unicidad de calificación. No se evaluó consistencia de tablas de migraciones ausentes. Los índices adicionales deben decidirse con `EXPLAIN` y volúmenes representativos; no se recomienda agregarlos por intuición.

## Rendimiento y reportes

Se eliminó la consulta de valores de atributos por cada evidencia del PDF: ahora se cargan en lotes de 400 IDs con consultas preparadas en `config/reportes.php`, con prueba aislada de agrupación y consulta vacía. La carga del PDF aún lee todas las evidencias que cumplen el instrumento y guarda el resultado completo en memoria. ZIP también puede acumular un conjunto completo de archivos antes de construir el paquete; no hay límite funcional documentado de cantidad/tamaño agregado.

El PDF existente `views/docente/calificaciones-pdf.php` solo genera aprobadas por instrumento, lee `calificacion_evidencia` y usa un umbral numérico fijo de 60%. No consulta evaluaciones versionadas, convocatoria, periodo, categoría, criterio ni estado configurable. No se amplió ese reporte para evitar convertir ese porcentaje heredado en regla institucional ni mezclar dictámenes de distintas versiones. **Los filtros solicitados de reportes quedan pendientes de definir con la institución** y requieren una base con las migraciones académicas/históricas instaladas.

## Hallazgos de seguridad y riesgos

1. **Bloqueo para publicación:** el dump `sql/gestor_evidencia_produccion_v2.sql` contiene cuentas activas con datos identificables y hashes de credenciales compartidas que se documentaban como contraseña de demostración. Se retiró la contraseña del README, pero cambiar el README no revoca esas credenciales ni limpia el historial Git. Sustituir/desactivar esas cuentas en los entornos destino y sanear el dump antes de distribuirlo; rotar las credenciales que hayan sido reutilizadas.
2. La conexión ahora acepta `GESTOR_DB_HOST`, `GESTOR_DB_NAME`, `GESTOR_DB_USER`, `GESTOR_DB_PASSWORD` y `GESTOR_DB_PORT`, y no revela errores SQL al visitante. Los valores por defecto siguen siendo `root` sin contraseña y puerto local: son exclusivamente para XAMPP aislado; producción debe inyectar valores privados y usuario de privilegio mínimo.
3. La carga y descarga documental cuenta con comprobaciones negativas automatizadas, pero esto no constituye una auditoría de penetración. ZIP no tiene límite agregado/streaming validado bajo volumen real.
4. Se usan CDN externos para JavaScript/CSS; disponibilidad, privacidad, CSP e integridad SRI no fueron validadas en esta fase.
5. La aplicación no es completamente segura por el hecho de que estas suites pasen. Faltan pruebas dinámicas de despliegue, HTTPS, configuración Apache, política de respaldos, pruebas de carga y revisión de todas las dependencias/CDN.

## Compatibilidad observada

PHP 8.0.30 CLI (Windows x64) con `mysqli`, `fileinfo`, `mbstring` y `zip`; MariaDB 10.4.32. No se ejecutó en MySQL 8, Linux/Apache ni PHP de otra versión. FPDF está en el árbol local, pero su disponibilidad/representación de glifos y el PDF tras el cambio de carga por lotes no se validaron visualmente. El OCR real depende de ejecutables locales/proveedor configurado; no se enviaron documentos externos.

## Pendientes funcionales

- Reportes filtrables por docente, instrumento, convocatoria, periodo, categoría, criterio y estado, ligados a la versión histórica correcta.
- Recorridos E2E de navegador para los tres roles, incluida instalación completa de migraciones y servicio local OCR disponible/no disponible.
- Prueba de PDF y ZIP con corpus/volumen sintético grande, medición de memoria/tiempo y límite de paquete.
- Revisión completa de dependencias frontend, cabeceras/CSP, configuración web y escaneo de seguridad.
- Revisión institucional de reglas, fuentes y requisitos antes de poblar criterios; no hay reglas oficiales cargadas.

## Despliegue y restauración

1. Crear respaldo consistente de base y archivos (`uploads/files`) y verificarlo restaurando en un entorno aislado.
2. Rotar usuarios/credenciales de muestra y configurar secretos mediante el entorno del servicio web, fuera del repositorio y del DocumentRoot.
3. Crear una copia staging de la base; importar esquema v2 solo para instalaciones nuevas. Sobre bases existentes, determinar primero versión y aplicar cada migración pendiente en orden: `20261118`, `20261119`, `20261120`, `20261121`, `20261122`, `20261123`.
4. Ejecutar todas las suites; configurar fuentes institucionales validadas y asignaciones de evaluadores; verificar carga, descarga, PDF, ZIP y restauración antes de promover.
5. Para rollback, detener escrituras, restaurar en conjunto la copia de base y `uploads/files`, verificar hashes/registro de archivos y reabrir el servicio. No restaurar solo la base ni ejecutar una migración inversa improvisada.

La migración no se ejecutó en la base activa durante esta validación. No usar este informe como aprobación institucional ni certificación de seguridad.
