-- Permitir numeros/nombres de habitacion repetidos entre hoteles.
-- Alcance:
-- - habitaciones
-- - Convierte el indice unico global de numero a indice unico por hotel.
-- - No toca reservaciones, caja, PWA/offline, /api/sync ni datos operativos.

SET @migration_name := '20260624_001_habitaciones_numero_unico_por_hotel.sql';

SET @has_hotel_id := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'habitaciones'
      AND COLUMN_NAME = 'hotel_id'
);

SET @sql := IF(
    @has_hotel_id = 0,
    'SIGNAL SQLSTATE ''45000'' SET MESSAGE_TEXT = ''habitaciones.hotel_id no existe; no se puede crear unico por hotel''',
    'SELECT ''habitaciones.hotel_id disponible'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @hotel_id_nulls := (
    SELECT COUNT(*)
    FROM habitaciones
    WHERE hotel_id IS NULL
);

SET @sql := IF(
    @hotel_id_nulls > 0,
    'SIGNAL SQLSTATE ''45000'' SET MESSAGE_TEXT = ''Existen habitaciones con hotel_id NULL; corregir antes de cambiar el indice unico''',
    'SELECT ''sin habitaciones con hotel_id NULL'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @duplicados_por_hotel := (
    SELECT COUNT(*)
    FROM (
        SELECT hotel_id, numero, COUNT(*) AS total
        FROM habitaciones
        GROUP BY hotel_id, numero
        HAVING COUNT(*) > 1
    ) AS d
);

SET @sql := IF(
    @duplicados_por_hotel > 0,
    'SIGNAL SQLSTATE ''45000'' SET MESSAGE_TEXT = ''Existen numeros de habitacion duplicados dentro del mismo hotel''',
    'SELECT ''sin duplicados por hotel y numero'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_uk_hotel_numero := (
    SELECT COUNT(DISTINCT INDEX_NAME)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'habitaciones'
      AND INDEX_NAME = 'uk_habitaciones_hotel_numero'
);

SET @sql := IF(
    @has_uk_hotel_numero = 0,
    'ALTER TABLE habitaciones ADD UNIQUE KEY uk_habitaciones_hotel_numero (hotel_id, numero)',
    'SELECT ''uk_habitaciones_hotel_numero ya existe'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @global_unique_numero_index := (
    SELECT INDEX_NAME
    FROM (
        SELECT INDEX_NAME,
               COUNT(*) AS total_columnas,
               SUM(CASE WHEN COLUMN_NAME = 'numero' THEN 1 ELSE 0 END) AS columnas_numero
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'habitaciones'
          AND NON_UNIQUE = 0
          AND INDEX_NAME <> 'PRIMARY'
        GROUP BY INDEX_NAME
        HAVING total_columnas = 1
           AND columnas_numero = 1
        LIMIT 1
    ) AS idx
);

SET @sql := IF(
    @global_unique_numero_index IS NOT NULL,
    CONCAT('ALTER TABLE habitaciones DROP INDEX `', REPLACE(@global_unique_numero_index, '`', '``'), '`'),
    'SELECT ''no existe indice unico global solo por numero'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO migrations (nombre, batch, checksum, estado, ejecutada_en)
SELECT
    @migration_name,
    COALESCE(MAX(batch), 0) + 1,
    NULL,
    'ejecutada',
    NOW()
FROM migrations
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

-- Rollback manual:
-- 1) Confirmar que no existan numeros repetidos globalmente:
--    SELECT numero, COUNT(*) FROM habitaciones GROUP BY numero HAVING COUNT(*) > 1;
-- 2) Si el resultado es vacio:
--    ALTER TABLE habitaciones ADD UNIQUE KEY numero (numero);
--    ALTER TABLE habitaciones DROP INDEX uk_habitaciones_hotel_numero;
-- 3) Quitar registro de migracion:
--    DELETE FROM migrations WHERE nombre = '20260624_001_habitaciones_numero_unico_por_hotel.sql';
