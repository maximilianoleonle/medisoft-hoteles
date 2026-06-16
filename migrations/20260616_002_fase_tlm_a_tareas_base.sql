-- Fase TLM-A: Tareas operativas base
-- Migracion aditiva, idempotente y multi-hotel.
--
-- Backup previo local valido:
--   src/storage/backups/phase_tlm_a_20260616_030648_before_tasks_base_medisoft_hoteles_import.sql
--   bytes: 3192456
--   sha256: C76243D8AF98A2D9AA9D94FB5B1BB4D29369A861594141C7EC397A13AF3CF242
--
-- Alcance:
--   - Crea tablas base para tareas operativas y eventos de tarea.
--   - No inserta tareas ni eventos.
--   - No toca habitaciones.estado.
--   - No toca mantenimientos_habitaciones.
--   - No toca Caja, pagos, abonos, nomina ni /api/sync.
--
-- Rollback manual seguro:
--   Ejecutar solo con autorizacion explicita y si TODAS las tablas estan vacias.
--   SELECT COUNT(*) FROM tarea_eventos;
--   SELECT COUNT(*) FROM tareas_operativas;
--   DROP TABLE tarea_eventos;
--   DROP TABLE tareas_operativas;
--   DELETE FROM migrations WHERE nombre = '20260616_002_fase_tlm_a_tareas_base.sql';

SET @migration_name := '20260616_002_fase_tlm_a_tareas_base.sql';

START TRANSACTION;

CREATE TABLE IF NOT EXISTS tareas_operativas (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    categoria ENUM('limpieza', 'mantenimiento', 'general') NOT NULL DEFAULT 'general',
    titulo VARCHAR(160) NOT NULL,
    descripcion TEXT NULL,
    prioridad ENUM('baja', 'media', 'alta', 'urgente') NOT NULL DEFAULT 'media',
    estado ENUM('pendiente', 'asignada', 'en_proceso', 'completada', 'cancelada') NOT NULL DEFAULT 'pendiente',
    habitacion_id INT NULL,
    reservacion_id INT NULL,
    huesped_id INT NULL,
    trabajador_id INT NULL,
    mantenimiento_id INT NULL,
    fecha_programada DATETIME NULL,
    fecha_limite DATETIME NULL,
    fecha_inicio DATETIME NULL,
    fecha_cierre DATETIME NULL,
    creada_por_usuario_id INT NULL,
    asignada_por_usuario_id INT NULL,
    cerrada_por_usuario_id INT NULL,
    cancelada_por_usuario_id INT NULL,
    origen ENUM('manual', 'habitacion', 'mantenimiento', 'sistema') NOT NULL DEFAULT 'manual',
    notas_cierre TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tareas_hotel_estado (hotel_id, estado),
    KEY idx_tareas_hotel_categoria (hotel_id, categoria),
    KEY idx_tareas_hotel_prioridad (hotel_id, prioridad),
    KEY idx_tareas_hotel_fecha_programada (hotel_id, fecha_programada),
    KEY idx_tareas_habitacion (habitacion_id),
    KEY idx_tareas_reservacion (reservacion_id),
    KEY idx_tareas_huesped (huesped_id),
    KEY idx_tareas_trabajador (trabajador_id),
    KEY idx_tareas_mantenimiento (mantenimiento_id),
    KEY idx_tareas_creada_por (creada_por_usuario_id),
    KEY idx_tareas_asignada_por (asignada_por_usuario_id),
    KEY idx_tareas_cerrada_por (cerrada_por_usuario_id),
    KEY idx_tareas_cancelada_por (cancelada_por_usuario_id),
    CONSTRAINT fk_tareas_operativas_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_tareas_operativas_habitacion
        FOREIGN KEY (habitacion_id) REFERENCES habitaciones (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_tareas_operativas_reservacion
        FOREIGN KEY (reservacion_id) REFERENCES reservaciones (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_tareas_operativas_huesped
        FOREIGN KEY (huesped_id) REFERENCES huespedes (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_tareas_operativas_trabajador
        FOREIGN KEY (trabajador_id) REFERENCES trabajadores (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_tareas_operativas_mantenimiento
        FOREIGN KEY (mantenimiento_id) REFERENCES mantenimientos_habitaciones (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_tareas_operativas_creada_por
        FOREIGN KEY (creada_por_usuario_id) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_tareas_operativas_asignada_por
        FOREIGN KEY (asignada_por_usuario_id) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_tareas_operativas_cerrada_por
        FOREIGN KEY (cerrada_por_usuario_id) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_tareas_operativas_cancelada_por
        FOREIGN KEY (cancelada_por_usuario_id) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tarea_eventos (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    tarea_id INT NOT NULL,
    tipo_evento ENUM('creada', 'actualizada', 'asignada', 'iniciada', 'completada', 'cancelada', 'comentario', 'sistema') NOT NULL,
    estado_anterior ENUM('pendiente', 'asignada', 'en_proceso', 'completada', 'cancelada') NULL,
    estado_nuevo ENUM('pendiente', 'asignada', 'en_proceso', 'completada', 'cancelada') NULL,
    comentario TEXT NULL,
    usuario_id INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tarea_eventos_hotel_tarea (hotel_id, tarea_id),
    KEY idx_tarea_eventos_tipo (hotel_id, tipo_evento),
    KEY idx_tarea_eventos_usuario (usuario_id),
    CONSTRAINT fk_tarea_eventos_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_tarea_eventos_tarea
        FOREIGN KEY (tarea_id) REFERENCES tareas_operativas (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_tarea_eventos_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @tareas_columns := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tareas_operativas'
      AND COLUMN_NAME IN (
          'id', 'hotel_id', 'categoria', 'titulo', 'descripcion', 'prioridad',
          'estado', 'habitacion_id', 'reservacion_id', 'huesped_id',
          'trabajador_id', 'mantenimiento_id', 'fecha_programada', 'fecha_limite',
          'fecha_inicio', 'fecha_cierre', 'creada_por_usuario_id',
          'asignada_por_usuario_id', 'cerrada_por_usuario_id',
          'cancelada_por_usuario_id', 'origen', 'notas_cierre',
          'created_at', 'updated_at'
      )
);

SET @tarea_eventos_columns := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tarea_eventos'
      AND COLUMN_NAME IN (
          'id', 'hotel_id', 'tarea_id', 'tipo_evento', 'estado_anterior',
          'estado_nuevo', 'comentario', 'usuario_id', 'created_at'
      )
);

SET @validation_sql := IF(
    @tareas_columns = 24
    AND @tarea_eventos_columns = 9,
    'SELECT ''OK: estructura TLM-A verificada'' AS resultado',
    'SELECT no_existe_columna_tlm_a'
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
