-- Permitir configuraciones de inventario repetidas entre hoteles.
-- Alcance:
-- - inventario_config_habitacion
-- - Convierte el indice unico global de (tipo_habitacion, producto_id)
--   a indice unico por hotel.
-- - No toca movimientos, reservaciones, check-in/check-out, caja, PWA/offline ni /api/sync.

SET @migration_name := '20260624_003_inventario_config_habitacion_unico_por_hotel.sql';

DROP PROCEDURE IF EXISTS migrar_inventario_config_habitacion_unico_por_hotel;

DELIMITER $$

CREATE PROCEDURE migrar_inventario_config_habitacion_unico_por_hotel()
BEGIN
    DECLARE v_has_table INT DEFAULT 0;
    DECLARE v_has_hotel_id INT DEFAULT 0;
    DECLARE v_has_tipo_habitacion INT DEFAULT 0;
    DECLARE v_has_producto_id INT DEFAULT 0;
    DECLARE v_hotel_id_nulls INT DEFAULT 0;
    DECLARE v_duplicados_por_hotel INT DEFAULT 0;
    DECLARE v_named_index_exists INT DEFAULT 0;
    DECLARE v_has_composite_unique INT DEFAULT 0;
    DECLARE v_global_unique_tipo_producto_index VARCHAR(128) DEFAULT NULL;

    SELECT COUNT(*)
    INTO v_has_table
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'inventario_config_habitacion';

    IF v_has_table = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'inventario_config_habitacion no existe; no se puede crear unico por hotel';
    END IF;

    SELECT COUNT(*)
    INTO v_has_hotel_id
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'inventario_config_habitacion'
      AND COLUMN_NAME = 'hotel_id';

    IF v_has_hotel_id = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'inventario_config_habitacion.hotel_id no existe; no se puede crear unico por hotel';
    END IF;

    SELECT COUNT(*)
    INTO v_has_tipo_habitacion
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'inventario_config_habitacion'
      AND COLUMN_NAME = 'tipo_habitacion';

    IF v_has_tipo_habitacion = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'inventario_config_habitacion.tipo_habitacion no existe; no se puede crear unico por hotel';
    END IF;

    SELECT COUNT(*)
    INTO v_has_producto_id
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'inventario_config_habitacion'
      AND COLUMN_NAME = 'producto_id';

    IF v_has_producto_id = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'inventario_config_habitacion.producto_id no existe; no se puede crear unico por hotel';
    END IF;

    SELECT COUNT(*)
    INTO v_hotel_id_nulls
    FROM inventario_config_habitacion
    WHERE hotel_id IS NULL;

    IF v_hotel_id_nulls > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Existen configuraciones con hotel_id NULL; corregir antes de cambiar el indice unico';
    END IF;

    SELECT COUNT(*)
    INTO v_duplicados_por_hotel
    FROM (
        SELECT hotel_id, tipo_habitacion, producto_id, COUNT(*) AS total
        FROM inventario_config_habitacion
        GROUP BY hotel_id, tipo_habitacion, producto_id
        HAVING COUNT(*) > 1
    ) AS d;

    IF v_duplicados_por_hotel > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Existen configuraciones duplicadas dentro del mismo hotel';
    END IF;

    SELECT COUNT(DISTINCT INDEX_NAME)
    INTO v_named_index_exists
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'inventario_config_habitacion'
      AND INDEX_NAME = 'uk_inventario_config_hotel_tipo_producto';

    SELECT COUNT(*)
    INTO v_has_composite_unique
    FROM (
        SELECT INDEX_NAME,
               COUNT(*) AS total_columnas,
               SUM(CASE WHEN COLUMN_NAME = 'hotel_id' AND SEQ_IN_INDEX = 1 THEN 1 ELSE 0 END) AS hotel_id_primero,
               SUM(CASE WHEN COLUMN_NAME = 'tipo_habitacion' AND SEQ_IN_INDEX = 2 THEN 1 ELSE 0 END) AS tipo_segundo,
               SUM(CASE WHEN COLUMN_NAME = 'producto_id' AND SEQ_IN_INDEX = 3 THEN 1 ELSE 0 END) AS producto_tercero
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'inventario_config_habitacion'
          AND NON_UNIQUE = 0
        GROUP BY INDEX_NAME
        HAVING total_columnas = 3
           AND hotel_id_primero = 1
           AND tipo_segundo = 1
           AND producto_tercero = 1
    ) AS idx;

    IF v_named_index_exists > 0 AND v_has_composite_unique = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Existe uk_inventario_config_hotel_tipo_producto con columnas inesperadas';
    END IF;

    IF v_has_composite_unique = 0 THEN
        ALTER TABLE inventario_config_habitacion
            ADD UNIQUE KEY uk_inventario_config_hotel_tipo_producto (hotel_id, tipo_habitacion, producto_id);
    END IF;

    SELECT INDEX_NAME
    INTO v_global_unique_tipo_producto_index
    FROM (
        SELECT INDEX_NAME,
               COUNT(*) AS total_columnas,
               SUM(CASE WHEN COLUMN_NAME = 'tipo_habitacion' AND SEQ_IN_INDEX = 1 THEN 1 ELSE 0 END) AS tipo_primero,
               SUM(CASE WHEN COLUMN_NAME = 'producto_id' AND SEQ_IN_INDEX = 2 THEN 1 ELSE 0 END) AS producto_segundo
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'inventario_config_habitacion'
          AND NON_UNIQUE = 0
          AND INDEX_NAME <> 'PRIMARY'
        GROUP BY INDEX_NAME
        HAVING total_columnas = 2
           AND tipo_primero = 1
           AND producto_segundo = 1
        LIMIT 1
    ) AS idx;

    IF v_global_unique_tipo_producto_index IS NOT NULL THEN
        SET @drop_global_tipo_producto_sql := CONCAT(
            'ALTER TABLE inventario_config_habitacion DROP INDEX `',
            REPLACE(v_global_unique_tipo_producto_index, '`', '``'),
            '`'
        );
        PREPARE stmt FROM @drop_global_tipo_producto_sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;

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
END$$

CALL migrar_inventario_config_habitacion_unico_por_hotel()$$

DROP PROCEDURE IF EXISTS migrar_inventario_config_habitacion_unico_por_hotel$$

DELIMITER ;

-- Rollback manual:
-- 1) Confirmar que no existan configuraciones repetidas globalmente:
--    SELECT tipo_habitacion, producto_id, COUNT(*)
--    FROM inventario_config_habitacion
--    GROUP BY tipo_habitacion, producto_id
--    HAVING COUNT(*) > 1;
-- 2) Si el resultado es vacio:
--    ALTER TABLE inventario_config_habitacion
--        ADD UNIQUE KEY uk_tipo_producto (tipo_habitacion, producto_id);
--    ALTER TABLE inventario_config_habitacion
--        DROP INDEX uk_inventario_config_hotel_tipo_producto;
-- 3) Quitar registro de migracion:
--    DELETE FROM migrations WHERE nombre = '20260624_003_inventario_config_habitacion_unico_por_hotel.sql';
