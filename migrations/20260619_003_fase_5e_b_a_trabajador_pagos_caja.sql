-- Fase 5E-B-A - Tabla independiente para pagos laborales con Caja.
--
-- OBJETIVO:
-- - Crear trabajador_pagos_caja como tabla aditiva de pagos laborales reales.
-- - Mantener trabajador_pagos como tabla de conceptos laborales, sin alterarla.
-- - No insertar pagos historicos.
-- - No crear movimientos de Caja.
-- - No crear categorias de Caja.
-- - No tocar anticipos, prestamos, cortes, saldos, permisos, PWA/offline ni /api/sync.
--
-- BACKUP REALIZADO ANTES DE EJECUTAR EN LOCAL:
-- - Archivo: backups/medisoft_hoteles_import_before_5e_b_a_trabajador_pagos_caja_20260619_220444.sql
-- - SHA256: 2E279999DA95C0216942F1FE480E5E43E96AAE42A06DA7C9FD83633BC53898BC
-- - Tamano: 1685511 bytes

SET @migration_name := '20260619_003_fase_5e_b_a_trabajador_pagos_caja.sql';

CREATE TABLE IF NOT EXISTS trabajador_pagos_caja (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    trabajador_id INT NOT NULL,
    movimiento_caja_id INT NOT NULL,
    corte_id INT NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia') NOT NULL,
    referencia VARCHAR(120) NOT NULL,
    periodo_inicio DATE NULL,
    periodo_fin DATE NULL,
    concepto VARCHAR(160) NULL,
    fecha_pago DATETIME NOT NULL,
    estado ENUM('pagado', 'revertido') NOT NULL DEFAULT 'pagado',
    notas TEXT NULL,
    created_by INT NULL,
    updated_by INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_trabajador_pagos_caja_hotel_referencia (hotel_id, referencia),
    UNIQUE KEY uk_trabajador_pagos_caja_movimiento (movimiento_caja_id),
    KEY idx_trabajador_pagos_caja_hotel_trabajador (hotel_id, trabajador_id),
    KEY idx_trabajador_pagos_caja_hotel_fecha (hotel_id, fecha_pago),
    KEY idx_trabajador_pagos_caja_hotel_corte (hotel_id, corte_id),
    KEY idx_trabajador_pagos_caja_estado (hotel_id, estado),
    KEY idx_trabajador_pagos_caja_created_by (created_by),
    KEY idx_trabajador_pagos_caja_updated_by (updated_by),
    CONSTRAINT fk_trabajador_pagos_caja_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_pagos_caja_trabajador
        FOREIGN KEY (trabajador_id) REFERENCES trabajadores (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_pagos_caja_movimiento
        FOREIGN KEY (movimiento_caja_id) REFERENCES movimientos_caja (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_pagos_caja_corte
        FOREIGN KEY (corte_id) REFERENCES cortes_caja (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_pagos_caja_created_by
        FOREIGN KEY (created_by) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_pagos_caja_updated_by
        FOREIGN KEY (updated_by) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_trabajador_pagos_caja_monto
        CHECK (monto > 0),
    CONSTRAINT chk_trabajador_pagos_caja_periodo
        CHECK (periodo_inicio IS NULL OR periodo_fin IS NULL OR periodo_fin >= periodo_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @required_columns := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trabajador_pagos_caja'
      AND COLUMN_NAME IN (
          'id',
          'hotel_id',
          'trabajador_id',
          'movimiento_caja_id',
          'corte_id',
          'monto',
          'metodo_pago',
          'referencia',
          'periodo_inicio',
          'periodo_fin',
          'concepto',
          'fecha_pago',
          'estado',
          'notas',
          'created_by',
          'updated_by',
          'created_at',
          'updated_at'
      )
);

SET @required_indexes := (
    SELECT COUNT(DISTINCT INDEX_NAME)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trabajador_pagos_caja'
      AND INDEX_NAME IN (
          'PRIMARY',
          'uk_trabajador_pagos_caja_hotel_referencia',
          'uk_trabajador_pagos_caja_movimiento',
          'idx_trabajador_pagos_caja_hotel_trabajador',
          'idx_trabajador_pagos_caja_hotel_fecha',
          'idx_trabajador_pagos_caja_hotel_corte',
          'idx_trabajador_pagos_caja_estado',
          'idx_trabajador_pagos_caja_created_by',
          'idx_trabajador_pagos_caja_updated_by'
      )
);

SET @required_constraints := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trabajador_pagos_caja'
      AND CONSTRAINT_NAME IN (
          'fk_trabajador_pagos_caja_hotel',
          'fk_trabajador_pagos_caja_trabajador',
          'fk_trabajador_pagos_caja_movimiento',
          'fk_trabajador_pagos_caja_corte',
          'fk_trabajador_pagos_caja_created_by',
          'fk_trabajador_pagos_caja_updated_by',
          'chk_trabajador_pagos_caja_monto',
          'chk_trabajador_pagos_caja_periodo'
      )
);

SET @table_empty := (
    SELECT COUNT(*)
    FROM trabajador_pagos_caja
);

SET @validation_sql := IF(
    @required_columns = 18
    AND @required_indexes = 9
    AND @required_constraints = 8
    AND @table_empty = 0,
    'SELECT ''OK: trabajador_pagos_caja creada, vacia y con contrato 5E-B-A'' AS resultado',
    'SELECT no_existe_contrato_5e_b_a_trabajador_pagos_caja'
);

PREPARE stmt FROM @validation_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO migrations (nombre, batch, checksum, estado)
SELECT @migration_name, COALESCE(MAX(batch), 0) + 1, SHA2(CONCAT(@migration_name, '|v1|trabajador-pagos-caja'), 256), 'ejecutada'
FROM migrations
ON DUPLICATE KEY UPDATE
    checksum = VALUES(checksum),
    estado = 'ejecutada',
    ejecutada_en = CURRENT_TIMESTAMP;

-- Rollback manual documentado:
--
-- Ejecutar solo con autorizacion explicita y si la tabla esta vacia:
--
-- SELECT COUNT(*) AS pagos_laborales_caja
-- FROM trabajador_pagos_caja;
--
-- DROP TABLE trabajador_pagos_caja;
--
-- DELETE FROM migrations
-- WHERE nombre = '20260619_003_fase_5e_b_a_trabajador_pagos_caja.sql';
--
-- Si existen pagos laborales reales, no eliminar la tabla sin fase formal de
-- reversion/reconciliacion.
