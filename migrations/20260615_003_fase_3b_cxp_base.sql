-- Fase 3B - Cuentas por pagar base read-only.
--
-- OBJETIVO:
-- - Preparar estructura no destructiva para cuentas por pagar.
-- - Mantener la primera etapa como visibilidad/read-only.
-- - No registrar pagos.
-- - No tocar caja.
-- - No afectar reportes financieros oficiales.
-- - No tocar /api/sync.
--
-- BACKUP REALIZADO ANTES DE EJECUTAR EN LOCAL:
-- - Archivo: src/storage/backups/phase3b_20260615_040742_before_cxp_medisoft_hoteles_import.sql
-- - SHA256: 24663D206AE15B86B001708D8BC2665541548443A0EA363548CAFC3FDF3A4D2C
-- - Tamano: 3017728 bytes

SET @migration_name := '20260615_003_fase_3b_cxp_base.sql';

START TRANSACTION;

CREATE TABLE IF NOT EXISTS cuentas_por_pagar (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    proveedor_id INT NOT NULL,
    compra_id INT NULL,
    folio VARCHAR(80) NULL,
    descripcion VARCHAR(255) NULL,
    fecha_emision DATE NOT NULL,
    fecha_vencimiento DATE NULL,
    estado ENUM('pendiente', 'parcial', 'pagada', 'vencida', 'cancelada') NOT NULL DEFAULT 'pendiente',
    moneda CHAR(3) NOT NULL DEFAULT 'MXN',
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    impuestos DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    saldo DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    notas TEXT NULL,
    created_by INT NULL,
    updated_by INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_cxp_hotel_compra (hotel_id, compra_id),
    KEY idx_cxp_hotel_estado (hotel_id, estado),
    KEY idx_cxp_hotel_proveedor (hotel_id, proveedor_id),
    KEY idx_cxp_proveedor (proveedor_id),
    KEY idx_cxp_compra (compra_id),
    KEY idx_cxp_fecha_vencimiento (fecha_vencimiento),
    KEY idx_cxp_created_by (created_by),
    KEY idx_cxp_updated_by (updated_by),
    CONSTRAINT fk_cxp_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_cxp_proveedor
        FOREIGN KEY (proveedor_id) REFERENCES proveedores(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_cxp_compra
        FOREIGN KEY (compra_id) REFERENCES compras(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_cxp_created_by
        FOREIGN KEY (created_by) REFERENCES usuarios(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_cxp_updated_by
        FOREIGN KEY (updated_by) REFERENCES usuarios(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_cxp_importes_no_negativos
        CHECK (subtotal >= 0 AND impuestos >= 0 AND total >= 0 AND saldo >= 0),
    CONSTRAINT chk_cxp_saldo_no_mayor_total
        CHECK (saldo <= total)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cuentas_por_pagar_movimientos (
    id INT NOT NULL AUTO_INCREMENT,
    cuenta_por_pagar_id INT NOT NULL,
    hotel_id INT NOT NULL,
    tipo_movimiento ENUM('CREACION', 'AJUSTE', 'CANCELACION', 'PAGO_REFERENCIAL') NOT NULL,
    monto DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    saldo_anterior DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    saldo_posterior DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    referencia VARCHAR(120) NULL,
    notas TEXT NULL,
    usuario_id INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_cxp_mov_cuenta (cuenta_por_pagar_id),
    KEY idx_cxp_mov_hotel_tipo (hotel_id, tipo_movimiento),
    KEY idx_cxp_mov_usuario (usuario_id),
    CONSTRAINT fk_cxp_mov_cuenta
        FOREIGN KEY (cuenta_por_pagar_id) REFERENCES cuentas_por_pagar(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_cxp_mov_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_cxp_mov_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_cxp_mov_importes_no_negativos
        CHECK (monto >= 0 AND saldo_anterior >= 0 AND saldo_posterior >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @cxp_columns_ok := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'cuentas_por_pagar'
      AND COLUMN_NAME IN (
          'id', 'hotel_id', 'proveedor_id', 'compra_id', 'folio',
          'descripcion', 'fecha_emision', 'fecha_vencimiento', 'estado',
          'moneda', 'subtotal', 'impuestos', 'total', 'saldo', 'notas',
          'created_by', 'updated_by', 'created_at', 'updated_at'
      )
);

SET @cxp_mov_columns_ok := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'cuentas_por_pagar_movimientos'
      AND COLUMN_NAME IN (
          'id', 'cuenta_por_pagar_id', 'hotel_id', 'tipo_movimiento',
          'monto', 'saldo_anterior', 'saldo_posterior', 'referencia',
          'notas', 'usuario_id', 'created_at'
      )
);

SET @sql := IF(
    @cxp_columns_ok = 19,
    'SELECT ''cuentas_por_pagar estructura base OK'' AS info',
    'SELECT * FROM __fase_3b_cxp_estructura_invalida__'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @cxp_mov_columns_ok = 11,
    'SELECT ''cuentas_por_pagar_movimientos estructura base OK'' AS info',
    'SELECT * FROM __fase_3b_cxp_movimientos_estructura_invalida__'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

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

COMMIT;

-- Rollback manual documentado:
--
-- 1) Confirmar backup previo y que no hay datos operativos que conservar:
-- SELECT COUNT(*) AS cuentas FROM cuentas_por_pagar;
-- SELECT COUNT(*) AS movimientos FROM cuentas_por_pagar_movimientos;
--
-- 2) Si la fase se aplico por error y no hay datos que conservar:
-- DROP TABLE cuentas_por_pagar_movimientos;
-- DROP TABLE cuentas_por_pagar;
--
-- 3) Quitar registro de migracion:
-- DELETE FROM migrations
-- WHERE nombre = '20260615_003_fase_3b_cxp_base.sql';
--
-- 4) Si existen datos reales, no ejecutar DROP. Exportar, reconciliar y
-- documentar rollback especifico antes de cualquier cambio.
