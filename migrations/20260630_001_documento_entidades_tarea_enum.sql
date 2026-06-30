-- BUG-016 - Permitir vinculacion documental con tareas operativas.
--
-- Alcance:
-- - Agrega `tarea` al ENUM documento_entidades.entidad_tipo.
-- - No modifica documentos existentes, archivos, rutas ni modelos.
-- - Mantiene indices y FKs actuales.
--
-- Rollback manual, solo si no existen vinculos con entidad_tipo = 'tarea':
-- ALTER TABLE documento_entidades
--   MODIFY entidad_tipo ENUM('proveedor','compra','cuenta_por_pagar','huesped','reservacion','trabajador')
--   CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
-- DELETE FROM migrations WHERE nombre = '20260630_001_documento_entidades_tarea_enum.sql';

SET @migration_name := '20260630_001_documento_entidades_tarea_enum.sql';

DROP PROCEDURE IF EXISTS migrar_documento_entidades_tarea_enum;

DELIMITER $$

CREATE PROCEDURE migrar_documento_entidades_tarea_enum()
BEGIN
    DECLARE v_has_table INT DEFAULT 0;
    DECLARE v_has_column INT DEFAULT 0;
    DECLARE v_has_tarea INT DEFAULT 0;

    SELECT COUNT(*)
    INTO v_has_table
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'documento_entidades';

    SELECT COUNT(*)
    INTO v_has_column
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'documento_entidades'
      AND COLUMN_NAME = 'entidad_tipo';

    IF v_has_table = 0 OR v_has_column = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Falta documento_entidades.entidad_tipo para aplicar BUG-016';
    END IF;

    SELECT COUNT(*)
    INTO v_has_tarea
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'documento_entidades'
      AND COLUMN_NAME = 'entidad_tipo'
      AND COLUMN_TYPE LIKE '%''tarea''%';

    IF v_has_tarea = 0 THEN
        ALTER TABLE documento_entidades
            MODIFY entidad_tipo ENUM(
                'proveedor',
                'compra',
                'cuenta_por_pagar',
                'huesped',
                'reservacion',
                'trabajador',
                'tarea'
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
    END IF;

    SELECT COUNT(*)
    INTO v_has_tarea
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'documento_entidades'
      AND COLUMN_NAME = 'entidad_tipo'
      AND COLUMN_TYPE LIKE '%''tarea''%';

    IF v_has_tarea = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No se pudo agregar tarea al ENUM documento_entidades.entidad_tipo';
    END IF;

    INSERT INTO migrations (nombre, batch, checksum, estado, ejecutada_en)
    SELECT
        @migration_name,
        COALESCE(MAX(batch), 0) + 1,
        SHA2(CONCAT(@migration_name, '|v1'), 256),
        'ejecutada',
        NOW()
    FROM migrations
    ON DUPLICATE KEY UPDATE
        checksum = VALUES(checksum),
        estado = VALUES(estado),
        ejecutada_en = CURRENT_TIMESTAMP;
END$$

CALL migrar_documento_entidades_tarea_enum()$$

DROP PROCEDURE IF EXISTS migrar_documento_entidades_tarea_enum$$

DELIMITER ;
