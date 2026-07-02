-- Bloques Tier 1: notificaciones, compras, cuentas_cobrar, anticipos.
-- Alcance:
-- - Registra 4 bloques opcionales nuevos con precio editable.
-- - Los activa retroactivamente en hoteles existentes (no cambia su comportamiento).
-- - Los suma a los presets pro/premium segun alcance comercial.
-- - No modifica logica de caja ni datos operativos.

START TRANSACTION;

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, es_core, precio_mensual, activo_global, orden, icono, ruta_base)
VALUES
    ('anticipos', 'Anticipos y abonos', 'Apartado con anticipo, abonos ligados a Caja y saldo al check-in.', 'finanzas', 0, 129.00, 1, 55, 'hand-coins', NULL),
    ('cuentas_cobrar', 'Credito a clientes (CxC)', 'Cuentas por cobrar de empresas y clientes, cobros parciales via Caja.', 'finanzas', 0, 199.00, 1, 62, 'file-invoice-dollar', '/cuentas-por-cobrar'),
    ('compras', 'Compras y proveedores', 'Compras, recepcion, catalogo de proveedores y cuentas por pagar.', 'operacion', 0, 199.00, 1, 72, 'truck', '/compras'),
    ('notificaciones', 'Notificaciones y push', 'Centro de avisos, reglas automaticas y push al celular.', 'canales', 0, 149.00, 1, 115, 'bell', '/notificaciones')
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
WHERE m.clave IN ('anticipos', 'cuentas_cobrar', 'compras', 'notificaciones')
ON DUPLICATE KEY UPDATE updated_at = hotel_modulos.updated_at;

-- Presets: pro incluye anticipos y compras; premium incluye los 4.
INSERT INTO plan_modulos (plan_id, modulo_id, incluido, orden)
SELECT p.id, m.id, 1, m.orden
FROM planes p
INNER JOIN modulos m ON m.clave IN ('anticipos', 'compras')
WHERE p.clave = 'pro'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

INSERT INTO plan_modulos (plan_id, modulo_id, incluido, orden)
SELECT p.id, m.id, 1, m.orden
FROM planes p
INNER JOIN modulos m ON m.clave IN ('anticipos', 'cuentas_cobrar', 'compras', 'notificaciones')
WHERE p.clave = 'premium'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260702_004_modulos_tier1.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
