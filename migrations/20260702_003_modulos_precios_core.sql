-- Bloques comerciales activables con precio (SaaS a la carte).
-- Alcance:
-- - Agrega es_core y precio_mensual al catalogo de modulos.
-- - Agrega precio_override por hotel en hotel_modulos.
-- - Crea modulos nuevos: exportaciones, personal, tareas, documentos, configuracion.
-- - Marca el paquete basico (core) y siembra precios default editables desde el panel SaaS.
-- - Activa los modulos nuevos en hoteles existentes para no cambiar su comportamiento actual.

START TRANSACTION;

-- modulos.es_core
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'modulos' AND COLUMN_NAME = 'es_core');
SET @sql := IF(@col = 0, 'ALTER TABLE modulos ADD COLUMN es_core TINYINT(1) NOT NULL DEFAULT 0 AFTER categoria', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- modulos.precio_mensual
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'modulos' AND COLUMN_NAME = 'precio_mensual');
SET @sql := IF(@col = 0, 'ALTER TABLE modulos ADD COLUMN precio_mensual DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER es_core', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- hotel_modulos.precio_override
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hotel_modulos' AND COLUMN_NAME = 'precio_override');
SET @sql := IF(@col = 0, 'ALTER TABLE hotel_modulos ADD COLUMN precio_override DECIMAL(10,2) NULL DEFAULT NULL AFTER config_json', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Modulos nuevos (cierran huecos de gating reales).
INSERT INTO modulos
    (clave, nombre, descripcion, categoria, activo_global, orden, icono, ruta_base)
VALUES
    ('configuracion', 'Configuracion', 'Configuracion general del hotel, backups y preferencias.', 'core', 1, 85, 'settings', '/configuracion'),
    ('exportaciones', 'Exportaciones Excel/PDF', 'Descarga de listados y reportes en Excel y PDF en todo el sistema.', 'analitica', 1, 82, 'file-down', NULL),
    ('personal', 'Personal y Nomina', 'Trabajadores, ledger laboral, nomina por periodos y pagos via Caja.', 'administracion', 1, 84, 'id-card', '/trabajadores'),
    ('tareas', 'Tareas operativas', 'Alta, asignacion y seguimiento de tareas de limpieza y mantenimiento.', 'operacion', 1, 95, 'list-checks', '/tareas'),
    ('documentos', 'Centro documental', 'Carga, consulta y trazabilidad de documentos por entidad.', 'administracion', 1, 83, 'folder-open', '/documentos')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    categoria = VALUES(categoria),
    orden = VALUES(orden),
    icono = VALUES(icono),
    ruta_base = VALUES(ruta_base),
    updated_at = CURRENT_TIMESTAMP;

-- Paquete basico (core): siempre activo, sin costo individual.
UPDATE modulos SET es_core = 1, precio_mensual = 0.00
WHERE clave IN ('dashboard', 'habitaciones', 'reservaciones', 'huespedes', 'caja', 'usuarios', 'configuracion');

-- Precios default de bloques opcionales (editables desde /admin/saas/modulos).
UPDATE modulos SET precio_mensual = 249.00 WHERE clave = 'facturacion' AND precio_mensual = 0;
UPDATE modulos SET precio_mensual = 299.00 WHERE clave = 'inventario' AND precio_mensual = 0;
UPDATE modulos SET precio_mensual = 249.00 WHERE clave = 'reportes' AND precio_mensual = 0;
UPDATE modulos SET precio_mensual = 99.00  WHERE clave = 'exportaciones' AND precio_mensual = 0;
UPDATE modulos SET precio_mensual = 149.00 WHERE clave = 'tarifas_dinamicas' AND precio_mensual = 0;
UPDATE modulos SET precio_mensual = 99.00  WHERE clave = 'limpieza' AND precio_mensual = 0;
UPDATE modulos SET precio_mensual = 99.00  WHERE clave = 'mantenimiento' AND precio_mensual = 0;
UPDATE modulos SET precio_mensual = 79.00  WHERE clave = 'lavanderia' AND precio_mensual = 0;
UPDATE modulos SET precio_mensual = 349.00 WHERE clave = 'personal' AND precio_mensual = 0;
UPDATE modulos SET precio_mensual = 129.00 WHERE clave = 'tareas' AND precio_mensual = 0;
UPDATE modulos SET precio_mensual = 149.00 WHERE clave = 'documentos' AND precio_mensual = 0;
UPDATE modulos SET precio_mensual = 149.00 WHERE clave = 'pwa' AND precio_mensual = 0;
UPDATE modulos SET precio_mensual = 299.00 WHERE clave = 'whatsapp' AND precio_mensual = 0;
UPDATE modulos SET precio_mensual = 499.00 WHERE clave = 'ia_ejecutiva' AND precio_mensual = 0;
UPDATE modulos SET precio_mensual = 199.00 WHERE clave = 'auditoria' AND precio_mensual = 0;

-- Precio default del paquete basico y bundles (solo si aun no tienen precio).
UPDATE planes SET precio_mensual = 999.00  WHERE clave = 'basico' AND precio_mensual IS NULL;
UPDATE planes SET precio_mensual = 1799.00 WHERE clave = 'pro' AND precio_mensual IS NULL;
UPDATE planes SET precio_mensual = 2999.00 WHERE clave = 'premium' AND precio_mensual IS NULL;

-- Retrocompatibilidad: hoteles que ya operaban ven estos bloques encendidos
-- (personal/tareas/documentos/exportaciones estaban implicitamente disponibles).
INSERT INTO hotel_modulos (hotel_id, modulo_id, activo, fuente, enabled_at, created_at, updated_at)
SELECT h.hotel_id, m.id, 1, 'migracion', NOW(), NOW(), NOW()
FROM (SELECT DISTINCT hotel_id FROM hotel_modulos WHERE activo = 1) h
CROSS JOIN modulos m
WHERE m.clave IN ('exportaciones', 'personal', 'tareas', 'documentos')
ON DUPLICATE KEY UPDATE updated_at = hotel_modulos.updated_at;

-- Presets comerciales: pro y premium incluyen los bloques nuevos acordes.
INSERT INTO plan_modulos (plan_id, modulo_id, incluido, orden)
SELECT p.id, m.id, 1, m.orden
FROM planes p
INNER JOIN modulos m ON m.clave IN ('exportaciones', 'tareas')
WHERE p.clave = 'pro'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

INSERT INTO plan_modulos (plan_id, modulo_id, incluido, orden)
SELECT p.id, m.id, 1, m.orden
FROM planes p
INNER JOIN modulos m ON m.clave IN ('exportaciones', 'personal', 'tareas', 'documentos')
WHERE p.clave = 'premium'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260702_003_modulos_precios_core.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
