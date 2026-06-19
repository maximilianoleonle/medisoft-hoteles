-- Fase 7B-A - Cuentas por cobrar base vacia.
--
-- OBJETIVO:
-- - Crear estructura aditiva para CxC operativa futura.
-- - Mantener tablas vacias en esta fase.
-- - No poblar desde reservaciones, pagos, abonos ni facturacion historica.
-- - No crear cobros.
-- - No tocar Caja ni cortes.
-- - No tocar /api/sync.
--
-- BACKUP REALIZADO ANTES DE EJECUTAR EN LOCAL:
-- - Archivo: backups/medisoft_hoteles_import_before_7b_a_cxc_base_20260618_163625.sql
-- - SHA256: 0E13547DF43359E71C1A3503EFD8F3A5B5CD096A9BF843EB19F282C0FBE90514
-- - Tamano: 3269772 bytes

SET @migration_name := '20260618_001_fase_7b_a_cxc_base_vacia.sql';

START TRANSACTION;

CREATE TABLE IF NOT EXISTS cuentas_por_cobrar (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    origen_tipo ENUM('manual', 'reservacion', 'solicitud_factura', 'ajuste') NOT NULL DEFAULT 'manual',
    origen_id INT NULL,
    huesped_id INT NULL,
    reservacion_id INT NULL,
    solicitud_factura_id INT NULL,
    folio VARCHAR(80) NULL,
    concepto VARCHAR(255) NOT NULL,
    fecha_emision DATE NOT NULL,
    fecha_vencimiento DATE NULL,
    estado ENUM('pendiente', 'parcial', 'liquidada', 'vencida', 'cancelada', 'incobrable') NOT NULL DEFAULT 'pendiente',
    moneda CHAR(3) NOT NULL DEFAULT 'MXN',
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    saldo DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    notas TEXT NULL,
    creado_por_usuario_id INT NULL,
    actualizado_por_usuario_id INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_cxc_hotel_origen (hotel_id, origen_tipo, origen_id),
    KEY idx_cxc_hotel_estado (hotel_id, estado),
    KEY idx_cxc_hotel_huesped (hotel_id, huesped_id),
    KEY idx_cxc_hotel_reservacion (hotel_id, reservacion_id),
    KEY idx_cxc_hotel_factura (hotel_id, solicitud_factura_id),
    KEY idx_cxc_fecha_vencimiento (fecha_vencimiento),
    KEY idx_cxc_creado_por (creado_por_usuario_id),
    KEY idx_cxc_actualizado_por (actualizado_por_usuario_id),
    CONSTRAINT fk_cxc_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_cxc_huesped
        FOREIGN KEY (huesped_id) REFERENCES huespedes (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_cxc_reservacion
        FOREIGN KEY (reservacion_id) REFERENCES reservaciones (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_cxc_solicitud_factura
        FOREIGN KEY (solicitud_factura_id) REFERENCES solicitudes_factura (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_cxc_creado_por
        FOREIGN KEY (creado_por_usuario_id) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_cxc_actualizado_por
        FOREIGN KEY (actualizado_por_usuario_id) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_cxc_importes_no_negativos
        CHECK (total >= 0 AND saldo >= 0),
    CONSTRAINT chk_cxc_saldo_no_mayor_total
        CHECK (saldo <= total)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cuentas_por_cobrar_movimientos (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    cuenta_por_cobrar_id INT NOT NULL,
    tipo_movimiento ENUM('CREACION', 'AJUSTE', 'CANCELACION', 'NOTA', 'RECLASIFICACION') NOT NULL,
    monto DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    saldo_anterior DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    saldo_posterior DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    referencia VARCHAR(120) NULL,
    notas TEXT NULL,
    usuario_id INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_cxc_mov_cuenta (cuenta_por_cobrar_id),
    KEY idx_cxc_mov_hotel_tipo (hotel_id, tipo_movimiento),
    KEY idx_cxc_mov_usuario (usuario_id),
    CONSTRAINT fk_cxc_mov_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_cxc_mov_cuenta
        FOREIGN KEY (cuenta_por_cobrar_id) REFERENCES cuentas_por_cobrar (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_cxc_mov_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_cxc_mov_importes_no_negativos
        CHECK (monto >= 0 AND saldo_anterior >= 0 AND saldo_posterior >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @cxc_columns_ok := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'cuentas_por_cobrar'
      AND COLUMN_NAME IN (
          'id', 'hotel_id', 'origen_tipo', 'origen_id', 'huesped_id',
          'reservacion_id', 'solicitud_factura_id', 'folio', 'concepto',
          'fecha_emision', 'fecha_vencimiento', 'estado', 'moneda',
          'total', 'saldo', 'notas', 'creado_por_usuario_id',
          'actualizado_por_usuario_id', 'created_at', 'updated_at'
      )
);

SET @cxc_mov_columns_ok := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'cuentas_por_cobrar_movimientos'
      AND COLUMN_NAME IN (
          'id', 'hotel_id', 'cuenta_por_cobrar_id', 'tipo_movimiento',
          'monto', 'saldo_anterior', 'saldo_posterior', 'referencia',
          'notas', 'usuario_id', 'created_at'
      )
);

SET @validation_sql := IF(
    @cxc_columns_ok = 20
    AND @cxc_mov_columns_ok = 11,
    'SELECT ''OK: estructura CxC base 7B-A verificada'' AS resultado',
    'SELECT no_existe_columna_cxc_base_7b_a'
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

-- Rollback manual documentado:
--
-- Ejecutar solo con autorizacion explicita y si ambas tablas siguen vacias:
--
-- SELECT COUNT(*) AS movimientos FROM cuentas_por_cobrar_movimientos;
-- SELECT COUNT(*) AS cuentas FROM cuentas_por_cobrar;
--
-- DROP TABLE cuentas_por_cobrar_movimientos;
-- DROP TABLE cuentas_por_cobrar;
--
-- DELETE FROM migrations
-- WHERE nombre = '20260618_001_fase_7b_a_cxc_base_vacia.sql';
--
-- Si existen datos reales, no ejecutar DROP. Exportar, reconciliar y documentar
-- rollback especifico antes de cualquier cambio.
