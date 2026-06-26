-- Permitir codigos de productos repetidos entre hoteles.
-- Alcance:
-- - inventario_productos
-- - Convierte el indice unico global de codigo a indice unico por hotel.
-- - No toca movimientos, reservaciones, check-in/check-out, caja, PWA/offline ni /api/sync.

SET @migration_name := '20260624_002_inventario_productos_codigo_unico_por_hotel.sql';

DROP PROCEDURE IF EXISTS migrar_inventario_productos_codigo_unico_por_hotel;

DELIMITER $$

CREATE PROCEDURE migrar_inventario_productos_codigo_unico_por_hotel()
BEGIN
    DECLARE v_has_table INT DEFAULT 0;
    DECLARE v_has_hotel_id INT DEFAULT 0;
    DECLARE v_has_codigo INT DEFAULT 0;
    DECLARE v_hotel_id_nulls INT DEFAULT 0;
    DECLARE v_duplicados_por_hotel INT DEFAULT 0;
    DECLARE v_named_index_exists INT DEFAULT 0;
    DECLARE v_has_composite_unique INT DEFAULT 0;
    DECLARE v_global_unique_codigo_index VARCHAR(128) DEFAULT NULL;

    SELECT COUNT(*)
    INTO v_has_table
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'inventario_productos';

    IF v_has_table = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'inventario_productos no existe; no se puede crear unico por hotel';
    END IF;

    SELECT COUNT(*)
    INTO v_has_hotel_id
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'inventario_productos'
      AND COLUMN_NAME = 'hotel_id';

    IF v_has_hotel_id = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'inventario_productos.hotel_id no existe; no se puede crear unico por hotel';
    END IF;

    SELECT COUNT(*)
    INTO v_has_codigo
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'inventario_productos'
      AND COLUMN_NAME = 'codigo';

    IF v_has_codigo = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'inventario_productos.codigo no existe; no se puede crear unico por hotel';
    END IF;

    SELECT COUNT(*)
    INTO v_hotel_id_nulls
    FROM inventario_productos
    WHERE hotel_id IS NULL;

    IF v_hotel_id_nulls > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Existen productos con hotel_id NULL; corregir antes de cambiar el indice unico';
    END IF;

    SELECT COUNT(*)
    INTO v_duplicados_por_hotel
    FROM (
        SELECT hotel_id, codigo, COUNT(*) AS total
        FROM inventario_productos
        GROUP BY hotel_id, codigo
        HAVING COUNT(*) > 1
    ) AS d;

    IF v_duplicados_por_hotel > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Existen codigos de producto duplicados dentro del mismo hotel';
    END IF;

    SELECT COUNT(DISTINCT INDEX_NAME)
    INTO v_named_index_exists
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'inventario_productos'
      AND INDEX_NAME = 'uk_inventario_productos_hotel_codigo';

    SELECT COUNT(*)
    INTO v_has_composite_unique
    FROM (
        SELECT INDEX_NAME,
               COUNT(*) AS total_columnas,
               SUM(CASE WHEN COLUMN_NAME = 'hotel_id' AND SEQ_IN_INDEX = 1 THEN 1 ELSE 0 END) AS hotel_id_primero,
               SUM(CASE WHEN COLUMN_NAME = 'codigo' AND SEQ_IN_INDEX = 2 THEN 1 ELSE 0 END) AS codigo_segundo
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'inventario_productos'
          AND NON_UNIQUE = 0
        GROUP BY INDEX_NAME
        HAVING total_columnas = 2
           AND hotel_id_primero = 1
           AND codigo_segundo = 1
    ) AS idx;

    IF v_named_index_exists > 0 AND v_has_composite_unique = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Existe uk_inventario_productos_hotel_codigo con columnas inesperadas';
    END IF;

    IF v_has_composite_unique = 0 THEN
        ALTER TABLE inventario_productos
            ADD UNIQUE KEY uk_inventario_productos_hotel_codigo (hotel_id, codigo);
    END IF;

    SELECT INDEX_NAME
    INTO v_global_unique_codigo_index
    FROM (
        SELECT INDEX_NAME,
               COUNT(*) AS total_columnas,
               SUM(CASE WHEN COLUMN_NAME = 'codigo' THEN 1 ELSE 0 END) AS columnas_codigo
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'inventario_productos'
          AND NON_UNIQUE = 0
          AND INDEX_NAME <> 'PRIMARY'
        GROUP BY INDEX_NAME
        HAVING total_columnas = 1
           AND columnas_codigo = 1
        LIMIT 1
    ) AS idx;

    IF v_global_unique_codigo_index IS NOT NULL THEN
        SET @drop_global_codigo_sql := CONCAT(
            'ALTER TABLE inventario_productos DROP INDEX `',
            REPLACE(v_global_unique_codigo_index, '`', '``'),
            '`'
        );
        PREPARE stmt FROM @drop_global_codigo_sql;
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

CALL migrar_inventario_productos_codigo_unico_por_hotel()$$

DROP PROCEDURE IF EXISTS migrar_inventario_productos_codigo_unico_por_hotel$$

DELIMITER ;

-- Rollback manual:
-- 1) Confirmar que no existan codigos repetidos globalmente:
--    SELECT codigo, COUNT(*) FROM inventario_productos GROUP BY codigo HAVING COUNT(*) > 1;
-- 2) Si el resultado es vacio:
--    ALTER TABLE inventario_productos ADD UNIQUE KEY codigo (codigo);
--    ALTER TABLE inventario_productos DROP INDEX uk_inventario_productos_hotel_codigo;
-- 3) Quitar registro de migracion:
--    DELETE FROM migrations WHERE nombre = '20260624_002_inventario_productos_codigo_unico_por_hotel.sql';
