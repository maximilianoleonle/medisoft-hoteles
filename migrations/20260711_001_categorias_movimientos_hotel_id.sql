-- Aislar catalogo de categorias de movimientos de caja por hotel.
-- Antes: categorias_movimientos era GLOBAL (sin hotel_id); un hotel podia
-- ver/renombrar/desactivar/reordenar las categorias que otro usaba. Este es el
-- hallazgo MEDIO de la auditoria multi-tenant 2026-07-11.
--
-- Backfill set-based (sin cursores):
--  A) Cada categoria se asigna a su hotel "dueno" = el que mas movimientos tiene
--     con ella (desempate: menor hotel_id). Las sin uso -> hotel base los-cedros.
--  B) Para cada (hotel, categoria) usado en movimientos_caja donde el dueno de la
--     categoria es OTRO hotel, se crea una copia de la categoria para ese hotel y
--     se reapuntan sus movimientos a la copia. Asi cada movimiento referencia una
--     categoria de su propio hotel (integridad relacional). El historico visible
--     no cambia: movimientos_caja.categoria guarda el nombre denormalizado.
--
-- No toca el flujo de cobro de hospedaje/anticipo (esos movimientos usan el
-- nombre como texto, no el catalogo). No pone UNIQUE(hotel_id,nombre) porque el
-- dato historico tiene duplicados legitimos ("Devoluciones"); la unicidad la
-- valida el modelo por hotel.
--
-- IMPORTANTE: ALTER TABLE hace commit implicito en MySQL; no depender de
-- START TRANSACTION para revertir. Rollback manual: quitar FK/indice/columna
-- hotel_id de categorias_movimientos (los movimientos reapuntados no se revierten
-- pero siguen siendo validos porque las copias son equivalentes).

DROP PROCEDURE IF EXISTS migrar_categorias_movimientos_hotel_id;

DELIMITER $$

CREATE PROCEDURE migrar_categorias_movimientos_hotel_id()
BEGIN
    DECLARE v_los_cedros_id INT DEFAULT NULL;

    -- Precondicion 1: hotel base para el fallback del backfill.
    SELECT id INTO v_los_cedros_id
    FROM hoteles WHERE slug = 'los-cedros' LIMIT 1;

    IF v_los_cedros_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'categorias_movimientos hotel_id detenida: no existe hoteles.slug = los-cedros';
    END IF;

    -- Precondicion 2: la tabla debe existir.
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categorias_movimientos' LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'categorias_movimientos hotel_id detenida: no existe la tabla';
    END IF;

    -- Precondicion 3: idempotencia. Si ya tiene hotel_id, no re-ejecutar.
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'categorias_movimientos'
          AND COLUMN_NAME = 'hotel_id' LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'categorias_movimientos hotel_id detenida: la columna ya existe';
    END IF;

    ALTER TABLE categorias_movimientos
        ADD COLUMN hotel_id INT NULL AFTER id;

    -- Paso A: asignar cada categoria a su hotel de mayor uso; sin uso -> los-cedros.
    UPDATE categorias_movimientos c
    SET c.hotel_id = COALESCE(
        (SELECT m.hotel_id
         FROM movimientos_caja m
         WHERE m.categoria_id = c.id AND m.hotel_id IS NOT NULL
         GROUP BY m.hotel_id
         ORDER BY COUNT(*) DESC, m.hotel_id ASC
         LIMIT 1),
        v_los_cedros_id
    )
    WHERE c.hotel_id IS NULL;

    -- Paso B.1: crear copias por-hotel para los usos cruzados (otro hotel usa una
    -- categoria cuyo dueno es distinto y aun no tiene una equivalente por nombre).
    INSERT INTO categorias_movimientos (hotel_id, nombre, tipo, descripcion, icono, color, activa, orden)
    SELECT DISTINCT m.hotel_id, c.nombre, c.tipo, c.descripcion, c.icono, c.color, 1, c.orden
    FROM movimientos_caja m
    JOIN categorias_movimientos c ON c.id = m.categoria_id
    WHERE m.categoria_id IS NOT NULL
      AND m.hotel_id IS NOT NULL
      AND c.hotel_id <> m.hotel_id
      AND NOT EXISTS (
          SELECT 1 FROM categorias_movimientos c2
          WHERE c2.hotel_id = m.hotel_id AND c2.nombre = c.nombre
      );

    -- Paso B.2: reapuntar los movimientos cruzados a la copia de su propio hotel.
    UPDATE movimientos_caja m
    JOIN categorias_movimientos c  ON c.id = m.categoria_id
    JOIN categorias_movimientos c2 ON c2.hotel_id = m.hotel_id AND c2.nombre = c.nombre
    SET m.categoria_id = c2.id
    WHERE m.categoria_id IS NOT NULL
      AND m.hotel_id IS NOT NULL
      AND c.hotel_id <> m.hotel_id;

    -- Verificacion 1: no debe quedar ninguna categoria sin hotel.
    IF EXISTS (SELECT 1 FROM categorias_movimientos WHERE hotel_id IS NULL LIMIT 1) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'categorias_movimientos hotel_id detenida: quedaron categorias con hotel_id NULL';
    END IF;

    -- Verificacion 2: ningun movimiento debe apuntar a una categoria de otro hotel.
    IF EXISTS (
        SELECT 1 FROM movimientos_caja m
        JOIN categorias_movimientos c ON c.id = m.categoria_id
        WHERE m.categoria_id IS NOT NULL
          AND m.hotel_id IS NOT NULL
          AND c.hotel_id <> m.hotel_id
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'categorias_movimientos hotel_id detenida: quedan movimientos apuntando a categoria de otro hotel';
    END IF;

    ALTER TABLE categorias_movimientos
        ADD INDEX idx_categorias_movimientos_hotel_id (hotel_id),
        ADD CONSTRAINT fk_categorias_movimientos_hotel
            FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT;

    INSERT INTO migrations (nombre, batch, checksum, estado)
    VALUES ('20260711_001_categorias_movimientos_hotel_id.sql', 1, NULL, 'ejecutada')
    ON DUPLICATE KEY UPDATE
        estado = VALUES(estado),
        ejecutada_en = CURRENT_TIMESTAMP;
END$$

CALL migrar_categorias_movimientos_hotel_id()$$

DROP PROCEDURE IF EXISTS migrar_categorias_movimientos_hotel_id$$

DELIMITER ;
