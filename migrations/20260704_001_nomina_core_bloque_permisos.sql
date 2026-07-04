-- Bloque nomina_avanzada - Fase 1 (base tecnica del modulo de Nomina Core).
-- Alcance:
-- - Registra el bloque comercial nomina_avanzada ($399, opt-in: SIN activacion
--   retroactiva). Complementa al bloque 'personal' (pre-nomina administrativa);
--   no lo reemplaza ni cambia su precio ni sus rutas.
-- - Preset comercial: el plan premium lo incluye.
-- - Siembra permisos nomina.* en los roles base (es_sistema = 1) de hoteles
--   EXISTENTES: gerente recibe nomina.all; administrador recibe nomina.view,
--   nomina.incidencias y nomina.calcular. Hoteles nuevos los heredan de los
--   presets de config/permisos.php via Rol::sembrarPresetsParaHotel().
-- - NO crea tablas de dominio, NO toca tablas trabajador_*, NO toca Caja.
-- - Idempotente: se puede re-ejecutar sin duplicar permisos ni registros.

SET @migration_name := '20260704_001_nomina_core_bloque_permisos.sql';

START TRANSACTION;

-- 1) Registro del bloque en el catalogo comercial.
INSERT INTO modulos
    (clave, nombre, descripcion, categoria, es_core, precio_mensual, activo_global, orden, icono, ruta_base)
VALUES
    ('nomina_avanzada', 'Nomina avanzada', 'Motor de nomina configurable por negocio: modo operativo, hibrido o legal por fases, catalogos propios, permisos finos y auditoria. Requiere el bloque Personal para gestionar empleados.', 'administracion', 0, 399.00, 1, 21, 'file-invoice-dollar', '/nomina')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    categoria = VALUES(categoria),
    orden = VALUES(orden),
    icono = VALUES(icono),
    ruta_base = VALUES(ruta_base),
    updated_at = CURRENT_TIMESTAMP;

-- 2) Preset comercial: premium lo incluye.
INSERT INTO plan_modulos (plan_id, modulo_id, incluido, orden)
SELECT p.id, m.id, 1, m.orden
FROM planes p
INNER JOIN modulos m ON m.clave = 'nomina_avanzada'
WHERE p.clave = 'premium'
ON DUPLICATE KEY UPDATE incluido = VALUES(incluido), orden = VALUES(orden), updated_at = CURRENT_TIMESTAMP;

-- 3) Permisos nomina.* para roles base de hoteles existentes.
--    Solo roles es_sistema = 1; los roles custom los administra cada dueno.
--    Se omiten roles con comodin '*' (ya lo tienen todo).

-- 3a) gerente -> nomina.all
UPDATE roles
SET permisos_json = JSON_ARRAY_APPEND(COALESCE(permisos_json, JSON_ARRAY()), '$', 'nomina.all')
WHERE clave = 'gerente'
  AND es_sistema = 1
  AND (permisos_json IS NULL OR (
        NOT JSON_CONTAINS(permisos_json, '"nomina.all"')
    AND NOT JSON_CONTAINS(permisos_json, '"*"')
  ));

-- 3b) administrador -> nomina.view, nomina.incidencias, nomina.calcular
UPDATE roles
SET permisos_json = JSON_ARRAY_APPEND(COALESCE(permisos_json, JSON_ARRAY()), '$', 'nomina.view')
WHERE clave = 'administrador'
  AND es_sistema = 1
  AND (permisos_json IS NULL OR (
        NOT JSON_CONTAINS(permisos_json, '"nomina.view"')
    AND NOT JSON_CONTAINS(permisos_json, '"nomina.all"')
    AND NOT JSON_CONTAINS(permisos_json, '"*"')
  ));

UPDATE roles
SET permisos_json = JSON_ARRAY_APPEND(COALESCE(permisos_json, JSON_ARRAY()), '$', 'nomina.incidencias')
WHERE clave = 'administrador'
  AND es_sistema = 1
  AND (permisos_json IS NULL OR (
        NOT JSON_CONTAINS(permisos_json, '"nomina.incidencias"')
    AND NOT JSON_CONTAINS(permisos_json, '"nomina.all"')
    AND NOT JSON_CONTAINS(permisos_json, '"*"')
  ));

UPDATE roles
SET permisos_json = JSON_ARRAY_APPEND(COALESCE(permisos_json, JSON_ARRAY()), '$', 'nomina.calcular')
WHERE clave = 'administrador'
  AND es_sistema = 1
  AND (permisos_json IS NULL OR (
        NOT JSON_CONTAINS(permisos_json, '"nomina.calcular"')
    AND NOT JSON_CONTAINS(permisos_json, '"nomina.all"')
    AND NOT JSON_CONTAINS(permisos_json, '"*"')
  ));

-- 4) Registro de la migracion.
INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES (@migration_name, 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;

-- Rollback manual seguro:
-- (solo si ningun hotel contrato el bloque: SELECT COUNT(*) FROM hotel_modulos hm
--  INNER JOIN modulos m ON m.id = hm.modulo_id WHERE m.clave = 'nomina_avanzada' AND hm.activo = 1;)
-- DELETE pm FROM plan_modulos pm INNER JOIN modulos m ON m.id = pm.modulo_id WHERE m.clave = 'nomina_avanzada';
-- DELETE hm FROM hotel_modulos hm INNER JOIN modulos m ON m.id = hm.modulo_id WHERE m.clave = 'nomina_avanzada';
-- DELETE FROM modulos WHERE clave = 'nomina_avanzada';
-- UPDATE roles SET permisos_json = JSON_REMOVE(permisos_json, JSON_UNQUOTE(JSON_SEARCH(permisos_json, 'one', 'nomina.all')))
--   WHERE es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'nomina.all') IS NOT NULL;
-- (repetir JSON_REMOVE para nomina.view / nomina.incidencias / nomina.calcular)
-- DELETE FROM migrations WHERE nombre = '20260704_001_nomina_core_bloque_permisos.sql';
