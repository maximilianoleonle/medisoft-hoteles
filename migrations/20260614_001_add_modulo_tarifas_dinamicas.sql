-- SaaS modules - Add dynamic rates catalog entry.
-- Scope:
-- - Adds the global module used by the SaaS hotel module selector.
-- - Adds it to Pro and Premium commercial presets.
-- - Does not alter tariff tables, reservations, pricing calculations, routes, auth or permissions.

START TRANSACTION;

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, activo_global, orden, icono, ruta_base)
VALUES
    ('tarifas_dinamicas', 'Tarifas dinamicas', 'Incrementos y reglas de tarifas por temporada, fecha o habitacion.', 'administracion', 1, 87, 'tags', '/configuracion/tarifas')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    categoria = VALUES(categoria),
    activo_global = VALUES(activo_global),
    orden = VALUES(orden),
    icono = VALUES(icono),
    ruta_base = VALUES(ruta_base),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO plan_modulos (plan_id, modulo_id, incluido, orden)
SELECT p.id, m.id, 1, m.orden
FROM planes p
INNER JOIN modulos m ON m.clave = 'tarifas_dinamicas'
WHERE p.clave IN ('pro', 'premium')
ON DUPLICATE KEY UPDATE
    incluido = VALUES(incluido),
    orden = VALUES(orden),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260614_001_add_modulo_tarifas_dinamicas.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
