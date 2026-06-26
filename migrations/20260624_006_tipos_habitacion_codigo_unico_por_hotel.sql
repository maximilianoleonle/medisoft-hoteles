-- Permitir codigos de tipo de habitacion repetidos entre hoteles.
-- Alcance:
-- - tipos_habitacion
-- - Convierte el indice unico global de codigo a indice unico por hotel.
-- - No toca habitaciones.tipo, reservaciones, caja, PWA/offline ni /api/sync.

SET @migration_name := '20260624_006_tipos_habitacion_codigo_unico_por_hotel.sql';

DROP PROCEDURE IF EXISTS migrar_tipos_habitacion_codigo_unico_por_hotel;

DELIMITER $$

CREATE PROCEDURE migrar_tipos_habitacion_codigo_unico_por_hotel()
BEGIN
    DECLARE v_has_table INT DEFAULT 0;
    DECLARE v_has_hotel_id INT DEFAULT 0;
    DECLARE v_has_codigo INT DEFAULT 0;
    DECLARE v_invalid_hotel_ids INT DEFAULT 0;
    DECLARE v_duplicados_por_scope INT DEFAULT 0;
    DECLARE v_has_scoped_unique INT DEFAULT 0;
    DECLARE v_global_unique_codigo_index VARCHAR(128) DEFAULT NULL;

    SELECT COUNT(*)
    INTO v_has_table
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tipos_habitacion';

    IF v_has_table = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'tipos_habitacion no existe; no se puede crear unico por hotel';
    END IF;

    SELECT COUNT(*)
    INTO v_has_hotel_id
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tipos_habitacion'
      AND COLUMN_NAME = 'hotel_id';

    IF v_has_hotel_id = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'tipos_habitacion.hotel_id no existe; no se puede crear unico por hotel';
    END IF;

    SELECT COUNT(*)
    INTO v_has_codigo
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tipos_habitacion'
      AND COLUMN_NAME = 'codigo';

    IF v_has_codigo = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'tipos_habitacion.codigo no existe; no se puede crear unico por hotel';
    END IF;

    SELECT COUNT(*)
    INTO v_invalid_hotel_ids
    FROM tipos_habitacion th
    LEFT JOIN hoteles h
        ON h.id = th.hotel_id
    WHERE th.hotel_id IS NOT NULL
      AND h.id IS NULL;

    IF v_invalid_hotel_ids > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Existen tipos_habitacion con hotel_id invalido';
    END IF;

    SELECT COUNT(*)
    INTO v_duplicados_por_scope
    FROM (
        SELECT COALESCE(hotel_id, 0) AS hotel_scope, codigo, COUNT(*) AS total
        FROM tipos_habitacion
        GROUP BY COALESCE(hotel_id, 0), codigo
        HAVING COUNT(*) > 1
    ) AS d;

    IF v_duplicados_por_scope > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Existen codigos de tipo duplicados dentro del mismo hotel o scope global';
    END IF;

    SELECT COUNT(DISTINCT INDEX_NAME)
    INTO v_has_scoped_unique
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tipos_habitacion'
      AND INDEX_NAME = 'uk_tipos_habitacion_hotel_codigo';

    IF v_has_scoped_unique = 0 THEN
        ALTER TABLE tipos_habitacion
            ADD UNIQUE KEY uk_tipos_habitacion_hotel_codigo ((COALESCE(hotel_id, 0)), codigo);
    END IF;

    SELECT INDEX_NAME
    INTO v_global_unique_codigo_index
    FROM (
        SELECT INDEX_NAME,
               COUNT(*) AS total_columnas,
               SUM(CASE WHEN COLUMN_NAME = 'codigo' THEN 1 ELSE 0 END) AS columnas_codigo
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'tipos_habitacion'
          AND NON_UNIQUE = 0
          AND INDEX_NAME <> 'PRIMARY'
          AND INDEX_NAME <> 'uk_tipos_habitacion_hotel_codigo'
        GROUP BY INDEX_NAME
        HAVING total_columnas = 1
           AND columnas_codigo = 1
        LIMIT 1
    ) AS idx;

    IF v_global_unique_codigo_index IS NOT NULL THEN
        SET @drop_global_codigo_sql := CONCAT(
            'ALTER TABLE tipos_habitacion DROP INDEX `',
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

CALL migrar_tipos_habitacion_codigo_unico_por_hotel()$$

DROP PROCEDURE IF EXISTS migrar_tipos_habitacion_codigo_unico_por_hotel$$

DELIMITER ;

-- Rollback manual:
-- 1) Confirmar que no existan codigos repetidos globalmente:
--    SELECT codigo, COUNT(*) FROM tipos_habitacion GROUP BY codigo HAVING COUNT(*) > 1;
-- 2) Si el resultado es vacio:
--    ALTER TABLE tipos_habitacion ADD UNIQUE KEY uk_codigo (codigo);
--    ALTER TABLE tipos_habitacion DROP INDEX uk_tipos_habitacion_hotel_codigo;
-- 3) Quitar registro de migracion:
--    DELETE FROM migrations WHERE nombre = '20260624_006_tipos_habitacion_codigo_unico_por_hotel.sql';
