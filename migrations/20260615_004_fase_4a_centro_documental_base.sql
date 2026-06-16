-- Fase 4A-A: Centro Documental Base
-- Migracion aditiva, idempotente y multi-hotel.
-- Backup previo local:
--   src/storage/backups/phase4a_20260615_182352_before_document_center_medisoft_hoteles_import.sql
--   bytes: 1535817
--   sha256: 698A304F69B312EF06EABA787C83969629F2B14BCA096906CC08CADDC7D898F0
--
-- Alcance:
--   - Crea las tablas documento_tipos, documentos y documento_entidades si no existen.
--   - No inserta datos operativos.
--   - No crea endpoints de carga, descarga ni borrado.
--   - No expone documentos por URL publica.
--
-- Rollback manual seguro:
--   Ejecutar solo con autorizacion explicita y si las tres tablas estan vacias.
--   SELECT COUNT(*) FROM documento_entidades;
--   SELECT COUNT(*) FROM documentos;
--   SELECT COUNT(*) FROM documento_tipos;
--   DROP TABLE documento_entidades;
--   DROP TABLE documentos;
--   DROP TABLE documento_tipos;
--   DELETE FROM migrations WHERE nombre = '20260615_004_fase_4a_centro_documental_base.sql';

SET @migration_name := '20260615_004_fase_4a_centro_documental_base.sql';

START TRANSACTION;

CREATE TABLE IF NOT EXISTS documento_tipos (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NULL,
    clave VARCHAR(80) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    descripcion VARCHAR(255) NULL,
    mime_permitidos TEXT NULL,
    max_size_mb DECIMAL(6,2) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_documento_tipos_hotel_clave (hotel_id, clave),
    KEY idx_documento_tipos_hotel_id (hotel_id),
    KEY idx_documento_tipos_activo (activo),
    CONSTRAINT fk_documento_tipos_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_documento_tipos_max_size
        CHECK (max_size_mb IS NULL OR max_size_mb >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documentos (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    documento_tipo_id INT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    storage_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL,
    sha256 CHAR(64) NULL,
    titulo VARCHAR(180) NULL,
    descripcion VARCHAR(255) NULL,
    etiquetas TEXT NULL,
    estado ENUM('activo', 'archivado', 'eliminado') NOT NULL DEFAULT 'activo',
    subido_por_usuario_id INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_documentos_hotel_estado (hotel_id, estado),
    KEY idx_documentos_hotel_tipo (hotel_id, documento_tipo_id),
    KEY idx_documentos_documento_tipo_id (documento_tipo_id),
    KEY idx_documentos_usuario_id (subido_por_usuario_id),
    KEY idx_documentos_sha256 (sha256),
    CONSTRAINT fk_documentos_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_documentos_tipo
        FOREIGN KEY (documento_tipo_id) REFERENCES documento_tipos (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_documentos_usuario
        FOREIGN KEY (subido_por_usuario_id) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_documentos_size_bytes
        CHECK (size_bytes > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documento_entidades (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    documento_id INT NOT NULL,
    entidad_tipo ENUM('proveedor', 'compra', 'cuenta_por_pagar', 'huesped', 'reservacion', 'trabajador') NOT NULL,
    entidad_id INT NOT NULL,
    relacion VARCHAR(80) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_documento_entidades_documento_entidad (hotel_id, documento_id, entidad_tipo, entidad_id),
    KEY idx_documento_entidades_hotel_id (hotel_id),
    KEY idx_documento_entidades_documento_id (documento_id),
    KEY idx_documento_entidades_entidad (hotel_id, entidad_tipo, entidad_id),
    CONSTRAINT fk_documento_entidades_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_documento_entidades_documento
        FOREIGN KEY (documento_id) REFERENCES documentos (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_documento_entidades_entidad_id
        CHECK (entidad_id > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @documento_tipos_columns := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'documento_tipos'
      AND COLUMN_NAME IN (
          'id', 'hotel_id', 'clave', 'nombre', 'descripcion',
          'mime_permitidos', 'max_size_mb', 'activo',
          'created_at', 'updated_at'
      )
);

SET @documentos_columns := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'documentos'
      AND COLUMN_NAME IN (
          'id', 'hotel_id', 'documento_tipo_id', 'nombre_original',
          'nombre_archivo', 'storage_path', 'mime_type', 'size_bytes',
          'sha256', 'titulo', 'descripcion', 'etiquetas', 'estado',
          'subido_por_usuario_id', 'created_at', 'updated_at'
      )
);

SET @documento_entidades_columns := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'documento_entidades'
      AND COLUMN_NAME IN (
          'id', 'hotel_id', 'documento_id', 'entidad_tipo',
          'entidad_id', 'relacion', 'created_at', 'updated_at'
      )
);

SET @validation_sql := IF(
    @documento_tipos_columns = 10
    AND @documentos_columns = 16
    AND @documento_entidades_columns = 8,
    'SELECT ''OK: estructura documental base verificada'' AS resultado',
    'SELECT no_existe_columna_documental_base'
);

PREPARE stmt FROM @validation_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES (@migration_name, 17, SHA2(@migration_name, 256), 'ejecutada')
ON DUPLICATE KEY UPDATE
    checksum = VALUES(checksum),
    estado = 'ejecutada',
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
