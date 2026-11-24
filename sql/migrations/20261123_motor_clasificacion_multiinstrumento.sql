-- Infraestructura vacía del motor multiinstrumento. No crea reglas ni criterios ficticios.
CREATE TABLE actividades_academicas (
  id_actividad INT NOT NULL AUTO_INCREMENT,
  id_version INT NOT NULL,
  codigo VARCHAR(60) NOT NULL,
  nombre VARCHAR(180) NOT NULL,
  descripcion TEXT NULL,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_actividad),
  UNIQUE KEY uq_actividad_version_codigo (id_version,codigo),
  UNIQUE KEY uq_actividad_id_version (id_actividad,id_version),
  CONSTRAINT fk_actividad_version FOREIGN KEY (id_version)
    REFERENCES versiones_criterios(id_version) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE criterio_actividad (
  id_version INT NOT NULL,
  id_criterio INT NOT NULL,
  id_actividad INT NOT NULL,
  PRIMARY KEY (id_criterio,id_actividad),
  CONSTRAINT fk_criterio_actividad_criterio FOREIGN KEY (id_criterio,id_version)
    REFERENCES criterios_academicos(id_criterio,id_version) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_criterio_actividad_actividad FOREIGN KEY (id_actividad,id_version)
    REFERENCES actividades_academicas(id_actividad,id_version) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- JSON versionado por criterio/subcriterio. Solo se ejecuta cuando existe una
-- fuente normativa VALIDADA asociada al criterio/subcriterio.
CREATE TABLE reglas_clasificacion_documental (
  id_regla BIGINT NOT NULL AUTO_INCREMENT,
  id_version INT NOT NULL,
  id_criterio INT NOT NULL,
  id_subcriterio INT NULL,
  id_actividad INT NOT NULL,
  id_fuente INT NOT NULL,
  regla_json LONGTEXT NOT NULL CHECK (JSON_VALID(regla_json)),
  activa TINYINT(1) NOT NULL DEFAULT 1,
  creada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_regla),
  UNIQUE KEY uq_regla_id_version (id_regla,id_version),
  KEY idx_regla_version_criterio (id_version,id_criterio,activa),
  CONSTRAINT fk_regla_criterio FOREIGN KEY (id_criterio,id_version)
    REFERENCES criterios_academicos(id_criterio,id_version) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_regla_actividad FOREIGN KEY (id_actividad,id_version)
    REFERENCES actividades_academicas(id_actividad,id_version) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_regla_subcriterio FOREIGN KEY (id_subcriterio,id_version)
    REFERENCES subcriterios_academicos(id_subcriterio,id_version) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_regla_fuente FOREIGN KEY (id_fuente,id_version)
    REFERENCES fuentes_normativas(id_fuente,id_version) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE evidencia_aplicaciones_academicas (
  id_aplicacion BIGINT NOT NULL AUTO_INCREMENT,
  id_evidencia INT NOT NULL,
  id_instrumento INT NOT NULL,
  id_convocatoria INT NOT NULL,
  id_version INT NOT NULL,
  estado ENUM('PROPUESTA','CONFIRMADA','RECHAZADA') NOT NULL DEFAULT 'PROPUESTA',
  validacion_documental ENUM('SIN_INFORMACION','INCOMPLETA','PRELIMINAR') NOT NULL DEFAULT 'SIN_INFORMACION',
  coincidencia_semantica ENUM('NO_CONFIGURADA','COINCIDE','NO_COINCIDE','AMBIGUA') NOT NULL DEFAULT 'NO_CONFIGURADA',
  dictamen_oficial ENUM('NO_EMITIDO') NOT NULL DEFAULT 'NO_EMITIDO',
  resumen_json LONGTEXT NULL CHECK (resumen_json IS NULL OR JSON_VALID(resumen_json)),
  decidido_por INT NULL,
  decidido_en DATETIME NULL,
  creada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_aplicacion),
  UNIQUE KEY uq_evidencia_conv_version (id_evidencia,id_convocatoria,id_version),
  UNIQUE KEY uq_aplicacion_contexto (id_aplicacion,id_version),
  KEY idx_aplicacion_instrumento_conv (id_instrumento,id_convocatoria,id_version,estado),
  CONSTRAINT fk_aplicacion_evidencia FOREIGN KEY (id_evidencia)
    REFERENCES evidencias(id_evidencia) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_aplicacion_instrumento FOREIGN KEY (id_instrumento)
    REFERENCES instrumentos(id_instrumento) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_aplicacion_conv_instrumento FOREIGN KEY (id_convocatoria,id_instrumento)
    REFERENCES convocatorias(id_convocatoria,id_instrumento) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_aplicacion_version_conv FOREIGN KEY (id_version,id_convocatoria)
    REFERENCES versiones_criterios(id_version,id_convocatoria) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_aplicacion_decisor FOREIGN KEY (decidido_por)
    REFERENCES usuarios(id_usuario) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE propuestas_clasificacion_documental (
  id_propuesta BIGINT NOT NULL AUTO_INCREMENT,
  id_aplicacion BIGINT NOT NULL,
  id_version INT NOT NULL,
  id_criterio INT NOT NULL,
  id_subcriterio INT NULL,
  id_actividad INT NOT NULL,
  id_subcriterio_unico BIGINT AS (IFNULL(id_subcriterio,0)) PERSISTENT,
  id_regla BIGINT NOT NULL,
  estado ENUM('PROPUESTA','CONFIRMADA','RECHAZADA','INFORMACION_INSUFICIENTE') NOT NULL DEFAULT 'PROPUESTA',
  coincidencia_regla ENUM('COINCIDE','INFORMACION_INSUFICIENTE') NOT NULL,
  justificacion VARCHAR(1000) NOT NULL,
  datos_utilizados_json LONGTEXT NOT NULL CHECK (JSON_VALID(datos_utilizados_json)),
  requisitos_cumplidos_json LONGTEXT NOT NULL CHECK (JSON_VALID(requisitos_cumplidos_json)),
  requisitos_faltantes_json LONGTEXT NOT NULL CHECK (JSON_VALID(requisitos_faltantes_json)),
  requisitos_no_aplicables_json LONGTEXT NOT NULL CHECK (JSON_VALID(requisitos_no_aplicables_json)),
  confianza DECIMAL(5,4) NULL,
  decidido_por INT NULL,
  decidido_en DATETIME NULL,
  evaluado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_propuesta),
  UNIQUE KEY uq_propuesta_version (id_propuesta,id_version),
  UNIQUE KEY uq_propuesta_aplicacion_criterio_actividad (id_aplicacion,id_criterio,id_subcriterio_unico,id_actividad),
  UNIQUE KEY uq_propuesta_regla_version (id_regla,id_version),
  CONSTRAINT fk_propuesta_aplicacion FOREIGN KEY (id_aplicacion,id_version)
    REFERENCES evidencia_aplicaciones_academicas(id_aplicacion,id_version) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_propuesta_criterio FOREIGN KEY (id_criterio,id_version)
    REFERENCES criterios_academicos(id_criterio,id_version) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_propuesta_subcriterio FOREIGN KEY (id_subcriterio,id_version)
    REFERENCES subcriterios_academicos(id_subcriterio,id_version) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_propuesta_actividad FOREIGN KEY (id_actividad,id_version)
    REFERENCES actividades_academicas(id_actividad,id_version) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_propuesta_regla FOREIGN KEY (id_regla,id_version)
    REFERENCES reglas_clasificacion_documental(id_regla,id_version) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_propuesta_decisor FOREIGN KEY (decidido_por)
    REFERENCES usuarios(id_usuario) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE historial_clasificacion_documental (
  id_evento BIGINT NOT NULL AUTO_INCREMENT,
  id_aplicacion BIGINT NOT NULL,
  id_propuesta BIGINT NULL,
  estado_anterior VARCHAR(32) NULL,
  estado_nuevo VARCHAR(32) NOT NULL,
  actor_id INT NOT NULL,
  actor_nombre VARCHAR(255) NOT NULL,
  motivo VARCHAR(1000) NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_evento),
  KEY idx_historial_clasificacion_aplicacion (id_aplicacion,creado_en),
  CONSTRAINT fk_historial_clasificacion_aplicacion FOREIGN KEY (id_aplicacion)
    REFERENCES evidencia_aplicaciones_academicas(id_aplicacion) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_historial_clasificacion_actor FOREIGN KEY (actor_id)
    REFERENCES usuarios(id_usuario) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_historial_clasificacion_propuesta FOREIGN KEY (id_propuesta)
    REFERENCES propuestas_clasificacion_documental(id_propuesta) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TRIGGER historial_clasificacion_no_update BEFORE UPDATE ON historial_clasificacion_documental
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='El historial de clasificación es inmutable';
CREATE TRIGGER historial_clasificacion_no_delete BEFORE DELETE ON historial_clasificacion_documental
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='El historial de clasificación es inmutable';
