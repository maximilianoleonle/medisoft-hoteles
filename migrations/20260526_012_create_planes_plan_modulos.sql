-- Microfase 4.5 - Planes comerciales y presets de modulos.
-- Alcance:
-- - Crea catalogo comercial de planes.
-- - Crea relacion de modulos sugeridos por plan.
-- - Agrega plan_id nullable a hoteles como referencia comercial.
-- - No modifica PWA, branding, pagos, facturacion SaaS ni permisos operativos.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS planes (
    id INT NOT NULL AUTO_INCREMENT,
    clave VARCHAR(80) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    descripcion VARCHAR(255) DEFAULT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    orden INT NOT NULL DEFAULT 0,
    precio_mensual DECIMAL(10,2) DEFAULT NULL,
    moneda_codigo CHAR(3) DEFAULT 'MXN',
    metadata JSON DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_planes_clave (clave),
    KEY idx_planes_activo (activo),
    KEY idx_planes_orden (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS plan_modulos (
    id INT NOT NULL AUTO_INCREMENT,
    plan_id INT NOT NULL,
    modulo_id INT NOT NULL,
    incluido TINYINT(1) NOT NULL DEFAULT 1,
    orden INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_plan_modulos_plan_modulo (plan_id, modulo_id),
    KEY idx_plan_modulos_plan (plan_id),
    KEY idx_plan_modulos_modulo (modulo_id),
    KEY idx_plan_modulos_incluido (incluido),
    CONSTRAINT fk_plan_modulos_plan
        FOREIGN KEY (plan_id) REFERENCES planes(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_plan_modulos_modulo
        FOREIGN KEY (modulo_id) REFERENCES modulos(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @hotel_plan_col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'hoteles'
      AND COLUMN_NAME = 'plan_id'
);

SET @add_hotel_plan_col := IF(
    @hotel_plan_col_exists = 0,
    'ALTER TABLE hoteles ADD COLUMN plan_id INT NULL AFTER activo',
    'SELECT 1'
);
PREPARE stmt FROM @add_hotel_plan_col;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @hotel_plan_fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'hoteles'
      AND CONSTRAINT_NAME = 'fk_hoteles_plan'
);

SET @add_hotel_plan_fk := IF(
    @hotel_plan_fk_exists = 0,
    'ALTER TABLE hoteles ADD CONSTRAINT fk_hoteles_plan FOREIGN KEY (plan_id) REFERENCES planes(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @add_hotel_plan_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @hotel_plan_idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'hoteles'
      AND INDEX_NAME = 'idx_hoteles_plan_id'
);

SET @add_hotel_plan_idx := IF(
    @hotel_plan_idx_exists = 0,
    'CREATE INDEX idx_hoteles_plan_id ON hoteles(plan_id)',
    'SELECT 1'
);
PREPARE stmt FROM @add_hotel_plan_idx;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO planes
    (clave, nombre, descripcion, activo, orden, precio_mensual, moneda_codigo)
VALUES
    ('basico', 'Basico', 'Operacion hotelera esencial.', 1, 10, NULL, 'MXN'),
    ('pro', 'Pro', 'Operacion avanzada con inventario y facturacion.', 1, 20, NULL, 'MXN'),
    ('premium', 'Premium', 'Suite completa con canales, auditoria e inteligencia.', 1, 30, NULL, 'MXN'),
    ('personalizado', 'Personalizado', 'Configuracion manual de modulos por hotel.', 1, 40, NULL, 'MXN')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    activo = VALUES(activo),
    orden = VALUES(orden),
    precio_mensual = VALUES(precio_mensual),
    moneda_codigo = VALUES(moneda_codigo),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO plan_modulos (plan_id, modulo_id, incluido, orden)
SELECT p.id, m.id, 1, m.orden
FROM planes p
INNER JOIN modulos m ON m.clave IN ('dashboard', 'habitaciones', 'reservaciones', 'huespedes', 'caja', 'reportes')
WHERE p.clave = 'basico'
ON DUPLICATE KEY UPDATE
    incluido = VALUES(incluido),
    orden = VALUES(orden),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO plan_modulos (plan_id, modulo_id, incluido, orden)
SELECT p.id, m.id, 1, m.orden
FROM planes p
INNER JOIN modulos m ON m.clave IN ('dashboard', 'habitaciones', 'reservaciones', 'huespedes', 'caja', 'reportes', 'facturacion', 'inventario', 'limpieza', 'mantenimiento')
WHERE p.clave = 'pro'
ON DUPLICATE KEY UPDATE
    incluido = VALUES(incluido),
    orden = VALUES(orden),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO plan_modulos (plan_id, modulo_id, incluido, orden)
SELECT p.id, m.id, 1, m.orden
FROM planes p
INNER JOIN modulos m ON m.clave IN ('dashboard', 'habitaciones', 'reservaciones', 'huespedes', 'caja', 'reportes', 'facturacion', 'inventario', 'limpieza', 'mantenimiento', 'lavanderia', 'auditoria', 'pwa', 'whatsapp', 'ia_ejecutiva')
WHERE p.clave = 'premium'
ON DUPLICATE KEY UPDATE
    incluido = VALUES(incluido),
    orden = VALUES(orden),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260526_012_create_planes_plan_modulos.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
