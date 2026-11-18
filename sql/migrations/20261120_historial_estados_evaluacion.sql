-- Estados configurables, evaluación vigente por contexto y auditoría append-only.
-- No altera ni elimina filas de calificacion_evidencia.
CREATE TABLE estados_evaluacion (
  id_estado INT NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(40) NOT NULL,
  nombre VARCHAR(80) NOT NULL,
  descripcion VARCHAR(255) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  orden INT NOT NULL DEFAULT 1,
  PRIMARY KEY (id_estado),
  UNIQUE KEY uq_estado_codigo (codigo)
) ENGINE=InnoDB;

INSERT INTO estados_evaluacion (codigo,nombre,descripcion,orden) VALUES
 ('PENDIENTE_REVISION','Pendiente de revisión','Aún no se ha emitido una decisión de revisión.',1),
 ('EN_REVISION','En revisión','La evidencia está siendo revisada.',2),
 ('OBSERVADA','Observada','Se registró una observación para atender.',3),
 ('ACEPTADA','Aceptada','La evidencia fue aceptada en el flujo configurado.',4),
 ('RECHAZADA','Rechazada','La evidencia fue rechazada en el flujo configurado.',5),
 ('REENVIADA_REVISION','Reenviada para revisión','La evidencia fue presentada nuevamente para revisión.',6);

CREATE TABLE transiciones_estado_evaluacion (
  id_transicion INT NOT NULL AUTO_INCREMENT,
  id_instrumento INT NOT NULL,
  id_estado_anterior INT NULL,
  estado_anterior_clave INT AS (IFNULL(id_estado_anterior,0)) PERSISTENT,
  id_estado_nuevo INT NOT NULL,
  requiere_motivo TINYINT(1) NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_transicion),
  UNIQUE KEY uq_transicion (id_instrumento,estado_anterior_clave,id_estado_nuevo),
  CONSTRAINT fk_transicion_instrumento FOREIGN KEY (id_instrumento)
    REFERENCES instrumentos(id_instrumento) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_transicion_anterior FOREIGN KEY (id_estado_anterior)
    REFERENCES estados_evaluacion(id_estado) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_transicion_nuevo FOREIGN KEY (id_estado_nuevo)
    REFERENCES estados_evaluacion(id_estado) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE evaluaciones_versionadas (
  id_evaluacion BIGINT NOT NULL AUTO_INCREMENT,
  id_evidencia INT NOT NULL,
  id_instrumento INT NOT NULL,
  id_convocatoria INT NULL,
  id_version INT NULL,
  contexto_version_clave INT AS (IFNULL(id_version,0)) PERSISTENT,
  id_estado INT NULL,
  resultado DECIMAL(6,2) NULL,
  comentario VARCHAR(1000) NULL,
  id_usuario_eval INT NULL,
  id_calificacion_legada INT NULL,
  cerrada TINYINT(1) NOT NULL DEFAULT 0,
  creada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  cerrada_en DATETIME NULL,
  PRIMARY KEY (id_evaluacion),
  UNIQUE KEY uq_evaluacion_legada (id_calificacion_legada),
  UNIQUE KEY uq_evaluacion_contexto (id_evidencia,id_instrumento,contexto_version_clave),
  UNIQUE KEY uq_evaluacion_id_version (id_evaluacion,id_version),
  KEY idx_evaluacion_convocatoria (id_convocatoria,id_instrumento),
  CONSTRAINT fk_eval_versionada_evidencia FOREIGN KEY (id_evidencia)
    REFERENCES evidencias(id_evidencia) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_eval_versionada_instrumento FOREIGN KEY (id_instrumento)
    REFERENCES instrumentos(id_instrumento) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_eval_versionada_conv_inst FOREIGN KEY (id_convocatoria,id_instrumento)
    REFERENCES convocatorias(id_convocatoria,id_instrumento) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_eval_versionada_version_conv FOREIGN KEY (id_version,id_convocatoria)
    REFERENCES versiones_criterios(id_version,id_convocatoria) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_eval_versionada_estado FOREIGN KEY (id_estado)
    REFERENCES estados_evaluacion(id_estado) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_eval_versionada_usuario FOREIGN KEY (id_usuario_eval)
    REFERENCES usuarios(id_usuario) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_eval_versionada_legada FOREIGN KEY (id_calificacion_legada)
    REFERENCES calificacion_evidencia(id_calificacion) ON DELETE RESTRICT ON UPDATE CASCADE,
  CHECK ((id_convocatoria IS NULL AND id_version IS NULL) OR (id_convocatoria IS NOT NULL AND id_version IS NOT NULL))
) ENGINE=InnoDB;

CREATE TABLE historial_evaluaciones (
  id_historial BIGINT NOT NULL AUTO_INCREMENT,
  id_evaluacion BIGINT NOT NULL,
  tipo_evento ENUM('CREADA','ACTUALIZADA','OBSERVACION','CAMBIO_ESTADO','CIERRE','REAPERTURA','MIGRACION_LEGADA') NOT NULL,
  resultado_anterior DECIMAL(6,2) NULL,
  resultado_nuevo DECIMAL(6,2) NULL,
  comentario_anterior VARCHAR(1000) NULL,
  comentario_nuevo VARCHAR(1000) NULL,
  id_estado_anterior INT NULL,
  id_estado_nuevo INT NULL,
  id_usuario_actor INT NULL,
  actor_nombre VARCHAR(302) NULL,
  actor_correo VARCHAR(150) NULL,
  motivo TEXT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_historial),
  KEY idx_historial_evaluacion_fecha (id_evaluacion,creado_en,id_historial),
  KEY idx_historial_actor (id_usuario_actor),
  CONSTRAINT fk_historial_evaluacion FOREIGN KEY (id_evaluacion)
    REFERENCES evaluaciones_versionadas(id_evaluacion) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_historial_estado_anterior FOREIGN KEY (id_estado_anterior)
    REFERENCES estados_evaluacion(id_estado) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_historial_estado_nuevo FOREIGN KEY (id_estado_nuevo)
    REFERENCES estados_evaluacion(id_estado) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_historial_actor FOREIGN KEY (id_usuario_actor)
    REFERENCES usuarios(id_usuario) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TRIGGER trg_historial_evaluaciones_no_update
BEFORE UPDATE ON historial_evaluaciones FOR EACH ROW
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='El historial de evaluaciones es inmutable';
CREATE TRIGGER trg_historial_evaluaciones_no_delete
BEFORE DELETE ON historial_evaluaciones FOR EACH ROW
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='El historial de evaluaciones es inmutable';

CREATE TABLE resultado_criterio_versionado (
  id_evaluacion BIGINT NOT NULL,
  id_version INT NOT NULL,
  id_criterio INT NOT NULL,
  resultado_json LONGTEXT NULL CHECK (resultado_json IS NULL OR JSON_VALID(resultado_json)),
  comentario TEXT NULL,
  PRIMARY KEY (id_evaluacion,id_criterio),
  CONSTRAINT fk_rescrit_evaluacion FOREIGN KEY (id_evaluacion,id_version)
    REFERENCES evaluaciones_versionadas(id_evaluacion,id_version) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_rescrit_criterio FOREIGN KEY (id_criterio,id_version)
    REFERENCES criterios_academicos(id_criterio,id_version) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE resultado_subcriterio_versionado (
  id_evaluacion BIGINT NOT NULL,
  id_version INT NOT NULL,
  id_subcriterio INT NOT NULL,
  resultado_json LONGTEXT NULL CHECK (resultado_json IS NULL OR JSON_VALID(resultado_json)),
  comentario TEXT NULL,
  PRIMARY KEY (id_evaluacion,id_subcriterio),
  CONSTRAINT fk_ressub_evaluacion FOREIGN KEY (id_evaluacion,id_version)
    REFERENCES evaluaciones_versionadas(id_evaluacion,id_version) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_ressub_subcriterio FOREIGN KEY (id_subcriterio,id_version)
    REFERENCES subcriterios_academicos(id_subcriterio,id_version) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Captura vigente de resultados que ya existían; no infiere estado institucional.
INSERT INTO evaluaciones_versionadas
  (id_evidencia,id_instrumento,id_estado,resultado,comentario,id_usuario_eval,id_calificacion_legada,creada_en,actualizada_en)
SELECT id_evidencia,id_instrumento,NULL,resultado,comentario,id_usuario_eval,id_calificacion,calificado_en,actualizado_en
FROM calificacion_evidencia;

-- Hito base para que cada calificación legada tenga un origen visible en su historial.
INSERT INTO historial_evaluaciones
  (id_evaluacion,tipo_evento,resultado_anterior,resultado_nuevo,comentario_anterior,comentario_nuevo,
   id_estado_anterior,id_estado_nuevo,id_usuario_actor,actor_nombre,actor_correo,motivo,creado_en)
SELECT ev.id_evaluacion,'MIGRACION_LEGADA',NULL,ce.resultado,NULL,ce.comentario,NULL,NULL,
       ce.id_usuario_eval,
       TRIM(CONCAT_WS(' ',u.nombre,u.apellidop,u.apellidom)),u.correo,
       'Importación de la calificación vigente; no se infirió un estado institucional.',
       ce.actualizado_en
FROM calificacion_evidencia ce
JOIN evaluaciones_versionadas ev ON ev.id_calificacion_legada=ce.id_calificacion
LEFT JOIN usuarios u ON u.id_usuario=ce.id_usuario_eval;
