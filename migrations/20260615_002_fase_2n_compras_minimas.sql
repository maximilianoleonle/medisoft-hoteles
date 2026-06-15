-- Fase 2O - Promocion controlada del borrador Fase 2N - Compras minimas.
--
-- BACKUP OBLIGATORIO ANTES DE EJECUTAR:
-- - Generar mysqldump completo de medisoft_hoteles_import.
-- - Confirmar archivo, tamano y SHA256.
--
-- ALCANCE:
-- - Crea solo tablas compras y compra_detalles.
-- - No crea pagos de compras.
-- - No crea cuentas por pagar.
-- - No crea documentos ni adjuntos.
-- - No toca movimientos_caja, cortes_caja ni calculos financieros.
-- - No toca /api/sync.
-- - No crea rutas, controladores, modelos ni vistas.
--
-- CONTRATO FUNCIONAL FUTURO:
-- - compra.estado permite: borrador, recibida, cancelada.
-- - Solo estado recibida podria generar movimientos_inventario.
-- - La recepcion futura debe ser transaccional e idempotente.
-- - La aplicacion debe validar que proveedor_id y producto_id pertenecen al mismo hotel_id.

SET @migration_name := '20260615_002_fase_2n_compras_minimas.sql';

START TRANSACTION;

CREATE TABLE IF NOT EXISTS compras (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    proveedor_id INT NOT NULL,
    folio VARCHAR(60) NULL,
    fecha_compra DATE NOT NULL,
    fecha_recepcion DATETIME NULL,
    estado ENUM('borrador', 'recibida', 'cancelada') NOT NULL DEFAULT 'borrador',
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    impuestos DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    notas TEXT NULL,
    recibida_por INT NULL,
    cancelada_por INT NULL,
    created_by INT NULL,
    updated_by INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_compras_hotel_folio (hotel_id, folio),
    KEY idx_compras_hotel_estado (hotel_id, estado),
    KEY idx_compras_hotel_proveedor (hotel_id, proveedor_id),
    KEY idx_compras_fecha (fecha_compra),
    KEY idx_compras_recibida_por (recibida_por),
    KEY idx_compras_cancelada_por (cancelada_por),
    KEY idx_compras_created_by (created_by),
    KEY idx_compras_updated_by (updated_by),
    CONSTRAINT fk_compras_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_compras_proveedor
        FOREIGN KEY (proveedor_id) REFERENCES proveedores(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_compras_recibida_por
        FOREIGN KEY (recibida_por) REFERENCES usuarios(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_compras_cancelada_por
        FOREIGN KEY (cancelada_por) REFERENCES usuarios(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_compras_created_by
        FOREIGN KEY (created_by) REFERENCES usuarios(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_compras_updated_by
        FOREIGN KEY (updated_by) REFERENCES usuarios(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_compras_totales_no_negativos
        CHECK (subtotal >= 0 AND impuestos >= 0 AND total >= 0),
    CONSTRAINT chk_compras_recepcion_estado
        CHECK ((estado = 'recibida' AND fecha_recepcion IS NOT NULL) OR estado <> 'recibida')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS compra_detalles (
    id INT NOT NULL AUTO_INCREMENT,
    compra_id INT NOT NULL,
    hotel_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    costo_unitario DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    movimiento_inventario_id INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_compra_detalles_compra (compra_id),
    KEY idx_compra_detalles_hotel_producto (hotel_id, producto_id),
    UNIQUE KEY uk_compra_detalles_movimiento (movimiento_inventario_id),
    CONSTRAINT fk_compra_detalles_compra
        FOREIGN KEY (compra_id) REFERENCES compras(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_compra_detalles_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_compra_detalles_producto
        FOREIGN KEY (producto_id) REFERENCES inventario_productos(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_compra_detalles_movimiento
        FOREIGN KEY (movimiento_inventario_id) REFERENCES movimientos_inventario(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_compra_detalles_cantidad_positiva
        CHECK (cantidad > 0),
    CONSTRAINT chk_compra_detalles_importes_no_negativos
        CHECK (costo_unitario >= 0 AND subtotal >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @compras_columns_ok := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'compras'
      AND COLUMN_NAME IN (
          'id', 'hotel_id', 'proveedor_id', 'folio', 'fecha_compra',
          'fecha_recepcion', 'estado', 'subtotal', 'impuestos', 'total',
          'notas', 'recibida_por', 'cancelada_por', 'created_by',
          'updated_by', 'created_at', 'updated_at'
      )
);

SET @compra_detalles_columns_ok := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'compra_detalles'
      AND COLUMN_NAME IN (
          'id', 'compra_id', 'hotel_id', 'producto_id', 'cantidad',
          'costo_unitario', 'subtotal', 'movimiento_inventario_id',
          'created_at', 'updated_at'
      )
);

SET @sql := IF(
    @compras_columns_ok = 17,
    'SELECT ''compras estructura minima OK'' AS info',
    'SELECT * FROM __fase_2o_compras_estructura_invalida__'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @compra_detalles_columns_ok = 10,
    'SELECT ''compra_detalles estructura minima OK'' AS info',
    'SELECT * FROM __fase_2o_compra_detalles_estructura_invalida__'
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
-- SELECT COUNT(*) AS compras FROM compras;
-- SELECT COUNT(*) AS compra_detalles FROM compra_detalles;
--
-- 2) Si la fase se aplico por error y no hay datos que conservar:
-- DROP TABLE compra_detalles;
-- DROP TABLE compras;
--
-- 3) Quitar registro de migracion:
-- DELETE FROM migrations
-- WHERE nombre = '20260615_002_fase_2n_compras_minimas.sql';
--
-- 4) Si existen datos reales, no ejecutar DROP. Exportar, reconciliar y
-- documentar rollback especifico antes de cualquier cambio.
