-- Historial append-only de correcciones y validaciones humanas de extracción.
CREATE TABLE revisiones_metadatos_documentales (
  id_revision BIGINT NOT NULL AUTO_INCREMENT,
  id_evidencia INT NOT NULL,
  id_trabajo BIGINT NOT NULL,
  numero_revision INT UNSIGNED NOT NULL,
  datos_json LONGTEXT NOT NULL CHECK (JSON_VALID(datos_json)),
  id_usuario INT NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_revision),
  UNIQUE KEY uq_revision_metadatos (id_evidencia,id_trabajo,numero_revision),
  KEY idx_revision_metadatos_reciente (id_evidencia,id_trabajo,creado_en),
  CONSTRAINT fk_revision_metadatos_evidencia FOREIGN KEY (id_evidencia)
    REFERENCES evidencias(id_evidencia) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_revision_metadatos_trabajo FOREIGN KEY (id_trabajo)
    REFERENCES trabajos_documentales(id_trabajo) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_revision_metadatos_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuarios(id_usuario) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TRIGGER revisiones_metadatos_no_update BEFORE UPDATE ON revisiones_metadatos_documentales
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='El historial de metadatos es inmutable';
CREATE TRIGGER revisiones_metadatos_no_delete BEFORE DELETE ON revisiones_metadatos_documentales
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='El historial de metadatos es inmutable';
