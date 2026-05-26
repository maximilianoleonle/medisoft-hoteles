-- Fase 2A.1 - Schema/backfill hotel_id para modulo Habitaciones
-- Alcance permitido:
-- - tipos_habitacion
-- - habitaciones
-- - habitacion_imagenes
-- - mantenimientos_habitaciones
--
-- IMPORTANTE:
-- MySQL ejecuta commits implicitos con ALTER TABLE. No depender de
-- START TRANSACTION para revertir esta migracion. Usar el rollback
-- documentado en docs/fase_2A_1_hotel_id_habitaciones.md.
--
-- Esta migracion no cambia indices unicos globales, no modifica codigo
-- funcional, no toca reservaciones, caja, login/sesiones ni PWA/offline.

DROP PROCEDURE IF EXISTS validar_fase_2a1_precondiciones;

DELIMITER //

CREATE PROCEDURE validar_fase_2a1_precondiciones()
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM hoteles
        WHERE slug = 'los-cedros'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Fase 2A.1 detenida: no existe hoteles.slug = los-cedros';
    END IF;

    IF EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME IN (
              'tipos_habitacion',
              'habitaciones',
              'habitacion_imagenes',
              'mantenimientos_habitaciones'
          )
          AND COLUMN_NAME = 'hotel_id'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Fase 2A.1 detenida: alguna tabla de Habitaciones ya tiene hotel_id';
    END IF;
END//

DELIMITER ;

CALL validar_fase_2a1_precondiciones();
DROP PROCEDURE IF EXISTS validar_fase_2a1_precondiciones;

SET @los_cedros_id := (
    SELECT id
    FROM hoteles
    WHERE slug = 'los-cedros'
    LIMIT 1
);

ALTER TABLE tipos_habitacion
    ADD COLUMN hotel_id INT NULL AFTER id;

ALTER TABLE habitaciones
    ADD COLUMN hotel_id INT NULL AFTER id;

ALTER TABLE habitacion_imagenes
    ADD COLUMN hotel_id INT NULL AFTER id;

ALTER TABLE mantenimientos_habitaciones
    ADD COLUMN hotel_id INT NULL AFTER id;

UPDATE tipos_habitacion
SET hotel_id = @los_cedros_id
WHERE hotel_id IS NULL;

UPDATE habitaciones
SET hotel_id = @los_cedros_id
WHERE hotel_id IS NULL;

UPDATE habitacion_imagenes
SET hotel_id = @los_cedros_id
WHERE hotel_id IS NULL;

UPDATE mantenimientos_habitaciones
SET hotel_id = @los_cedros_id
WHERE hotel_id IS NULL;

DROP PROCEDURE IF EXISTS validar_fase_2a1_backfill;

DELIMITER //

CREATE PROCEDURE validar_fase_2a1_backfill()
BEGIN
    IF EXISTS (SELECT 1 FROM tipos_habitacion WHERE hotel_id IS NULL LIMIT 1) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Fase 2A.1 detenida: tipos_habitacion tiene hotel_id NULL despues del backfill';
    END IF;

    IF EXISTS (SELECT 1 FROM habitaciones WHERE hotel_id IS NULL LIMIT 1) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Fase 2A.1 detenida: habitaciones tiene hotel_id NULL despues del backfill';
    END IF;

    IF EXISTS (SELECT 1 FROM habitacion_imagenes WHERE hotel_id IS NULL LIMIT 1) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Fase 2A.1 detenida: habitacion_imagenes tiene hotel_id NULL despues del backfill';
    END IF;

    IF EXISTS (SELECT 1 FROM mantenimientos_habitaciones WHERE hotel_id IS NULL LIMIT 1) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Fase 2A.1 detenida: mantenimientos_habitaciones tiene hotel_id NULL despues del backfill';
    END IF;
END//

DELIMITER ;

CALL validar_fase_2a1_backfill();
DROP PROCEDURE IF EXISTS validar_fase_2a1_backfill;

ALTER TABLE tipos_habitacion
    ADD INDEX idx_tipos_habitacion_hotel_id (hotel_id),
    ADD CONSTRAINT fk_tipos_habitacion_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT;

ALTER TABLE habitaciones
    ADD INDEX idx_habitaciones_hotel_id (hotel_id),
    ADD CONSTRAINT fk_habitaciones_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT;

ALTER TABLE habitacion_imagenes
    ADD INDEX idx_habitacion_imagenes_hotel_id (hotel_id),
    ADD CONSTRAINT fk_habitacion_imagenes_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT;

ALTER TABLE mantenimientos_habitaciones
    ADD INDEX idx_mantenimientos_habitaciones_hotel_id (hotel_id),
    ADD CONSTRAINT fk_mantenimientos_habitaciones_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260526_003_add_hotel_id_habitaciones.sql', 2, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;
