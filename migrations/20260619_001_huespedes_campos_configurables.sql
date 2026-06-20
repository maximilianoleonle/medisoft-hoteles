-- Fase HUES-REG-001 - Campos configurables de huespedes.
--
-- OBJETIVO:
-- - Permitir que cada hotel configure datos extra del huesped y vehiculo.
-- - Mantener campos existentes sin cambios.
-- - Guardar datos nuevos en JSON flexible.
-- - No tocar PWA, offline, /api/sync, caja ni calculos de reservaciones.
--
-- Rollback manual seguro:
-- ALTER TABLE huesped_vehiculos DROP COLUMN datos_extra_json;
-- ALTER TABLE huespedes DROP COLUMN datos_extra_json;
-- DELETE FROM migrations WHERE nombre = '20260619_001_huespedes_campos_configurables.sql';

SET @migration_name := '20260619_001_huespedes_campos_configurables.sql';

START TRANSACTION;

SET @huesped_extra_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'huespedes'
      AND COLUMN_NAME = 'datos_extra_json'
);

SET @sql := IF(
    @huesped_extra_exists = 0,
    'ALTER TABLE huespedes ADD COLUMN datos_extra_json TEXT NULL AFTER notas',
    'SELECT ''huespedes.datos_extra_json ya existe'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @vehiculo_extra_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'huesped_vehiculos'
      AND COLUMN_NAME = 'datos_extra_json'
);

SET @sql := IF(
    @vehiculo_extra_exists = 0,
    'ALTER TABLE huesped_vehiculos ADD COLUMN datos_extra_json TEXT NULL AFTER estacionamiento',
    'SELECT ''huesped_vehiculos.datos_extra_json ya existe'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columns_ok := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND (
          (TABLE_NAME = 'huespedes' AND COLUMN_NAME = 'datos_extra_json')
          OR (TABLE_NAME = 'huesped_vehiculos' AND COLUMN_NAME = 'datos_extra_json')
      )
);

SET @validation_sql := IF(
    @columns_ok = 2,
    'SELECT ''OK: campos configurables de huespedes verificados'' AS resultado',
    'SELECT no_existe_columna_huespedes_campos_configurables'
);
PREPARE stmt FROM @validation_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO migrations (nombre, batch, checksum, estado)
SELECT @migration_name, COALESCE(MAX(batch), 0) + 1, SHA2(CONCAT(@migration_name, '|v1'), 256), 'ejecutada'
FROM migrations
ON DUPLICATE KEY UPDATE
    checksum = VALUES(checksum),
    estado = 'ejecutada',
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
