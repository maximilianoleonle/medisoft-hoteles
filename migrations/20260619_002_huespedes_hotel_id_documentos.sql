-- Fase HUES-REG-002 - Hotelizar huespedes para vinculacion documental.
--
-- OBJETIVO:
-- - Permitir que Centro Documental vincule documentos a huespedes por hotel.
-- - Corregir el caso donde la identificacion/INE no se podia vincular porque
--   huespedes no tenia hotel_id.
-- - Backfill seguro desde reservaciones existentes.
-- - Mantener hotel_id nullable para no bloquear huespedes historicos sin reserva.
--
-- Rollback manual seguro:
-- ALTER TABLE huespedes DROP FOREIGN KEY fk_huespedes_hotel;
-- ALTER TABLE huespedes DROP INDEX idx_huespedes_hotel_id;
-- ALTER TABLE huespedes DROP COLUMN hotel_id;
-- DELETE FROM migrations WHERE nombre = '20260619_002_huespedes_hotel_id_documentos.sql';

SET @migration_name := '20260619_002_huespedes_hotel_id_documentos.sql';

START TRANSACTION;

SET @huesped_hotel_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'huespedes'
      AND COLUMN_NAME = 'hotel_id'
);

SET @sql := IF(
    @huesped_hotel_exists = 0,
    'ALTER TABLE huespedes ADD COLUMN hotel_id INT NULL AFTER id',
    'SELECT ''huespedes.hotel_id ya existe'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE huespedes h
INNER JOIN (
    SELECT huesped_id, MIN(hotel_id) AS hotel_id
    FROM reservaciones
    WHERE huesped_id IS NOT NULL
      AND hotel_id IS NOT NULL
    GROUP BY huesped_id
) r ON r.huesped_id = h.id
SET h.hotel_id = r.hotel_id
WHERE h.hotel_id IS NULL;

SET @huesped_hotel_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'huespedes'
      AND INDEX_NAME = 'idx_huespedes_hotel_id'
);

SET @sql := IF(
    @huesped_hotel_index_exists = 0,
    'ALTER TABLE huespedes ADD INDEX idx_huespedes_hotel_id (hotel_id)',
    'SELECT ''idx_huespedes_hotel_id ya existe'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @huesped_hotel_fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND CONSTRAINT_NAME = 'fk_huespedes_hotel'
);

SET @sql := IF(
    @huesped_hotel_fk_exists = 0,
    'ALTER TABLE huespedes ADD CONSTRAINT fk_huespedes_hotel FOREIGN KEY (hotel_id) REFERENCES hoteles (id) ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT ''fk_huespedes_hotel ya existe'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columns_ok := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'huespedes'
      AND COLUMN_NAME = 'hotel_id'
);

SET @validation_sql := IF(
    @columns_ok = 1,
    'SELECT ''OK: huespedes.hotel_id verificado'' AS resultado',
    'SELECT no_existe_columna_huespedes_hotel_id'
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
