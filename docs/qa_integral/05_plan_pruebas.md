# Plan de pruebas

1. Confirmar aislamiento de la aplicación, base y documentos. Verificar respaldo y ensayo de restauración antes de la limpieza autorizada.
2. Comparar conteos por tabla, PK y FK, cuentas por rol, secuencia de usuarios y huellas privadas de usuarios, permisos y catálogos. Confirmar SNII, PRODEP y ESDEPED.
3. Verificar login con una cuenta autorizada de cada rol, perfil y módulos de la matriz. Comprobar permisos del servidor para sesiones sin autenticar, roles sin acceso y accesos fuera del alcance de prueba por propietario o instrumento.
4. Confirmar estados vacíos de evidencias, calificaciones y evaluación. Revisar expedientes y gestión académica. Verificar las precondiciones de procesamiento documental y clasificación antes de probar esas funciones.
5. Guardar capturas de QA sin credenciales ni datos personales innecesarios en `capturas/` por rol y en `seguridad/` para controles de acceso. Registrar fecha, resultado y referencias en el resultado de limpieza.

Las fases 2 a 5 usarán el mismo entorno QA. Se conservarán sus registros y no habrá otra limpieza general. Esta fase termina antes del Prompt 2.
