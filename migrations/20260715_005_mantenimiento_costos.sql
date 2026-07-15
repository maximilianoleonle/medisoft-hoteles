-- Mantenimiento Plus F2: costo real hacia gastos de caja
-- Migracion aditiva, idempotente y multi-hotel.
--
-- Alcance:
--   - Agrega a mantenimientos_habitaciones: costo_estimado, proveedor,
--     nota_costo y la liga al egreso de caja (gasto_movimiento_id +
--     gasto_registrado_en). La columna existente `costo` sigue siendo el
--     costo REAL del trabajo.
--   - NO toca movimientos_caja ni cortes: el egreso se crea solo por el
--     flujo existente de caja (MovimientoCaja::registrarMovimiento).
--
-- Rollback manual seguro (solo con autorizacion explicita):
--   ALTER TABLE mantenimientos_habitaciones
--     DROP COLUMN costo_estimado, DROP COLUMN proveedor, DROP COLUMN nota_costo,
--     DROP COLUMN gasto_movimiento_id, DROP COLUMN gasto_registrado_en;
--   DELETE FROM migrations WHERE nombre = '20260715_005_mantenimiento_costos.sql';

SET @migration_name := '20260715_005_mantenimiento_costos.sql';

START TRANSACTION;

SET @tabla := 'mantenimientos_habitaciones';

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @tabla AND COLUMN_NAME = 'costo_estimado');
SET @ddl := IF(@col = 0,
    'ALTER TABLE mantenimientos_habitaciones ADD COLUMN costo_estimado DECIMAL(10,2) NULL DEFAULT NULL AFTER costo',
    'SELECT ''OK: costo_estimado ya existe'' AS resultado');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @tabla AND COLUMN_NAME = 'proveedor');
SET @ddl := IF(@col = 0,
    'ALTER TABLE mantenimientos_habitaciones ADD COLUMN proveedor VARCHAR(200) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER costo_estimado',
    'SELECT ''OK: proveedor ya existe'' AS resultado');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @tabla AND COLUMN_NAME = 'nota_costo');
SET @ddl := IF(@col = 0,
    'ALTER TABLE mantenimientos_habitaciones ADD COLUMN nota_costo VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER proveedor',
    'SELECT ''OK: nota_costo ya existe'' AS resultado');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @tabla AND COLUMN_NAME = 'gasto_movimiento_id');
SET @ddl := IF(@col = 0,
    'ALTER TABLE mantenimientos_habitaciones ADD COLUMN gasto_movimiento_id INT NULL DEFAULT NULL AFTER nota_costo, ADD KEY idx_mant_gasto_movimiento (gasto_movimiento_id)',
    'SELECT ''OK: gasto_movimiento_id ya existe'' AS resultado');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @tabla AND COLUMN_NAME = 'gasto_registrado_en');
SET @ddl := IF(@col = 0,
    'ALTER TABLE mantenimientos_habitaciones ADD COLUMN gasto_registrado_en DATETIME NULL DEFAULT NULL AFTER gasto_movimiento_id',
    'SELECT ''OK: gasto_registrado_en ya existe'' AS resultado');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO migrations (nombre, batch, checksum, estado)
SELECT @migration_name, COALESCE(MAX(batch), 0) + 1, SHA2(CONCAT(@migration_name, '|v1'), 256), 'ejecutada'
FROM migrations
ON DUPLICATE KEY UPDATE
    checksum = VALUES(checksum),
    estado = 'ejecutada',
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
