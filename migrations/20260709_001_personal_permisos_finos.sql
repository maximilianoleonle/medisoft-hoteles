-- Bloque Personal - permisos finos (auditoria Fable jul-2026).
-- Alcance:
-- - El bloque Personal (/trabajadores) se gateaba con usuarios.view (lectura)
--   y usuarios.edit / usuarios.create (gestion Y pago por Caja). Eso acoplaba
--   "editar usuarios del sistema" con "pagar nomina": un rol con usuarios.edit
--   podia pagar/revertir sin intencion. Ahora el gate usa personal.*:
--     personal.view      -> lectura (fichas, reportes, pre-nomina, simulador)
--     personal.gestionar -> altas/edicion, conceptos, anticipos, prestamos,
--                           asistencia y ciclo de pre-nomina (cerrar/aprobar/anular)
--     personal.pagar      -> pago por Caja, reversion y pago desde snapshot
-- - Backfill que PRESERVA el acceso actual de cada rol (por permiso, no por
--   clave, para cubrir tambien roles custom): quien podia pagar hoy
--   (usuarios.edit) recibe gestionar + pagar + view; quien solo leia
--   (usuarios.view sin edit) recibe view.
-- - Idempotente: guardas JSON_CONTAINS evitan duplicados; omite roles con
--   comodin '*' o 'personal.all'. Hoteles nuevos heredan los presets de
--   config/permisos.php via Rol::sembrarPresetsParaHotel().
-- - NO crea tablas, NO toca trabajador_*, NO toca Caja.

SET @migration_name := '20260709_001_personal_permisos_finos.sql';

START TRANSACTION;

-- A) Roles que HOY pueden operar Personal completo (tienen usuarios.edit):
--    reciben personal.view + personal.gestionar + personal.pagar.
UPDATE roles
SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'personal.view')
WHERE JSON_CONTAINS(permisos_json, '"usuarios.edit"')
  AND NOT JSON_CONTAINS(permisos_json, '"*"')
  AND NOT JSON_CONTAINS(permisos_json, '"personal.all"')
  AND NOT JSON_CONTAINS(permisos_json, '"personal.view"');

UPDATE roles
SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'personal.gestionar')
WHERE JSON_CONTAINS(permisos_json, '"usuarios.edit"')
  AND NOT JSON_CONTAINS(permisos_json, '"*"')
  AND NOT JSON_CONTAINS(permisos_json, '"personal.all"')
  AND NOT JSON_CONTAINS(permisos_json, '"personal.gestionar"');

UPDATE roles
SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'personal.pagar')
WHERE JSON_CONTAINS(permisos_json, '"usuarios.edit"')
  AND NOT JSON_CONTAINS(permisos_json, '"*"')
  AND NOT JSON_CONTAINS(permisos_json, '"personal.all"')
  AND NOT JSON_CONTAINS(permisos_json, '"personal.pagar"');

-- B) Roles que HOY solo LEEN Personal (usuarios.view sin usuarios.edit):
--    reciben personal.view.
UPDATE roles
SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'personal.view')
WHERE JSON_CONTAINS(permisos_json, '"usuarios.view"')
  AND NOT JSON_CONTAINS(permisos_json, '"usuarios.edit"')
  AND NOT JSON_CONTAINS(permisos_json, '"*"')
  AND NOT JSON_CONTAINS(permisos_json, '"personal.all"')
  AND NOT JSON_CONTAINS(permisos_json, '"personal.view"');

-- C) Registro de la migracion.
INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES (@migration_name, 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;

-- Rollback manual seguro (retira los tres permisos personal.* finos de todos
-- los roles que los tengan; repetir el JSON_REMOVE hasta que JSON_SEARCH sea
-- NULL para cada clave). El bloque Personal volveria a gatearse por
-- usuarios.* SOLO si tambien se revierte el codigo del controlador.
-- UPDATE roles SET permisos_json = JSON_REMOVE(permisos_json, JSON_UNQUOTE(JSON_SEARCH(permisos_json, 'one', 'personal.gestionar')))
--   WHERE JSON_SEARCH(permisos_json, 'one', 'personal.gestionar') IS NOT NULL;
-- UPDATE roles SET permisos_json = JSON_REMOVE(permisos_json, JSON_UNQUOTE(JSON_SEARCH(permisos_json, 'one', 'personal.pagar')))
--   WHERE JSON_SEARCH(permisos_json, 'one', 'personal.pagar') IS NOT NULL;
-- DELETE FROM migrations WHERE nombre = '20260709_001_personal_permisos_finos.sql';
