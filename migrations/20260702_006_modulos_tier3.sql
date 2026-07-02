-- Bloques Tier 3: tablero_ejecutivo, reportes_distribucion.
-- Alcance:
-- - Registra 2 bloques opcionales de nivel direccion con precio editable.
-- - Los activa retroactivamente en hoteles existentes.
-- - Preset: premium suma ambos.

START TRANSACTION;

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, es_core, precio_mensual, activo_global, orden, icono, ruta_base)
VALUES
    ('tablero_ejecutivo', 'Tablero de direccion', 'Operacion diaria, conciliacion financiera, tablero ejecutivo y reporte gerencial diario.', 'analitica', 0, 249.00, 1, 81, 'gauge-high', '/operacion/diaria'),
    ('reportes_distribucion', 'Distribucion de reportes', 'Links seguros publicos de reportes PDF y envio automatico por correo.', 'analitica', 0, 149.00, 1, 86, 'share-nodes', '/reportes/links')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    categoria = VALUES(categoria),
    orden = VALUES(orden),
    icono = VALUES(icono),
    ruta_base = VALUES(ruta_base),
    updated_at = CURRENT_TIMESTAMP;

-- Retrocompatibilidad: hoteles operando hoy conservan estas funciones encendidas.
INSERT INTO hotel_modulos (hotel_id, modulo_id, activo, fuente, enabled_at, created_at, updated_at)
SELECT h.hotel_id, m.id, 1, 'migracion', NOW(), NOW(), NOW()
FROM (SELECT DISTINCT hotel_id FROM hotel_modulos WHERE activo = 1) h
CROSS JOIN modulos m
WHERE m.clave IN ('tablero_ejecutivo', 'reportes_distribucion')
ON DUPLICATE KEY UPDATE updated_at = hotel_modulos.updated_at;

INSERT INTO plan_modulos (plan_id, modulo_id, incluido, orden)
SELECT p.id, m.id, 1, m.orden
FROM planes p
INNER JOIN modulos m ON m.clave IN ('tablero_ejecutivo', 'reportes_distribucion')
WHERE p.clave = 'premium'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260702_006_modulos_tier3.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
