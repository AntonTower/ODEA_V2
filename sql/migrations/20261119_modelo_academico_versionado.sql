-- Modelo académico configurable. Crear vacío: no cargar criterios/periodos ficticios.
-- No elimina ni modifica instrumentos, tipos documentales o resultados actuales.
CREATE TABLE periodos_academicos (
  id_periodo INT NOT NULL AUTO_INCREMENT,
  id_instrumento INT NOT NULL,
  codigo VARCHAR(60) NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  fecha_inicio DATE NULL,
  fecha_fin DATE NULL,
  estado ENUM('BORRADOR','ACTIVO','INACTIVO') NOT NULL DEFAULT 'BORRADOR',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_periodo),
  UNIQUE KEY uq_periodo_instrumento_codigo (id_instrumento,codigo),
  UNIQUE KEY uq_periodo_id_instrumento (id_periodo,id_instrumento),
  CONSTRAINT fk_periodo_instrumento FOREIGN KEY (id_instrumento)
    REFERENCES instrumentos(id_instrumento) ON DELETE RESTRICT ON UPDATE CASCADE,
  CHECK (fecha_inicio IS NULL OR fecha_fin IS NULL OR fecha_fin>=fecha_inicio)
) ENGINE=InnoDB;

CREATE TABLE convocatorias (
  id_convocatoria INT NOT NULL AUTO_INCREMENT,
  id_instrumento INT NOT NULL,
  id_periodo INT NOT NULL,
  codigo VARCHAR(60) NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  descripcion TEXT NULL,
  fecha_apertura DATE NULL,
  fecha_cierre DATE NULL,
  estado ENUM('BORRADOR','ACTIVA','CERRADA','INACTIVA') NOT NULL DEFAULT 'BORRADOR',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_convocatoria),
  UNIQUE KEY uq_conv_instrumento_codigo (id_instrumento,codigo),
  UNIQUE KEY uq_conv_id_instrumento (id_convocatoria,id_instrumento),
  CONSTRAINT fk_conv_instrumento FOREIGN KEY (id_instrumento)
    REFERENCES instrumentos(id_instrumento) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_conv_periodo_instrumento FOREIGN KEY (id_periodo,id_instrumento)
    REFERENCES periodos_academicos(id_periodo,id_instrumento) ON DELETE RESTRICT ON UPDATE CASCADE,
  CHECK (fecha_apertura IS NULL OR fecha_cierre IS NULL OR fecha_cierre>=fecha_apertura)
) ENGINE=InnoDB;

CREATE TABLE versiones_criterios (
  id_version INT NOT NULL AUTO_INCREMENT,
  id_convocatoria INT NOT NULL,
  codigo_version VARCHAR(40) NOT NULL,
  nombre VARCHAR(150) NULL,
  estado ENUM('BORRADOR','PUBLICADA','CERRADA','RETIRADA') NOT NULL DEFAULT 'BORRADOR',
  vigencia_desde DATE NULL,
  vigencia_hasta DATE NULL,
  publicada_en DATETIME NULL,
  creada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_version),
  UNIQUE KEY uq_version_conv_codigo (id_convocatoria,codigo_version),
  UNIQUE KEY uq_version_id_conv (id_version,id_convocatoria),
  CONSTRAINT fk_version_convocatoria FOREIGN KEY (id_convocatoria)
    REFERENCES convocatorias(id_convocatoria) ON DELETE RESTRICT ON UPDATE CASCADE,
  CHECK (vigencia_desde IS NULL OR vigencia_hasta IS NULL OR vigencia_hasta>=vigencia_desde)
) ENGINE=InnoDB;

CREATE TABLE categorias_academicas (
  id_categoria INT NOT NULL AUTO_INCREMENT,
  id_version INT NOT NULL,
  codigo VARCHAR(60) NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  descripcion TEXT NULL,
  orden INT NOT NULL DEFAULT 1,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  ponderacion DECIMAL(12,6) NULL,
  PRIMARY KEY (id_categoria),
  UNIQUE KEY uq_categoria_version_codigo (id_version,codigo),
  UNIQUE KEY uq_categoria_id_version (id_categoria,id_version),
  CONSTRAINT fk_categoria_version FOREIGN KEY (id_version)
    REFERENCES versiones_criterios(id_version) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE criterios_academicos (
  id_criterio INT NOT NULL AUTO_INCREMENT,
  id_version INT NOT NULL,
  id_categoria INT NOT NULL,
  codigo VARCHAR(60) NOT NULL,
  nombre VARCHAR(180) NOT NULL,
  descripcion TEXT NULL,
  orden INT NOT NULL DEFAULT 1,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  ponderacion DECIMAL(12,6) NULL,
  regla_vigencia_json LONGTEXT NULL CHECK (regla_vigencia_json IS NULL OR JSON_VALID(regla_vigencia_json)),
  PRIMARY KEY (id_criterio),
  UNIQUE KEY uq_criterio_version_codigo (id_version,codigo),
  UNIQUE KEY uq_criterio_id_version (id_criterio,id_version),
  CONSTRAINT fk_criterio_categoria_version FOREIGN KEY (id_categoria,id_version)
    REFERENCES categorias_academicas(id_categoria,id_version) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_criterio_version FOREIGN KEY (id_version)
    REFERENCES versiones_criterios(id_version) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE subcriterios_academicos (
  id_subcriterio INT NOT NULL AUTO_INCREMENT,
  id_version INT NOT NULL,
  id_criterio INT NOT NULL,
  codigo VARCHAR(60) NOT NULL,
  nombre VARCHAR(180) NOT NULL,
  descripcion TEXT NULL,
  orden INT NOT NULL DEFAULT 1,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  ponderacion DECIMAL(12,6) NULL,
  regla_vigencia_json LONGTEXT NULL CHECK (regla_vigencia_json IS NULL OR JSON_VALID(regla_vigencia_json)),
  PRIMARY KEY (id_subcriterio),
  UNIQUE KEY uq_subcriterio_version_codigo (id_version,codigo),
  UNIQUE KEY uq_subcriterio_id_version (id_subcriterio,id_version),
  CONSTRAINT fk_subcriterio_criterio_version FOREIGN KEY (id_criterio,id_version)
    REFERENCES criterios_academicos(id_criterio,id_version) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE requisitos_documentales (
  id_requisito INT NOT NULL AUTO_INCREMENT,
  id_version INT NOT NULL,
  codigo VARCHAR(60) NOT NULL,
  nombre VARCHAR(180) NOT NULL,
  descripcion TEXT NULL,
  obligatorio TINYINT(1) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  vigencia_desde DATE NULL,
  vigencia_hasta DATE NULL,
  regla_vigencia_json LONGTEXT NULL CHECK (regla_vigencia_json IS NULL OR JSON_VALID(regla_vigencia_json)),
  PRIMARY KEY (id_requisito),
  UNIQUE KEY uq_requisito_version_codigo (id_version,codigo),
  UNIQUE KEY uq_requisito_id_version (id_requisito,id_version),
  CONSTRAINT fk_requisito_version FOREIGN KEY (id_version)
    REFERENCES versiones_criterios(id_version) ON DELETE RESTRICT ON UPDATE CASCADE,
  CHECK (vigencia_desde IS NULL OR vigencia_hasta IS NULL OR vigencia_hasta>=vigencia_desde)
) ENGINE=InnoDB;

CREATE TABLE requisito_criterio (
  id_version INT NOT NULL,
  id_requisito INT NOT NULL,
  id_criterio INT NOT NULL,
  PRIMARY KEY (id_requisito,id_criterio),
  CONSTRAINT fk_req_criterio_req FOREIGN KEY (id_requisito,id_version)
    REFERENCES requisitos_documentales(id_requisito,id_version) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_req_criterio_criterio FOREIGN KEY (id_criterio,id_version)
    REFERENCES criterios_academicos(id_criterio,id_version) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE requisito_subcriterio (
  id_version INT NOT NULL,
  id_requisito INT NOT NULL,
  id_subcriterio INT NOT NULL,
  PRIMARY KEY (id_requisito,id_subcriterio),
  CONSTRAINT fk_req_sub_req FOREIGN KEY (id_requisito,id_version)
    REFERENCES requisitos_documentales(id_requisito,id_version) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_req_sub_subcriterio FOREIGN KEY (id_subcriterio,id_version)
    REFERENCES subcriterios_academicos(id_subcriterio,id_version) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE requisito_tipo_documental (
  id_requisito INT NOT NULL,
  id_tipo_evidencia INT NOT NULL,
  PRIMARY KEY (id_requisito,id_tipo_evidencia),
  CONSTRAINT fk_req_tipo_req FOREIGN KEY (id_requisito)
    REFERENCES requisitos_documentales(id_requisito) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_req_tipo_documental FOREIGN KEY (id_tipo_evidencia)
    REFERENCES tipos_de_evidencia(id_tipo_evidencia) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE fuentes_normativas (
  id_fuente INT NOT NULL AUTO_INCREMENT,
  id_version INT NOT NULL,
  nombre_documento VARCHAR(255) NOT NULL,
  fecha_publicacion DATE NULL,
  vigencia_desde DATE NULL,
  vigencia_hasta DATE NULL,
  version_documento VARCHAR(100) NULL,
  referencia_url TEXT NULL,
  id_responsable_validacion INT NULL,
  validacion_estado ENUM('PENDIENTE','VALIDADA','RECHAZADA') NOT NULL DEFAULT 'PENDIENTE',
  validada_en DATETIME NULL,
  PRIMARY KEY (id_fuente),
  UNIQUE KEY uq_fuente_id_version (id_fuente,id_version),
  CONSTRAINT fk_fuente_version FOREIGN KEY (id_version)
    REFERENCES versiones_criterios(id_version) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_fuente_responsable FOREIGN KEY (id_responsable_validacion)
    REFERENCES usuarios(id_usuario) ON DELETE SET NULL ON UPDATE CASCADE,
  CHECK (vigencia_desde IS NULL OR vigencia_hasta IS NULL OR vigencia_hasta>=vigencia_desde)
) ENGINE=InnoDB;

CREATE TABLE criterio_fuente_normativa (
  id_version INT NOT NULL,
  id_criterio INT NOT NULL,
  id_fuente INT NOT NULL,
  PRIMARY KEY (id_criterio,id_fuente),
  CONSTRAINT fk_criterio_fuente_criterio FOREIGN KEY (id_criterio,id_version)
    REFERENCES criterios_academicos(id_criterio,id_version) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_criterio_fuente_fuente FOREIGN KEY (id_fuente,id_version)
    REFERENCES fuentes_normativas(id_fuente,id_version) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE evidencia_criterio_versionado (
  id_version INT NOT NULL,
  id_evidencia INT NOT NULL,
  id_criterio INT NOT NULL,
  vinculada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_evidencia,id_criterio,id_version),
  CONSTRAINT fk_evcrit_evidencia FOREIGN KEY (id_evidencia)
    REFERENCES evidencias(id_evidencia) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_evcrit_criterio FOREIGN KEY (id_criterio,id_version)
    REFERENCES criterios_academicos(id_criterio,id_version) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE evidencia_subcriterio_versionado (
  id_version INT NOT NULL,
  id_evidencia INT NOT NULL,
  id_subcriterio INT NOT NULL,
  vinculada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_evidencia,id_subcriterio,id_version),
  CONSTRAINT fk_evsub_evidencia FOREIGN KEY (id_evidencia)
    REFERENCES evidencias(id_evidencia) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_evsub_subcriterio FOREIGN KEY (id_subcriterio,id_version)
    REFERENCES subcriterios_academicos(id_subcriterio,id_version) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;
