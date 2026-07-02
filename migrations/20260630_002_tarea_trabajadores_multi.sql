-- Fase TLM: asignacion multiple de trabajadores por tarea operativa
-- Migracion ADITIVA, idempotente y multi-hotel.
--
-- Alcance:
--   - Crea la tabla pivote tarea_trabajadores (tarea <-> trabajador, muchos a muchos).
--   - Respalda (backfill) las asignaciones 1-a-1 existentes desde tareas_operativas.trabajador_id.
--   - NO elimina ni altera tareas_operativas.trabajador_id (se conserva como "trabajador lider").
--   - NO toca habitaciones.estado, mantenimientos, Caja, pagos, abonos, nomina ni /api/sync.
--
-- Modelo hibrido:
--   tareas_operativas.trabajador_id  = trabajador lider (primero de la lista) -> toda la capa
--                                      de lectura existente (agenda, reportes, index) sigue igual.
--   tarea_trabajadores               = conjunto completo de trabajadores asignados.
--
-- Rollback manual seguro (solo con autorizacion explicita):
--   DROP TABLE tarea_trabajadores;
--   DELETE FROM migrations WHERE nombre = '20260630_002_tarea_trabajadores_multi.sql';

SET @migration_name := '20260630_002_tarea_trabajadores_multi.sql';

START TRANSACTION;

CREATE TABLE IF NOT EXISTS tarea_trabajadores (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    tarea_id INT NOT NULL,
    trabajador_id INT NOT NULL,
    asignado_por_usuario_id INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tarea_trabajador (tarea_id, trabajador_id),
    KEY idx_tt_hotel_tarea (hotel_id, tarea_id),
    KEY idx_tt_hotel_trabajador (hotel_id, trabajador_id),
    KEY idx_tt_asignado_por (asignado_por_usuario_id),
    CONSTRAINT fk_tt_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_tt_tarea
        FOREIGN KEY (tarea_id) REFERENCES tareas_operativas (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_tt_trabajador
        FOREIGN KEY (trabajador_id) REFERENCES trabajadores (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_tt_asignado_por
        FOREIGN KEY (asignado_por_usuario_id) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backfill: trae las asignaciones 1-a-1 existentes al pivote (no duplica gracias a INSERT IGNORE).
INSERT IGNORE INTO tarea_trabajadores (hotel_id, tarea_id, trabajador_id, asignado_por_usuario_id, created_at)
SELECT t.hotel_id,
       t.id,
       t.trabajador_id,
       t.asignada_por_usuario_id,
       COALESCE(t.updated_at, t.created_at, NOW())
FROM tareas_operativas t
WHERE t.trabajador_id IS NOT NULL;

SET @tt_columns := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tarea_trabajadores'
      AND COLUMN_NAME IN (
          'id', 'hotel_id', 'tarea_id', 'trabajador_id',
          'asignado_por_usuario_id', 'created_at'
      )
);

SET @validation_sql := IF(
    @tt_columns = 6,
    'SELECT ''OK: estructura tarea_trabajadores verificada'' AS resultado',
    'SELECT no_existe_columna_tarea_trabajadores'
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
