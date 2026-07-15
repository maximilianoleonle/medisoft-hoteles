-- Bloque modo_dueno - Fase 1: Modo Dueno (resumen remoto de solo lectura).
-- Alcance:
-- - Registra el bloque comercial modo_dueno (se vende como asiento adicional
--   para el dueno que no opera el hotel; vista /dueno, 100% lectura).
-- - Preset: el plan premium lo incluye (mismo criterio que copiloto_ia).
-- - Siembra el rol de sistema 'dueno_remoto' ("Dueno (remoto)") en cada hotel
--   existente, con permisos SOLO de lectura. Debe coincidir con el preset de
--   config/permisos.php; hoteles nuevos lo heredan via Rol::sembrarPresetsParaHotel().
-- - NO crea tablas nuevas, NO toca Caja ni datos operativos.

SET @migration_name := '20260715_002_modo_dueno.sql';

START TRANSACTION;

INSERT INTO modulos
    (clave, nombre, descripcion, categoria, es_core, precio_mensual, activo_global, orden, icono, ruta_base)
VALUES
    ('modo_dueno', 'Modo Dueno', 'Vista remota para el dueno que no opera el hotel: como va el dia, cuanto entro, ocupacion y que dicen los huespedes. Solo lectura, en su telefono.', 'analitica', 0, 249.00, 1, 84, 'user-tie', '/dueno')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    categoria = VALUES(categoria),
    orden = VALUES(orden),
    icono = VALUES(icono),
    ruta_base = VALUES(ruta_base),
    updated_at = CURRENT_TIMESTAMP;

-- Preset: premium lo incluye.
INSERT INTO plan_modulos (plan_id, modulo_id, incluido, orden)
SELECT p.id, m.id, 1, m.orden
FROM planes p
INNER JOIN modulos m ON m.clave = 'modo_dueno'
WHERE p.clave = 'premium'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

-- Rol de sistema "Dueno (remoto)" por hotel (idempotente; solo lectura).
INSERT INTO roles (hotel_id, clave, nombre, descripcion, es_sistema, activo, permisos_json, created_at, updated_at)
SELECT h.id, 'dueno_remoto', 'Dueno (remoto)',
       'Dueno que no opera el hotel: solo lectura del resumen del dia (Modo Dueno).', 1, 1,
       '["dueno.view","caja.view","habitaciones.view","reservaciones.view","reputacion.view","guardian.view","notificaciones.view"]',
       NOW(), NOW()
FROM hoteles h
WHERE NOT EXISTS (SELECT 1 FROM roles r WHERE r.hotel_id = h.id AND r.clave = 'dueno_remoto');

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES (@migration_name, 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;

-- Rollback manual:
-- DELETE FROM plan_modulos WHERE modulo_id = (SELECT id FROM modulos WHERE clave = 'modo_dueno');
-- DELETE FROM hotel_modulos WHERE modulo_id = (SELECT id FROM modulos WHERE clave = 'modo_dueno');
-- DELETE FROM modulos WHERE clave = 'modo_dueno';
-- DELETE FROM roles WHERE clave = 'dueno_remoto' AND es_sistema = 1;  -- (reasignar usuarios antes)
-- DELETE FROM migrations WHERE nombre = '20260715_002_modo_dueno.sql';
