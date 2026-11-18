-- Cola idempotente y resultados de procesamiento documental local.
-- Los archivos originales permanecen en uploads/files y no se copian a esta migracion.
CREATE TABLE trabajos_documentales (
  id_trabajo BIGINT NOT NULL AUTO_INCREMENT,
  sha256 CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  mime VARCHAR(100) CHARACTER SET ascii NOT NULL,
  bytes BIGINT UNSIGNED NOT NULL,
  pipeline_version VARCHAR(40) CHARACTER SET ascii NOT NULL,
  proveedor_solicitado VARCHAR(60) CHARACTER SET ascii NOT NULL,
  proveedor_usado VARCHAR(100) CHARACTER SET ascii NULL,
  estado ENUM('PENDIENTE','EN_PROCESO','PROCESADO','REQUIERE_REVISION','ERROR','CANCELADO') NOT NULL DEFAULT 'PENDIENTE',
  intentos SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  max_intentos SMALLINT UNSIGNED NOT NULL DEFAULT 3,
  mensaje_error VARCHAR(500) NULL,
  token_worker CHAR(32) CHARACTER SET ascii NULL,
  lease_hasta DATETIME NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  iniciado_en DATETIME NULL,
  procesado_en DATETIME NULL,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_trabajo),
  UNIQUE KEY uq_trabajo_documento (sha256,pipeline_version,proveedor_solicitado),
  KEY idx_trabajo_pendiente (estado,intentos,creado_en),
  KEY idx_trabajo_lease (estado,lease_hasta)
) ENGINE=InnoDB;

CREATE TABLE origenes_trabajo_documental (
  id_origen_trabajo BIGINT NOT NULL AUTO_INCREMENT,
  id_trabajo BIGINT NOT NULL,
  id_evidencia INT NOT NULL,
  tipo_origen ENUM('EVIDENCIA','ATRIBUTO') NOT NULL,
  id_origen BIGINT NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_origen_trabajo),
  UNIQUE KEY uq_origen_trabajo (id_trabajo,tipo_origen,id_origen),
  KEY idx_origen_evidencia (id_evidencia,id_origen_trabajo),
  CONSTRAINT fk_origen_trabajo FOREIGN KEY (id_trabajo)
    REFERENCES trabajos_documentales(id_trabajo) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_origen_evidencia FOREIGN KEY (id_evidencia)
    REFERENCES evidencias(id_evidencia) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE resultados_trabajo_documental (
  id_trabajo BIGINT NOT NULL,
  texto_extraido MEDIUMTEXT NULL,
  metadatos_json LONGTEXT NULL CHECK (metadatos_json IS NULL OR JSON_VALID(metadatos_json)),
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_trabajo),
  CONSTRAINT fk_resultado_trabajo FOREIGN KEY (id_trabajo)
    REFERENCES trabajos_documentales(id_trabajo) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;
