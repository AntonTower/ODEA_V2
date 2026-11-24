-- Reversion destructiva del modelo academico (migraciones 20261119 y 20261120).
-- Ejecutar manualmente solo tras respaldo verificable y restauracion ensayada.
-- Toda la baja queda dentro del procedimiento para que un SIGNAL no permita
-- que el cliente continue ejecutando DROP TABLE externos al guard.
DELIMITER //
CREATE PROCEDURE odea_down_gestion_academica_guard()
BEGIN
  IF (SELECT COUNT(*) FROM evidencia_criterio_versionado) > 0
     OR (SELECT COUNT(*) FROM evidencia_subcriterio_versionado) > 0
     OR (SELECT COUNT(*) FROM evaluaciones_versionadas) > 0
     OR (SELECT COUNT(*) FROM historial_evaluaciones) > 0
     OR (SELECT COUNT(*) FROM periodos_academicos) > 0
     OR (SELECT COUNT(*) FROM convocatorias) > 0
     OR (SELECT COUNT(*) FROM versiones_criterios) > 0
     OR (SELECT COUNT(*) FROM categorias_academicas) > 0
     OR (SELECT COUNT(*) FROM criterios_academicos) > 0
     OR (SELECT COUNT(*) FROM subcriterios_academicos) > 0
     OR (SELECT COUNT(*) FROM requisitos_documentales) > 0
     OR (SELECT COUNT(*) FROM fuentes_normativas) > 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Reversion detenida: el modelo academico contiene datos. No se eliminaron tablas.';
  END IF;

  DROP TABLE IF EXISTS resultado_subcriterio_versionado;
  DROP TABLE IF EXISTS resultado_criterio_versionado;
  DROP TABLE IF EXISTS historial_evaluaciones;
  DROP TABLE IF EXISTS evaluaciones_versionadas;
  DROP TABLE IF EXISTS transiciones_estado_evaluacion;
  DROP TABLE IF EXISTS estados_evaluacion;
  DROP TABLE IF EXISTS evidencia_subcriterio_versionado;
  DROP TABLE IF EXISTS evidencia_criterio_versionado;
  DROP TABLE IF EXISTS criterio_fuente_normativa;
  DROP TABLE IF EXISTS requisito_tipo_documental;
  DROP TABLE IF EXISTS requisito_subcriterio;
  DROP TABLE IF EXISTS requisito_criterio;
  DROP TABLE IF EXISTS fuentes_normativas;
  DROP TABLE IF EXISTS requisitos_documentales;
  DROP TABLE IF EXISTS subcriterios_academicos;
  DROP TABLE IF EXISTS criterios_academicos;
  DROP TABLE IF EXISTS categorias_academicas;
  DROP TABLE IF EXISTS versiones_criterios;
  DROP TABLE IF EXISTS convocatorias;
  DROP TABLE IF EXISTS periodos_academicos;
END//
CALL odea_down_gestion_academica_guard()//
DROP PROCEDURE odea_down_gestion_academica_guard//
DELIMITER ;
