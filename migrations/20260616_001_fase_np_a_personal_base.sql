-- Fase NP-A: Personal base
-- Migracion aditiva, idempotente y multi-hotel.
-- Backup previo local valido:
--   src/storage/backups/phase_np_a_20260616_021311_before_personal_base_medisoft_hoteles_import.sql
--   bytes: 3161464
--   sha256: 0F9E64B040437A73D559534E5753133F4C3508C29F5B9EA0278B351097666246
--
-- Alcance:
--   - Crea tablas base de trabajadores y ledger laboral.
--   - No inserta trabajadores ni movimientos.
--   - No toca usuarios de forma destructiva.
--   - No toca Caja, pagos reales, abonos, CxC ni /api/sync.
--
-- Rollback manual seguro:
--   Ejecutar solo con autorizacion explicita y si TODAS las tablas estan vacias.
--   SELECT COUNT(*) FROM trabajador_documentos;
--   SELECT COUNT(*) FROM trabajador_asistencias;
--   SELECT COUNT(*) FROM trabajador_prestamos;
--   SELECT COUNT(*) FROM trabajador_anticipos;
--   SELECT COUNT(*) FROM trabajador_pagos;
--   SELECT COUNT(*) FROM trabajadores;
--   DROP TABLE trabajador_documentos;
--   DROP TABLE trabajador_asistencias;
--   DROP TABLE trabajador_prestamos;
--   DROP TABLE trabajador_anticipos;
--   DROP TABLE trabajador_pagos;
--   DROP TABLE trabajadores;
--   DELETE FROM migrations WHERE nombre = '20260616_001_fase_np_a_personal_base.sql';

SET @migration_name := '20260616_001_fase_np_a_personal_base.sql';

START TRANSACTION;

CREATE TABLE IF NOT EXISTS trabajadores (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    usuario_id INT NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    identificacion VARCHAR(60) NULL,
    rol_laboral VARCHAR(80) NULL,
    telefono VARCHAR(30) NULL,
    email VARCHAR(120) NULL,
    estado ENUM('activo', 'inactivo', 'baja') NOT NULL DEFAULT 'activo',
    fecha_alta DATE NULL,
    fecha_baja DATE NULL,
    salario_base DECIMAL(12,2) NULL,
    periodicidad_pago ENUM('semanal', 'quincenal', 'mensual', 'por_evento') NULL,
    notas TEXT NULL,
    created_by INT NULL,
    updated_by INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_trabajadores_hotel_estado (hotel_id, estado),
    KEY idx_trabajadores_hotel_rol (hotel_id, rol_laboral),
    KEY idx_trabajadores_usuario_id (usuario_id),
    KEY idx_trabajadores_created_by (created_by),
    KEY idx_trabajadores_updated_by (updated_by),
    CONSTRAINT fk_trabajadores_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trabajadores_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_trabajadores_created_by
        FOREIGN KEY (created_by) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_trabajadores_updated_by
        FOREIGN KEY (updated_by) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_trabajadores_salario_base
        CHECK (salario_base IS NULL OR salario_base >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trabajador_pagos (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    trabajador_id INT NOT NULL,
    tipo ENUM('pago', 'comision', 'bono', 'descuento', 'ajuste') NOT NULL,
    efecto ENUM('a_favor', 'en_contra') NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    concepto VARCHAR(160) NULL,
    periodo_inicio DATE NULL,
    periodo_fin DATE NULL,
    fecha DATE NOT NULL,
    referencia VARCHAR(120) NULL,
    notas TEXT NULL,
    estado ENUM('activo', 'anulado') NOT NULL DEFAULT 'activo',
    created_by INT NULL,
    updated_by INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_trabajador_pagos_hotel_trabajador (hotel_id, trabajador_id),
    KEY idx_trabajador_pagos_hotel_fecha (hotel_id, fecha),
    KEY idx_trabajador_pagos_trabajador_estado (trabajador_id, estado),
    KEY idx_trabajador_pagos_created_by (created_by),
    KEY idx_trabajador_pagos_updated_by (updated_by),
    CONSTRAINT fk_trabajador_pagos_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_pagos_trabajador
        FOREIGN KEY (trabajador_id) REFERENCES trabajadores (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_pagos_created_by
        FOREIGN KEY (created_by) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_pagos_updated_by
        FOREIGN KEY (updated_by) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_trabajador_pagos_monto
        CHECK (monto >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trabajador_anticipos (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    trabajador_id INT NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    saldo_pendiente DECIMAL(12,2) NOT NULL,
    fecha DATE NOT NULL,
    motivo VARCHAR(160) NULL,
    estado ENUM('pendiente', 'descontado', 'cancelado') NOT NULL DEFAULT 'pendiente',
    referencia VARCHAR(120) NULL,
    notas TEXT NULL,
    created_by INT NULL,
    updated_by INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_trabajador_anticipos_hotel_trabajador (hotel_id, trabajador_id),
    KEY idx_trabajador_anticipos_trabajador_estado (trabajador_id, estado),
    KEY idx_trabajador_anticipos_created_by (created_by),
    KEY idx_trabajador_anticipos_updated_by (updated_by),
    CONSTRAINT fk_trabajador_anticipos_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_anticipos_trabajador
        FOREIGN KEY (trabajador_id) REFERENCES trabajadores (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_anticipos_created_by
        FOREIGN KEY (created_by) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_anticipos_updated_by
        FOREIGN KEY (updated_by) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_trabajador_anticipos_monto
        CHECK (monto >= 0),
    CONSTRAINT chk_trabajador_anticipos_saldo
        CHECK (saldo_pendiente >= 0 AND saldo_pendiente <= monto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trabajador_prestamos (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    trabajador_id INT NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    saldo_pendiente DECIMAL(12,2) NOT NULL,
    fecha DATE NOT NULL,
    plazo_meses INT NULL,
    abono_periodico DECIMAL(12,2) NULL,
    motivo VARCHAR(160) NULL,
    estado ENUM('vigente', 'liquidado', 'cancelado') NOT NULL DEFAULT 'vigente',
    referencia VARCHAR(120) NULL,
    notas TEXT NULL,
    created_by INT NULL,
    updated_by INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_trabajador_prestamos_hotel_trabajador (hotel_id, trabajador_id),
    KEY idx_trabajador_prestamos_trabajador_estado (trabajador_id, estado),
    KEY idx_trabajador_prestamos_created_by (created_by),
    KEY idx_trabajador_prestamos_updated_by (updated_by),
    CONSTRAINT fk_trabajador_prestamos_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_prestamos_trabajador
        FOREIGN KEY (trabajador_id) REFERENCES trabajadores (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_prestamos_created_by
        FOREIGN KEY (created_by) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_prestamos_updated_by
        FOREIGN KEY (updated_by) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_trabajador_prestamos_monto
        CHECK (monto >= 0),
    CONSTRAINT chk_trabajador_prestamos_saldo
        CHECK (saldo_pendiente >= 0 AND saldo_pendiente <= monto),
    CONSTRAINT chk_trabajador_prestamos_plazo
        CHECK (plazo_meses IS NULL OR plazo_meses >= 0),
    CONSTRAINT chk_trabajador_prestamos_abono
        CHECK (abono_periodico IS NULL OR abono_periodico >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trabajador_asistencias (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    trabajador_id INT NOT NULL,
    fecha DATE NOT NULL,
    tipo ENUM('asistencia', 'falta', 'retardo', 'permiso', 'incapacidad', 'descanso', 'horas_extra') NOT NULL DEFAULT 'asistencia',
    hora_entrada TIME NULL,
    hora_salida TIME NULL,
    horas DECIMAL(5,2) NULL,
    horas_extra DECIMAL(5,2) NULL,
    observaciones VARCHAR(255) NULL,
    created_by INT NULL,
    updated_by INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_trabajador_asistencias_dia (hotel_id, trabajador_id, fecha),
    KEY idx_trabajador_asistencias_hotel_fecha (hotel_id, fecha),
    KEY idx_trabajador_asistencias_trabajador_fecha (trabajador_id, fecha),
    KEY idx_trabajador_asistencias_created_by (created_by),
    KEY idx_trabajador_asistencias_updated_by (updated_by),
    CONSTRAINT fk_trabajador_asistencias_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_asistencias_trabajador
        FOREIGN KEY (trabajador_id) REFERENCES trabajadores (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_asistencias_created_by
        FOREIGN KEY (created_by) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_asistencias_updated_by
        FOREIGN KEY (updated_by) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_trabajador_asistencias_horas
        CHECK (horas IS NULL OR horas >= 0),
    CONSTRAINT chk_trabajador_asistencias_horas_extra
        CHECK (horas_extra IS NULL OR horas_extra >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trabajador_documentos (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    trabajador_id INT NOT NULL,
    tipo VARCHAR(60) NULL,
    nombre_original VARCHAR(160) NULL,
    ruta_archivo VARCHAR(255) NULL,
    mime VARCHAR(120) NULL,
    tamano INT NULL,
    notas VARCHAR(255) NULL,
    estado ENUM('activo', 'eliminado') NOT NULL DEFAULT 'activo',
    subido_por INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_trabajador_documentos_hotel_trabajador (hotel_id, trabajador_id),
    KEY idx_trabajador_documentos_trabajador_estado (trabajador_id, estado),
    KEY idx_trabajador_documentos_subido_por (subido_por),
    CONSTRAINT fk_trabajador_documentos_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_documentos_trabajador
        FOREIGN KEY (trabajador_id) REFERENCES trabajadores (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_documentos_subido_por
        FOREIGN KEY (subido_por) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_trabajador_documentos_tamano
        CHECK (tamano IS NULL OR tamano >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @trabajadores_columns := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trabajadores'
      AND COLUMN_NAME IN (
          'id', 'hotel_id', 'usuario_id', 'nombre_completo', 'identificacion',
          'rol_laboral', 'telefono', 'email', 'estado', 'fecha_alta',
          'fecha_baja', 'salario_base', 'periodicidad_pago', 'notas',
          'created_by', 'updated_by', 'created_at', 'updated_at'
      )
);

SET @trabajador_pagos_columns := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trabajador_pagos'
      AND COLUMN_NAME IN (
          'id', 'hotel_id', 'trabajador_id', 'tipo', 'efecto', 'monto',
          'concepto', 'periodo_inicio', 'periodo_fin', 'fecha', 'referencia',
          'notas', 'estado', 'created_by', 'updated_by', 'created_at', 'updated_at'
      )
);

SET @trabajador_anticipos_columns := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trabajador_anticipos'
      AND COLUMN_NAME IN (
          'id', 'hotel_id', 'trabajador_id', 'monto', 'saldo_pendiente',
          'fecha', 'motivo', 'estado', 'referencia', 'notas',
          'created_by', 'updated_by', 'created_at', 'updated_at'
      )
);

SET @trabajador_prestamos_columns := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trabajador_prestamos'
      AND COLUMN_NAME IN (
          'id', 'hotel_id', 'trabajador_id', 'monto', 'saldo_pendiente',
          'fecha', 'plazo_meses', 'abono_periodico', 'motivo', 'estado',
          'referencia', 'notas', 'created_by', 'updated_by', 'created_at', 'updated_at'
      )
);

SET @trabajador_asistencias_columns := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trabajador_asistencias'
      AND COLUMN_NAME IN (
          'id', 'hotel_id', 'trabajador_id', 'fecha', 'tipo',
          'hora_entrada', 'hora_salida', 'horas', 'horas_extra',
          'observaciones', 'created_by', 'updated_by', 'created_at', 'updated_at'
      )
);

SET @trabajador_documentos_columns := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trabajador_documentos'
      AND COLUMN_NAME IN (
          'id', 'hotel_id', 'trabajador_id', 'tipo', 'nombre_original',
          'ruta_archivo', 'mime', 'tamano', 'notas', 'estado',
          'subido_por', 'created_at', 'updated_at'
      )
);

SET @validation_sql := IF(
    @trabajadores_columns = 18
    AND @trabajador_pagos_columns = 17
    AND @trabajador_anticipos_columns = 14
    AND @trabajador_prestamos_columns = 16
    AND @trabajador_asistencias_columns = 14
    AND @trabajador_documentos_columns = 13,
    'SELECT ''OK: estructura Personal base NP-A verificada'' AS resultado',
    'SELECT no_existe_columna_personal_base'
);

PREPARE stmt FROM @validation_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES (@migration_name, 19, SHA2(@migration_name, 256), 'ejecutada')
ON DUPLICATE KEY UPDATE
    checksum = VALUES(checksum),
    estado = 'ejecutada',
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
