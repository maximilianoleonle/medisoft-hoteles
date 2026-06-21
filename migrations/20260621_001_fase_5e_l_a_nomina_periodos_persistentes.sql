-- Fase 5E-L-A - Cierre/aprobacion persistente de periodos de pre-nomina.
--
-- OBJETIVO:
-- - Crear snapshots persistentes de periodos de pre-nomina.
-- - Separar cierre/aprobacion administrativa de pagos reales con Caja.
-- - No crear nomina oficial, CFDI, timbrado, dispersion ni movimientos de Caja.
-- - No liquidar anticipos/prestamos automaticamente.
-- - No tocar PWA/offline, IndexedDB, cache names ni /api/sync.

SET @migration_name := '20260621_001_fase_5e_l_a_nomina_periodos_persistentes.sql';

CREATE TABLE IF NOT EXISTS trabajador_nomina_periodos (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    tipo_periodo ENUM('semanal', 'quincenal', 'mensual', 'manual') NOT NULL DEFAULT 'manual',
    etiqueta VARCHAR(120) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estado ENUM('cerrado', 'aprobado', 'anulado') NOT NULL DEFAULT 'cerrado',
    filtros_json JSON NULL,
    resumen_json JSON NULL,
    trabajadores_total INT NOT NULL DEFAULT 0,
    bruto_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    deducciones_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    pagos_caja_aplicados_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    reversiones_detectadas_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    neto_sugerido_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    pendiente_pago_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    cerrado_por INT NULL,
    cerrado_at DATETIME NOT NULL,
    aprobado_por INT NULL,
    aprobado_at DATETIME NULL,
    anulado_por INT NULL,
    anulado_at DATETIME NULL,
    motivo_anulacion VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_trabajador_nomina_periodo_hotel_rango (hotel_id, fecha_inicio, fecha_fin),
    KEY idx_trabajador_nomina_periodos_hotel_estado (hotel_id, estado),
    KEY idx_trabajador_nomina_periodos_hotel_fecha (hotel_id, fecha_inicio, fecha_fin),
    KEY idx_trabajador_nomina_periodos_cerrado_por (cerrado_por),
    KEY idx_trabajador_nomina_periodos_aprobado_por (aprobado_por),
    KEY idx_trabajador_nomina_periodos_anulado_por (anulado_por),
    CONSTRAINT fk_trabajador_nomina_periodos_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_nomina_periodos_cerrado_por
        FOREIGN KEY (cerrado_por) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_nomina_periodos_aprobado_por
        FOREIGN KEY (aprobado_por) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_nomina_periodos_anulado_por
        FOREIGN KEY (anulado_por) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_trabajador_nomina_periodos_rango
        CHECK (fecha_fin >= fecha_inicio),
    CONSTRAINT chk_trabajador_nomina_periodos_totales
        CHECK (
            trabajadores_total >= 0
            AND bruto_total >= 0
            AND deducciones_total >= 0
            AND pagos_caja_aplicados_total >= 0
            AND reversiones_detectadas_total >= 0
            AND pendiente_pago_total >= 0
        )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trabajador_nomina_periodo_detalles (
    id INT NOT NULL AUTO_INCREMENT,
    periodo_id INT NOT NULL,
    hotel_id INT NOT NULL,
    trabajador_id INT NOT NULL,
    trabajador_nombre VARCHAR(160) NOT NULL,
    trabajador_identificacion VARCHAR(80) NULL,
    trabajador_rol VARCHAR(100) NULL,
    trabajador_estado VARCHAR(30) NOT NULL,
    estado_preview_nomina VARCHAR(40) NOT NULL,
    motivo_bloqueo_nomina VARCHAR(255) NULL,
    conceptos_count INT NOT NULL DEFAULT 0,
    conceptos_a_favor DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    conceptos_en_contra DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    bruto_periodo DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    anticipos_count INT NOT NULL DEFAULT 0,
    anticipos_saldo DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    prestamos_count INT NOT NULL DEFAULT 0,
    prestamos_saldo DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    deducciones_informativas DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    pagos_caja_count INT NOT NULL DEFAULT 0,
    pagos_caja_pagados INT NOT NULL DEFAULT 0,
    pagos_caja_revertidos INT NOT NULL DEFAULT 0,
    pagos_caja_aplicados DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    pagos_caja_revertidos_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    reversiones_detectadas DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    ultimo_pago_caja DATETIME NULL,
    neto_sugerido DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    pendiente_pago_sugerido DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    snapshot_json JSON NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_trabajador_nomina_detalle_periodo_trabajador (periodo_id, trabajador_id),
    KEY idx_trabajador_nomina_detalles_hotel_periodo (hotel_id, periodo_id),
    KEY idx_trabajador_nomina_detalles_trabajador (hotel_id, trabajador_id),
    CONSTRAINT fk_trabajador_nomina_detalles_periodo
        FOREIGN KEY (periodo_id) REFERENCES trabajador_nomina_periodos (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_nomina_detalles_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_nomina_detalles_trabajador
        FOREIGN KEY (trabajador_id) REFERENCES trabajadores (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_trabajador_nomina_detalles_contadores
        CHECK (
            conceptos_count >= 0
            AND anticipos_count >= 0
            AND prestamos_count >= 0
            AND pagos_caja_count >= 0
            AND pagos_caja_pagados >= 0
            AND pagos_caja_revertidos >= 0
            AND pendiente_pago_sugerido >= 0
        )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trabajador_nomina_periodo_eventos (
    id INT NOT NULL AUTO_INCREMENT,
    periodo_id INT NOT NULL,
    hotel_id INT NOT NULL,
    tipo ENUM('cierre', 'aprobacion', 'anulacion') NOT NULL,
    estado_resultante ENUM('cerrado', 'aprobado', 'anulado') NOT NULL,
    descripcion VARCHAR(180) NOT NULL,
    motivo VARCHAR(255) NULL,
    created_by INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_trabajador_nomina_eventos_periodo (periodo_id, created_at),
    KEY idx_trabajador_nomina_eventos_hotel (hotel_id, created_at),
    KEY idx_trabajador_nomina_eventos_created_by (created_by),
    CONSTRAINT fk_trabajador_nomina_eventos_periodo
        FOREIGN KEY (periodo_id) REFERENCES trabajador_nomina_periodos (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_nomina_eventos_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_trabajador_nomina_eventos_created_by
        FOREIGN KEY (created_by) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @periodos_columns := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trabajador_nomina_periodos'
      AND COLUMN_NAME IN (
          'id',
          'hotel_id',
          'tipo_periodo',
          'etiqueta',
          'fecha_inicio',
          'fecha_fin',
          'estado',
          'filtros_json',
          'resumen_json',
          'trabajadores_total',
          'bruto_total',
          'deducciones_total',
          'pagos_caja_aplicados_total',
          'reversiones_detectadas_total',
          'neto_sugerido_total',
          'pendiente_pago_total',
          'cerrado_por',
          'cerrado_at',
          'aprobado_por',
          'aprobado_at',
          'anulado_por',
          'anulado_at',
          'motivo_anulacion',
          'created_at',
          'updated_at'
      )
);

SET @detalles_columns := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trabajador_nomina_periodo_detalles'
      AND COLUMN_NAME IN (
          'id',
          'periodo_id',
          'hotel_id',
          'trabajador_id',
          'trabajador_nombre',
          'trabajador_identificacion',
          'trabajador_rol',
          'trabajador_estado',
          'estado_preview_nomina',
          'motivo_bloqueo_nomina',
          'conceptos_count',
          'conceptos_a_favor',
          'conceptos_en_contra',
          'bruto_periodo',
          'anticipos_count',
          'anticipos_saldo',
          'prestamos_count',
          'prestamos_saldo',
          'deducciones_informativas',
          'pagos_caja_count',
          'pagos_caja_pagados',
          'pagos_caja_revertidos',
          'pagos_caja_aplicados',
          'pagos_caja_revertidos_total',
          'reversiones_detectadas',
          'ultimo_pago_caja',
          'neto_sugerido',
          'pendiente_pago_sugerido',
          'snapshot_json',
          'created_at'
      )
);

SET @eventos_columns := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trabajador_nomina_periodo_eventos'
      AND COLUMN_NAME IN (
          'id',
          'periodo_id',
          'hotel_id',
          'tipo',
          'estado_resultante',
          'descripcion',
          'motivo',
          'created_by',
          'created_at'
      )
);

SET @required_indexes := (
    SELECT COUNT(DISTINCT CONCAT(TABLE_NAME, ':', INDEX_NAME))
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND (
          (TABLE_NAME = 'trabajador_nomina_periodos' AND INDEX_NAME IN (
              'PRIMARY',
              'uk_trabajador_nomina_periodo_hotel_rango',
              'idx_trabajador_nomina_periodos_hotel_estado',
              'idx_trabajador_nomina_periodos_hotel_fecha',
              'idx_trabajador_nomina_periodos_cerrado_por',
              'idx_trabajador_nomina_periodos_aprobado_por',
              'idx_trabajador_nomina_periodos_anulado_por'
          ))
          OR (TABLE_NAME = 'trabajador_nomina_periodo_detalles' AND INDEX_NAME IN (
              'PRIMARY',
              'uk_trabajador_nomina_detalle_periodo_trabajador',
              'idx_trabajador_nomina_detalles_hotel_periodo',
              'idx_trabajador_nomina_detalles_trabajador'
          ))
          OR (TABLE_NAME = 'trabajador_nomina_periodo_eventos' AND INDEX_NAME IN (
              'PRIMARY',
              'idx_trabajador_nomina_eventos_periodo',
              'idx_trabajador_nomina_eventos_hotel',
              'idx_trabajador_nomina_eventos_created_by'
          ))
      )
);

SET @validation_sql := IF(
    @periodos_columns = 25
    AND @detalles_columns = 30
    AND @eventos_columns = 9
    AND @required_indexes = 15,
    'SELECT ''OK: snapshots persistentes de pre-nomina creados sin Caja'' AS resultado',
    'SELECT no_existe_contrato_5e_l_a_nomina_periodos_persistentes'
);

PREPARE stmt FROM @validation_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO migrations (nombre, batch, checksum, estado)
SELECT @migration_name, COALESCE(MAX(batch), 0) + 1, SHA2(CONCAT(@migration_name, '|v1|nomina-periodos-persistentes'), 256), 'ejecutada'
FROM migrations
ON DUPLICATE KEY UPDATE
    checksum = VALUES(checksum),
    estado = 'ejecutada',
    ejecutada_en = CURRENT_TIMESTAMP;

-- Rollback manual documentado:
--
-- Ejecutar solo con autorizacion explicita y backup verificado:
--
-- SELECT COUNT(*) AS periodos FROM trabajador_nomina_periodos;
-- SELECT COUNT(*) AS detalles FROM trabajador_nomina_periodo_detalles;
-- SELECT COUNT(*) AS eventos FROM trabajador_nomina_periodo_eventos;
--
-- Si los conteos son 0:
--
-- DROP TABLE trabajador_nomina_periodo_eventos;
-- DROP TABLE trabajador_nomina_periodo_detalles;
-- DROP TABLE trabajador_nomina_periodos;
--
-- DELETE FROM migrations
-- WHERE nombre = '20260621_001_fase_5e_l_a_nomina_periodos_persistentes.sql';
--
-- Si existen snapshots reales, no eliminar tablas sin fase formal de anulacion o
-- reconciliacion documental.
