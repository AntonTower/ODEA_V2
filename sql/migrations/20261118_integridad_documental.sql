-- ODEA: metadatos SHA-256 para archivos principales y archivos de atributos.
-- Respaldar previamente. Este archivo se entrega, pero no se ejecuta automáticamente.
ALTER TABLE evidencias
  ADD COLUMN archivo_sha256 CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER archivo,
  ADD COLUMN archivo_mime VARCHAR(100) CHARACTER SET ascii NULL AFTER archivo_sha256,
  ADD COLUMN archivo_bytes BIGINT UNSIGNED NULL AFTER archivo_mime,
  ADD KEY idx_evidencias_archivo_sha256 (archivo_sha256);

ALTER TABLE evidencia_valores_atributo
  ADD COLUMN valor_archivo_sha256 CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL AFTER valor_archivo,
  ADD COLUMN valor_archivo_mime VARCHAR(100) CHARACTER SET ascii NULL AFTER valor_archivo_sha256,
  ADD COLUMN valor_archivo_bytes BIGINT UNSIGNED NULL AFTER valor_archivo_mime,
  ADD KEY idx_eva_archivo_sha256 (valor_archivo_sha256);
