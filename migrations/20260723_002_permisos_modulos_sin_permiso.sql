-- Paso 1 de la auditoria de accesos (23 jul 2026): dar permisos a los modulos
-- que se vendian SIN ninguno definido.
--
-- Contexto: config/permisos.php es la fuente de verdad para los hoteles NUEVOS,
-- pero los hoteles YA sembrados tienen su propia fila en `roles` con el
-- permisos_json de cuando se crearon. Sin esta migracion, al activar los gates
-- (paso 2) los roles existentes se quedarian sin las areas nuevas y su gente
-- perderia accesos que hoy tiene.
--
-- Alcance:
-- - roles: SOLO los roles base (es_sistema = 1) gerente, administrador,
--   recepcionista y dueno_remoto, de TODOS los hoteles.
-- - NO toca propietario/superadmin (ya tienen el comodin '*').
-- - NO toca roles personalizados del hotel: esos los decide el propietario.
-- - NO toca hotel_usuarios (permisos por persona).
--
-- Metodo: se AGREGA cada permiso solo si no esta ya presente
-- (JSON_ARRAY_APPEND + guarda JSON_SEARCH). A diferencia de
-- 20260723_001_gerente_acceso_total.sql, esta NO pisa el permisos_json: si un
-- hotel personalizo su rol base, conserva lo suyo y solo suma lo nuevo.
--
-- Idempotente: re-ejecutarla no duplica ni cambia nada.
--
-- OJO: declarar el permiso no lo aplica. Mientras los controladores no llamen a
-- require_permission_or_403() (paso 2), esto solo puebla la matriz de la
-- pantalla Roles y permisos. Por eso es seguro correrla antes que el paso 2.

SET NAMES utf8mb4;
SET @migration_name := '20260723_002_permisos_modulos_sin_permiso.sql';

START TRANSACTION;

-- ---------------------------------------------------------------------------
-- 1) GERENTE — techo del hotel: control total de cada area nueva.
--    El '<modulo>.all' NO cubre areas nuevas, hay que nombrarlas una a una.
-- ---------------------------------------------------------------------------
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'motor_reservas.all')
 WHERE clave = 'gerente' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'motor_reservas.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'promociones.all')
 WHERE clave = 'gerente' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'promociones.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'upsells.all')
 WHERE clave = 'gerente' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'upsells.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'canales.all')
 WHERE clave = 'gerente' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'canales.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'whatsapp.all')
 WHERE clave = 'gerente' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'whatsapp.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'mensajes.all')
 WHERE clave = 'gerente' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'mensajes.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'checkin_digital.all')
 WHERE clave = 'gerente' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'checkin_digital.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'night_audit.all')
 WHERE clave = 'gerente' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'night_audit.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'lealtad.all')
 WHERE clave = 'gerente' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'lealtad.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'forecast.all')
 WHERE clave = 'gerente' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'forecast.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'operacion.all')
 WHERE clave = 'gerente' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'operacion.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'ia.all')
 WHERE clave = 'gerente' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'ia.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'copiloto.all')
 WHERE clave = 'gerente' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'copiloto.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'copiloto_ia.usar')
 WHERE clave = 'gerente' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'copiloto_ia.usar') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'auditoria.view')
 WHERE clave = 'gerente' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'auditoria.view') IS NULL;
-- 'Opiniones y encuestas' ya tenia .view pero ninguna accion: enviar encuestas
-- al huesped no puede autorizarlo un permiso de solo ver. Queda con .view y
-- .all a la vez; es redundante pero inocuo (el .all manda).
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'reputacion.all')
 WHERE clave = 'gerente' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'reputacion.all') IS NULL;

-- ---------------------------------------------------------------------------
-- 2) ADMINISTRADOR — operacion completa, menos lo que es de direccion:
--    la bitacora (auditoria.view) y las claves de WhatsApp (solo ve el estado).
-- ---------------------------------------------------------------------------
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'motor_reservas.all')
 WHERE clave = 'administrador' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'motor_reservas.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'promociones.all')
 WHERE clave = 'administrador' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'promociones.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'upsells.all')
 WHERE clave = 'administrador' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'upsells.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'canales.all')
 WHERE clave = 'administrador' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'canales.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'whatsapp.view')
 WHERE clave = 'administrador' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'whatsapp.view') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'mensajes.all')
 WHERE clave = 'administrador' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'mensajes.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'checkin_digital.all')
 WHERE clave = 'administrador' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'checkin_digital.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'night_audit.all')
 WHERE clave = 'administrador' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'night_audit.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'lealtad.all')
 WHERE clave = 'administrador' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'lealtad.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'forecast.all')
 WHERE clave = 'administrador' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'forecast.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'operacion.all')
 WHERE clave = 'administrador' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'operacion.all') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'ia.view')
 WHERE clave = 'administrador' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'ia.view') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'copiloto.usar')
 WHERE clave = 'administrador' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'copiloto.usar') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'copiloto.acciones')
 WHERE clave = 'administrador' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'copiloto.acciones') IS NULL;
-- El panel de uso del asistente hoy lo abre gerencia Y administracion (era un
-- gate por rol-string): se conserva para no quitarle un acceso que ya tiene.
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'copiloto.valor')
 WHERE clave = 'administrador' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'copiloto.valor') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'copiloto_ia.usar')
 WHERE clave = 'administrador' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'copiloto_ia.usar') IS NULL;
-- Hoy /reputacion no exige nada, asi que el administrador ya la usa: se le
-- concede para no quitarle un acceso que tiene al activar los gates.
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'reputacion.all')
 WHERE clave = 'administrador' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'reputacion.all') IS NULL;

-- ---------------------------------------------------------------------------
-- 3) RECEPCIONISTA — solo lo del mostrador: ve lo que entra por internet (no lo
--    concilia), manda las confirmaciones y recibe la identificacion del huesped.
-- ---------------------------------------------------------------------------
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'motor_reservas.view')
 WHERE clave = 'recepcionista' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'motor_reservas.view') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'mensajes.view')
 WHERE clave = 'recepcionista' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'mensajes.view') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'mensajes.enviar')
 WHERE clave = 'recepcionista' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'mensajes.enviar') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'checkin_digital.view')
 WHERE clave = 'recepcionista' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'checkin_digital.view') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'checkin_digital.generar')
 WHERE clave = 'recepcionista' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'checkin_digital.generar') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'checkin_digital.identificacion')
 WHERE clave = 'recepcionista' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'checkin_digital.identificacion') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'copiloto.usar')
 WHERE clave = 'recepcionista' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'copiloto.usar') IS NULL;

-- ---------------------------------------------------------------------------
-- 4) DUENO (REMOTO) — solo lectura, en la linea del Modo Dueno.
-- ---------------------------------------------------------------------------
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'operacion.view')
 WHERE clave = 'dueno_remoto' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'operacion.view') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'ia.view')
 WHERE clave = 'dueno_remoto' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'ia.view') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'forecast.view')
 WHERE clave = 'dueno_remoto' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'forecast.view') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'motor_reservas.view')
 WHERE clave = 'dueno_remoto' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'motor_reservas.view') IS NULL;
UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json, '$', 'auditoria.view')
 WHERE clave = 'dueno_remoto' AND es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'auditoria.view') IS NULL;

-- Registro de la migracion.
INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES (@migration_name, 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;

-- Rollback manual (quita SOLO los permisos que agrego esta migracion; conserva
-- todo lo demas, incluida cualquier personalizacion del hotel):
--
-- UPDATE roles SET permisos_json = JSON_REMOVE(permisos_json,
--        JSON_UNQUOTE(JSON_SEARCH(permisos_json, 'one', 'motor_reservas.all')))
--  WHERE es_sistema = 1 AND JSON_SEARCH(permisos_json, 'one', 'motor_reservas.all') IS NOT NULL;
--   ... (repetir por cada permiso listado arriba; JSON_REMOVE de uno en uno,
--        porque los indices se recorren al borrar) ...
-- DELETE FROM migrations WHERE nombre = '20260723_002_permisos_modulos_sin_permiso.sql';
