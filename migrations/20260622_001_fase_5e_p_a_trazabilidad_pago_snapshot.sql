-- Fase 5E-P-A - Trazabilidad fuerte de pago desde snapshot de pre-nomina.
--
-- OBJETIVO:
-- - Agregar relacion nullable desde trabajador_pagos_caja hacia el snapshot
--   administrativo que origino un pago individual.
-- - Mantener pagos historicos sin backfill automatico.
-- - No recalcular snapshots, saldos, Caja, cortes ni movimientos.
-- - No crear nomina oficial, CFDI, timbrado, dispersion ni pago masivo.
-- - No tocar PWA/offline, IndexedDB, cache names ni /api/sync.

SET @migration_name := '20260622_001_fase_5e_p_a_trazabilidad_pago_snapshot.sql';

SET @has_period_column := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trabajador_pagos_caja'
      AND COLUMN_NAME = 'nomina_periodo_id'
);

SET @sql := IF(
    @has_period_column = 0,
    'ALTER TABLE trabajador_pagos_caja ADD COLUMN nomina_periodo_id INT NULL AFTER corte_id',
    'SELECT ''OK: columna nomina_periodo_id ya existe'' AS resultado'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_detail_column := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trabajador_pagos_caja'
      AND COLUMN_NAME = 'nomina_periodo_detalle_id'
);

SET @sql := IF(
    @has_detail_column = 0,
    'ALTER TABLE trabajador_pagos_caja ADD COLUMN nomina_periodo_detalle_id INT NULL AFTER nomina_periodo_id',
    'SELECT ''OK: columna nomina_periodo_detalle_id ya existe'' AS resultado'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_period_index := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trabajador_pagos_caja'
      AND INDEX_NAME = 'idx_trabajador_pagos_caja_nomina_periodo'
);

SET @sql := IF(
    @has_period_index = 0,
    'ALTER TABLE trabajador_pagos_caja ADD INDEX idx_trabajador_pagos_caja_nomina_periodo (nomina_periodo_id)',
    'SELECT ''OK: indice idx_trabajador_pagos_caja_nomina_periodo ya existe'' AS resultado'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_detail_index := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trabajador_pagos_caja'
      AND INDEX_NAME = 'idx_trabajador_pagos_caja_nomina_detalle'
);

SET @sql := IF(
    @has_detail_index = 0,
    'ALTER TABLE trabajador_pagos_caja ADD INDEX idx_trabajador_pagos_caja_nomina_detalle (nomina_periodo_detalle_id)',
    'SELECT ''OK: indice idx_trabajador_pagos_caja_nomina_detalle ya existe'' AS resultado'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_period_fk := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trabajador_pagos_caja'
      AND CONSTRAINT_NAME = 'fk_trabajador_pagos_caja_nomina_periodo'
);

SET @sql := IF(
    @has_period_fk = 0,
    'ALTER TABLE trabajador_pagos_caja ADD CONSTRAINT fk_trabajador_pagos_caja_nomina_periodo FOREIGN KEY (nomina_periodo_id) REFERENCES trabajador_nomina_periodos (id) ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT ''OK: constraint fk_trabajador_pagos_caja_nomina_periodo ya existe'' AS resultado'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_detail_fk := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trabajador_pagos_caja'
      AND CONSTRAINT_NAME = 'fk_trabajador_pagos_caja_nomina_detalle'
);

SET @sql := IF(
    @has_detail_fk = 0,
    'ALTER TABLE trabajador_pagos_caja ADD CONSTRAINT fk_trabajador_pagos_caja_nomina_detalle FOREIGN KEY (nomina_periodo_detalle_id) REFERENCES trabajador_nomina_periodo_detalles (id) ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT ''OK: constraint fk_trabajador_pagos_caja_nomina_detalle ya existe'' AS resultado'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columns_ok := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trabajador_pagos_caja'
      AND COLUMN_NAME IN ('nomina_periodo_id', 'nomina_periodo_detalle_id')
);

SET @indexes_ok := (
    SELECT COUNT(DISTINCT INDEX_NAME)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trabajador_pagos_caja'
      AND INDEX_NAME IN (
          'idx_trabajador_pagos_caja_nomina_periodo',
          'idx_trabajador_pagos_caja_nomina_detalle'
      )
);

SET @constraints_ok := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trabajador_pagos_caja'
      AND CONSTRAINT_NAME IN (
          'fk_trabajador_pagos_caja_nomina_periodo',
          'fk_trabajador_pagos_caja_nomina_detalle'
      )
);

SET @partial_links := (
    SELECT COUNT(*)
    FROM trabajador_pagos_caja
    WHERE (nomina_periodo_id IS NULL AND nomina_periodo_detalle_id IS NOT NULL)
       OR (nomina_periodo_id IS NOT NULL AND nomina_periodo_detalle_id IS NULL)
);

SET @validation_sql := IF(
    @columns_ok = 2
    AND @indexes_ok = 2
    AND @constraints_ok = 2
    AND @partial_links = 0,
    'SELECT ''OK: trazabilidad snapshot 5E-P-A disponible sin backfill parcial'' AS resultado',
    'SELECT no_existe_contrato_5e_p_a_trazabilidad_pago_snapshot'
);

PREPARE stmt FROM @validation_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO migrations (nombre, batch, checksum, estado)
SELECT @migration_name, COALESCE(MAX(batch), 0) + 1, SHA2(CONCAT(@migration_name, '|v1|trazabilidad-pago-snapshot'), 256), 'ejecutada'
FROM migrations
ON DUPLICATE KEY UPDATE
    checksum = VALUES(checksum),
    estado = 'ejecutada',
    ejecutada_en = CURRENT_TIMESTAMP;

-- Rollback manual documentado:
--
-- Ejecutar solo con autorizacion explicita y backup verificado.
-- Si existen pagos con nomina_periodo_id o nomina_periodo_detalle_id no nulos,
-- no eliminar columnas sin fase formal de reconciliacion.
--
-- SELECT COUNT(*) AS pagos_snapshot_trazados
-- FROM trabajador_pagos_caja
-- WHERE nomina_periodo_id IS NOT NULL
--    OR nomina_periodo_detalle_id IS NOT NULL;
--
-- ALTER TABLE trabajador_pagos_caja
--   DROP FOREIGN KEY fk_trabajador_pagos_caja_nomina_detalle;
--
-- ALTER TABLE trabajador_pagos_caja
--   DROP FOREIGN KEY fk_trabajador_pagos_caja_nomina_periodo;
--
-- ALTER TABLE trabajador_pagos_caja
--   DROP INDEX idx_trabajador_pagos_caja_nomina_detalle;
--
-- ALTER TABLE trabajador_pagos_caja
--   DROP INDEX idx_trabajador_pagos_caja_nomina_periodo;
--
-- ALTER TABLE trabajador_pagos_caja
--   DROP COLUMN nomina_periodo_detalle_id,
--   DROP COLUMN nomina_periodo_id;
--
-- DELETE FROM migrations
-- WHERE nombre = '20260622_001_fase_5e_p_a_trazabilidad_pago_snapshot.sql';
