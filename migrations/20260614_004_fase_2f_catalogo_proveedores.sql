-- Fase 2F minima - Catalogo de proveedores por hotel.
-- Alcance:
-- - Crea solo la tabla proveedores.
-- - No crea compras, pagos, cuentas por pagar ni documentos.
-- - No toca caja ni movimientos financieros.
-- - No migra datos legacy desde movimientos_caja.proveedor.
-- - Reversible mediante rollback manual documentado al final.

START TRANSACTION;

SET @migration_name := '20260614_004_fase_2f_catalogo_proveedores.sql';

CREATE TABLE IF NOT EXISTS proveedores (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    nombre VARCHAR(160) NOT NULL,
    razon_social VARCHAR(180) NULL,
    rfc VARCHAR(20) NULL,
    telefono VARCHAR(40) NULL,
    email VARCHAR(160) NULL,
    direccion VARCHAR(255) NULL,
    notas TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NULL,
    updated_by INT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_proveedores_hotel_activo (hotel_id, activo),
    KEY idx_proveedores_hotel_nombre (hotel_id, nombre),
    KEY idx_proveedores_hotel_rfc (hotel_id, rfc),
    KEY idx_proveedores_created_by (created_by),
    KEY idx_proveedores_updated_by (updated_by),
    CONSTRAINT fk_proveedores_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_proveedores_created_by
        FOREIGN KEY (created_by) REFERENCES usuarios(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_proveedores_updated_by
        FOREIGN KEY (updated_by) REFERENCES usuarios(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- 1) Verificar que no existan compras ni referencias futuras:
-- SELECT COUNT(*) FROM proveedores;
--
-- 2) Si la fase debe retirarse completa antes de usarse en produccion:
-- DROP TABLE proveedores;
--
-- 3) Quitar el registro de migracion:
-- DELETE FROM migrations
-- WHERE nombre = '20260614_004_fase_2f_catalogo_proveedores.sql';
--
-- No ejecutar rollback si una fase posterior ya creo compras, documentos,
-- cuentas por pagar o referencias hacia proveedores.
