-- CxC / CxP - permisos finos (auditoria Fable jul-2026).
-- Alcance:
-- - CuentaPorCobrarController y CuentaPorPagarController se gateaban SOLO por
--   modulo (cuentas_cobrar / compras): sus permisos del catalogo eran
--   decorativos. Ahora el gate usa permisos:
--     cuentas_por_cobrar.view   -> lectura
--     cuentas_por_cobrar.cobrar -> registrar/revertir cobro + generar CxC
--     cuentas_por_pagar.view    -> lectura
--     cuentas_por_pagar.pagar   -> registrar/revertir pago + generar CxP
-- - Los presets de sistema YA traen la lectura (gerente=.all, administrador
--   =.view), asi que el enforcement no rompe la lectura. Este backfill
--   PRESERVA la capacidad de ESCRITURA que hoy tienen (por modulo, sin
--   permiso) los roles con .view pero sin .all: reciben la accion .cobrar /
--   .pagar. Los .all ya la cubren por comodin; no se tocan.
-- - Idempotente: guardas JSON_CONTAINS evitan duplicados; omite '*' y el
--   comodin de modulo (.all). Hoteles nuevos heredan los presets.

SET @migration_name := '20260710_001_cxc_cxp_permisos_finos.sql';

START TRANSACTION;

-- A) Roles con lectura de CxC pero sin control total -> accion cobrar.
UPDATE roles
SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'cuentas_por_cobrar.cobrar')
WHERE JSON_CONTAINS(permisos_json, '"cuentas_por_cobrar.view"')
  AND NOT JSON_CONTAINS(permisos_json, '"*"')
  AND NOT JSON_CONTAINS(permisos_json, '"cuentas_por_cobrar.all"')
  AND NOT JSON_CONTAINS(permisos_json, '"cuentas_por_cobrar.cobrar"');

-- B) Roles con lectura de CxP pero sin control total -> accion pagar.
UPDATE roles
SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'cuentas_por_pagar.pagar')
WHERE JSON_CONTAINS(permisos_json, '"cuentas_por_pagar.view"')
  AND NOT JSON_CONTAINS(permisos_json, '"*"')
  AND NOT JSON_CONTAINS(permisos_json, '"cuentas_por_pagar.all"')
  AND NOT JSON_CONTAINS(permisos_json, '"cuentas_por_pagar.pagar"');

-- C) Registro de la migracion.
INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES (@migration_name, 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;

-- Rollback manual seguro (retira las acciones finas; repetir JSON_REMOVE hasta
-- que JSON_SEARCH sea NULL). Volveria a gatearse por modulo SOLO si tambien se
-- revierte el codigo de los controladores.
-- UPDATE roles SET permisos_json = JSON_REMOVE(permisos_json, JSON_UNQUOTE(JSON_SEARCH(permisos_json, 'one', 'cuentas_por_cobrar.cobrar')))
--   WHERE JSON_SEARCH(permisos_json, 'one', 'cuentas_por_cobrar.cobrar') IS NOT NULL;
-- UPDATE roles SET permisos_json = JSON_REMOVE(permisos_json, JSON_UNQUOTE(JSON_SEARCH(permisos_json, 'one', 'cuentas_por_pagar.pagar')))
--   WHERE JSON_SEARCH(permisos_json, 'one', 'cuentas_por_pagar.pagar') IS NOT NULL;
-- DELETE FROM migrations WHERE nombre = '20260710_001_cxc_cxp_permisos_finos.sql';
