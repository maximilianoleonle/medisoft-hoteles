-- Agregar hotel_id a remember_tokens para que el auto-login "recordarme"
-- restaure el MISMO hotel con el que se inicio sesion (evita el cruce a otro hotel).
-- Alcance:
-- - remember_tokens (solo agrega columna nullable + FK ON DELETE SET NULL).
-- - Aditiva: tokens existentes quedan con hotel_id NULL (comportamiento previo, sin hotel).
-- - No toca auth de usuarios, caja, PWA/offline, /api/sync ni reservaciones.

SET @migration_name := '20260626_001_remember_tokens_hotel_id.sql';

DROP PROCEDURE IF EXISTS migrar_remember_tokens_hotel_id;

DELIMITER $$

CREATE PROCEDURE migrar_remember_tokens_hotel_id()
BEGIN
    DECLARE v_has_table INT DEFAULT 0;
    DECLARE v_has_hotel_id INT DEFAULT 0;
    DECLARE v_has_index INT DEFAULT 0;
    DECLARE v_has_fk INT DEFAULT 0;

    SELECT COUNT(*)
    INTO v_has_table
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'remember_tokens';

    IF v_has_table = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'remember_tokens no existe; no se puede agregar hotel_id';
    END IF;

    SELECT COUNT(*)
    INTO v_has_hotel_id
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'remember_tokens'
      AND COLUMN_NAME = 'hotel_id';

    IF v_has_hotel_id = 0 THEN
        ALTER TABLE remember_tokens
            ADD COLUMN hotel_id INT NULL AFTER user_id;
    END IF;

    SELECT COUNT(DISTINCT INDEX_NAME)
    INTO v_has_index
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'remember_tokens'
      AND INDEX_NAME = 'idx_remember_tokens_hotel';

    IF v_has_index = 0 THEN
        ALTER TABLE remember_tokens
            ADD INDEX idx_remember_tokens_hotel (hotel_id);
    END IF;

    SELECT COUNT(*)
    INTO v_has_fk
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'remember_tokens'
      AND CONSTRAINT_NAME = 'fk_remember_tokens_hotel'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY';

    IF v_has_fk = 0 THEN
        ALTER TABLE remember_tokens
            ADD CONSTRAINT fk_remember_tokens_hotel
                FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
                ON UPDATE CASCADE
                ON DELETE SET NULL;
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

CALL migrar_remember_tokens_hotel_id()$$

DROP PROCEDURE IF EXISTS migrar_remember_tokens_hotel_id$$

DELIMITER ;

-- Rollback manual:
-- ALTER TABLE remember_tokens DROP FOREIGN KEY fk_remember_tokens_hotel;
-- ALTER TABLE remember_tokens DROP INDEX idx_remember_tokens_hotel;
-- ALTER TABLE remember_tokens DROP COLUMN hotel_id;
-- DELETE FROM migrations WHERE nombre = '20260626_001_remember_tokens_hotel_id.sql';
