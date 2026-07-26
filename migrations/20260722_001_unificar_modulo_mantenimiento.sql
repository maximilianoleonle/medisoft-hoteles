-- Unifica Mantenimiento y Mantenimiento Plus en un solo bloque comercial.
--
-- Decision de producto (2026-07-22): todas las funciones correctivas,
-- programadas, evidencia fotografica, costos, activos y preventivos forman
-- parte del modulo `mantenimiento`. No se suma ni se copia el precio de Plus.
--
-- La migracion conserva el acceso: si un hotel o plan tenia Plus activo,
-- activa/incluye Mantenimiento antes de retirar el catalogo duplicado.

SET @migration_name := '20260722_001_unificar_modulo_mantenimiento.sql';

START TRANSACTION;

SET @mantenimiento_id := (
    SELECT id FROM modulos WHERE clave = 'mantenimiento' LIMIT 1
);
SET @mantenimiento_plus_id := (
    SELECT id FROM modulos WHERE clave = 'mantenimiento_plus' LIMIT 1
);

UPDATE modulos
SET nombre = 'Mantenimiento',
    descripcion = 'Mantenimiento correctivo y programado, evidencias, costos, activos, preventivos y recordatorios.',
    categoria = 'operacion',
    activo_global = 1,
    icono = 'toolbox',
    ruta_base = '/mantenimientos/activos',
    updated_at = CURRENT_TIMESTAMP
WHERE id = @mantenimiento_id;

INSERT INTO hotel_modulos
    (hotel_id, modulo_id, activo, fuente, trial_until, config_json,
     precio_override, enabled_by, enabled_at, disabled_at)
SELECT hm.hotel_id,
       @mantenimiento_id,
       hm.activo,
       'migracion',
       hm.trial_until,
       hm.config_json,
       NULL,
       hm.enabled_by,
       hm.enabled_at,
       hm.disabled_at
FROM hotel_modulos hm
WHERE hm.modulo_id = @mantenimiento_plus_id
  AND @mantenimiento_id IS NOT NULL
ON DUPLICATE KEY UPDATE
    activo = GREATEST(hotel_modulos.activo, VALUES(activo)),
    trial_until = COALESCE(hotel_modulos.trial_until, VALUES(trial_until)),
    enabled_at = COALESCE(hotel_modulos.enabled_at, VALUES(enabled_at)),
    disabled_at = CASE
        WHEN GREATEST(hotel_modulos.activo, VALUES(activo)) = 1 THEN NULL
        ELSE hotel_modulos.disabled_at
    END,
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO plan_modulos (plan_id, modulo_id, incluido, orden)
SELECT pm.plan_id, @mantenimiento_id, pm.incluido, pm.orden
FROM plan_modulos pm
WHERE pm.modulo_id = @mantenimiento_plus_id
  AND @mantenimiento_id IS NOT NULL
ON DUPLICATE KEY UPDATE
    incluido = GREATEST(plan_modulos.incluido, VALUES(incluido)),
    orden = LEAST(plan_modulos.orden, VALUES(orden)),
    updated_at = CURRENT_TIMESTAMP;

DELETE FROM modulos
WHERE id = @mantenimiento_plus_id;

INSERT INTO migrations (nombre, batch, checksum, estado)
SELECT @migration_name,
       COALESCE(MAX(batch), 0) + 1,
       SHA2(CONCAT(@migration_name, '|v1'), 256),
       'ejecutada'
FROM migrations
ON DUPLICATE KEY UPDATE
    checksum = VALUES(checksum),
    estado = 'ejecutada',
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
