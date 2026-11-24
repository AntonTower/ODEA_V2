# Manual administrativo

1. Inicia sesión con una cuenta administrativa activa. Las facultades efectivas se validan en servidor.
2. En **Gestion de usuarios**, crea o actualiza cuentas. **Desactivar/Reactivar** conserva evidencias e historial. **Eliminar por completo** borra la cuenta, sus evidencias y archivos privados sin referencias; exige escribir ELIMINAR para confirmar. Se rechaza si hay evaluaciones, revisiones o aplicaciones academicas protegidas; en esos casos desactiva la cuenta para conservar la trazabilidad. Al reactivar un evaluador, configura de nuevo sus instrumentos. Revisa correo y rol; no compartas cuentas.
3. En **Tipos de evidencia**, administra tipos y atributos. Evita retirar definiciones con valores capturados asociados; exporta y revisa dependencias antes de cambios.
4. En **Instrumentos**, configura su estado y relaciones con tipos. **Desactivar/Activar** conserva el historial. **Eliminar por completo** borra un instrumento solo si no tiene evaluaciones, convocatorias u otras referencias protegidas; elimina sus vínculos de configuración. Asigna a cada evaluador solo instrumentos que debe revisar.
5. Las convocatorias, periodos, criterios y reglas requieren las migraciones académicas instaladas. Poblarlos solo con documentos normativos revisados por una persona responsable; ODEA no incluye criterios oficiales precargados.
6. Usa tableros y expedientes para supervisión autorizada. Los conteos dependen de tablas/migraciones disponibles y pueden presentarse como no disponibles.
7. Antes de cualquier despliegue o cambio de esquema, respalda base y `uploads/files` y valida la restauración en una copia.

No uses los usuarios heredados del dump en producción. Configura secretos de base de datos en variables privadas del servicio web, nunca en archivos versionados ni en JavaScript.
