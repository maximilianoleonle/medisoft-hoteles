-- Bloque comercial: Mantenimiento Plus
-- Migracion aditiva, idempotente y multi-hotel.
--
-- Alcance:
--   - Agrega el modulo 'mantenimiento_plus' al catalogo SaaS (evidencia
--     fotografica, costos a gastos, activos y preventivo con recordatorios).
--   - Lo incluye en los presets Pro y Premium.
--   - NO activa el modulo en ningun hotel (eso es del panel SaaS).
--   - El correctivo basico existente NO queda detras de este modulo.
--
-- Rollback manual seguro (solo con autorizacion explicita):
--   DELETE pm FROM plan_modulos pm INNER JOIN modulos m ON m.id = pm.modulo_id AND m.clave = 'mantenimiento_plus';
--   DELETE hm FROM hotel_modulos hm INNER JOIN modulos m ON m.id = hm.modulo_id AND m.clave = 'mantenimiento_plus';
--   DELETE FROM modulos WHERE clave = 'mantenimiento_plus';
--   DELETE FROM migrations WHERE nombre = '20260715_002_add_modulo_mantenimiento_plus.sql';

SET @migration_name := '20260715_002_add_modulo_mantenimiento_plus.sql';

START TRANSACTION;

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, activo_global, orden, icono, ruta_base)
VALUES
    ('mantenimiento_plus', 'Mantenimiento Plus', 'Evidencia fotografica de incidencias, costos reales a gastos, activos con mantenimiento preventivo y recordatorios.', 'operacion', 1, 88, 'toolbox', '/mantenimientos/activos')
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
INNER JOIN modulos m ON m.clave = 'mantenimiento_plus'
WHERE p.clave IN ('pro', 'premium')
ON DUPLICATE KEY UPDATE
    incluido = VALUES(incluido),
    orden = VALUES(orden),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO migrations (nombre, batch, checksum, estado)
SELECT @migration_name, COALESCE(MAX(batch), 0) + 1, SHA2(CONCAT(@migration_name, '|v1'), 256), 'ejecutada'
FROM migrations
ON DUPLICATE KEY UPDATE
    checksum = VALUES(checksum),
    estado = 'ejecutada',
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
