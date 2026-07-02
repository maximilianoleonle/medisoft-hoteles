-- Bloques Tier 2: vehiculos, llaves_remotos, descuentos, roles_avanzados.
-- Alcance:
-- - Registra 4 bloques opcionales nuevos con precio editable.
-- - Los activa retroactivamente en hoteles existentes (no cambia su comportamiento).
-- - Presets: pro suma descuentos; premium suma los 4.
-- - No modifica datos operativos ni calculos de reservaciones existentes.

START TRANSACTION;

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, es_core, precio_mensual, activo_global, orden, icono, ruta_base)
VALUES
    ('descuentos', 'Descuentos', 'Descuentos por huesped frecuente aplicados al crear reservaciones.', 'finanzas', 0, 99.00, 1, 57, 'percent', NULL),
    ('vehiculos', 'Vehiculos y estacionamiento', 'Registro de vehiculos y placas por huesped, control de estacionamiento.', 'operacion', 0, 99.00, 1, 45, 'car', NULL),
    ('llaves_remotos', 'Control de llaves y remotos', 'Entrega y recepcion de llaves y controles remotos por reservacion.', 'operacion', 0, 79.00, 1, 35, 'key', NULL),
    ('roles_avanzados', 'Roles avanzados', 'Roles y permisos configurables a la medida del hotel; sin el bloque se usan los presets.', 'administracion', 0, 199.00, 1, 88, 'user-shield', '/configuracion/roles')
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
WHERE m.clave IN ('descuentos', 'vehiculos', 'llaves_remotos', 'roles_avanzados')
ON DUPLICATE KEY UPDATE updated_at = hotel_modulos.updated_at;

INSERT INTO plan_modulos (plan_id, modulo_id, incluido, orden)
SELECT p.id, m.id, 1, m.orden
FROM planes p
INNER JOIN modulos m ON m.clave IN ('descuentos')
WHERE p.clave = 'pro'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

INSERT INTO plan_modulos (plan_id, modulo_id, incluido, orden)
SELECT p.id, m.id, 1, m.orden
FROM planes p
INNER JOIN modulos m ON m.clave IN ('descuentos', 'vehiculos', 'llaves_remotos', 'roles_avanzados')
WHERE p.clave = 'premium'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260702_005_modulos_tier2.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
